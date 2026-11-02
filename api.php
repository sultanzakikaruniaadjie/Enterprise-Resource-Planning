<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

const CASH0 = 30000000;
const PRICE = ['A' => 10000, 'B' => 15000];
const BOM   = ['A' => 3, 'B' => 2];
const COND = [
  'stok'  => ['Stok bahan baku tidak mencukupi', 'Terdampak: Produksi & Pembelian. Sistem memicu permintaan pembelian bahan tambahan dan memberi tahu Penjualan agar tanggal kirim disesuaikan.'],
  'ubah'  => ['Pelanggan mengubah jumlah pesanan', 'Terdampak: Penjualan, Produksi, Gudang. Kebutuhan stok & bahan baku dihitung ulang otomatis tanpa pencatatan ulang manual.'],
  'bayar' => ['Pembayaran pelanggan belum diterima', 'Terdampak: Keuangan. Pengiriman dapat menunggu status bayar, atau berjalan dengan piutang tercatat jelas.'],
  'telat' => ['Produksi mengalami keterlambatan', 'Terdampak: Produksi & Penjualan. Estimasi kirim diperbarui otomatis dan Penjualan diberi notifikasi.'],
];
const DIS = [
  'db'     => ['Server Database', 'Seluruh data tidak dapat dibaca/ditulis; semua modul berhenti.', 'Failover ke database cadangan; jika tidak ada, pulihkan dari backup terakhir dan validasi integritas data.'],
  'app'    => ['Server Aplikasi', 'Pengguna tidak bisa mengakses ERP walau database aktif (single point of failure).', 'Alihkan trafik via load balancer ke server cadangan; jangka panjang minimal 2 instance aktif.'],
  'net'    => ['Lapisan Jaringan', 'Pengguna jarak jauh kehilangan akses; pengguna LAN kantor tetap bisa bekerja.', 'Siapkan ISP cadangan; pengguna kantor bekerja via LAN sementara jaringan utama dipulihkan.'],
  'unauth' => ['Kontrol Akses', 'Risiko kebocoran/manipulasi data keuangan oleh pihak tidak berwenang.', 'Akses ditolak berdasar matriks hak akses; percobaan dicatat di log audit dan dilaporkan ke admin.'],
  'lost'   => ['Sistem Pencadangan', 'Data transaksi sejak backup terakhir hilang permanen.', 'Uji ulang jadwal backup otomatis; aktifkan backup ganda on-site dan off-site.'],
];
const TPL = [
  'buy'       => ['Pembelian bahan baku (kredit)', 'Persediaan Bahan Baku', 'Utang Usaha'],
  'payvendor' => ['Pembayaran utang pemasok', 'Utang Usaha', 'Kas'],
  'sell'      => ['Penjualan produk (kredit)', 'Piutang Usaha', 'Penjualan'],
  'recv'      => ['Penerimaan pembayaran pelanggan', 'Kas', 'Piutang Usaha'],
  'opex'      => ['Pembayaran beban operasional (tunai)', 'Beban Operasional', 'Kas'],
];

/* ---------- helpers ---------- */
function q($sql, $p = []) { global $db; $st = $db->prepare($sql); $st->execute($p); return $st; }
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp' . number_format(round($n), 0, ',', '.'); }
function kv($a, $b) { return "<div class='kv'><span>$a</span><b>$b</b></div>"; }
function inv($c) { return q("SELECT * FROM inventory WHERE code=?", [$c])->fetch(); }
function jr($d, $db_, $cr, $amt, $tag = '', $ref = null) {
  q("INSERT INTO journal (descr,debit,credit,amt,tag,ref_id) VALUES (?,?,?,?,?,?)", [$d, $db_, $cr, $amt, $tag, $ref]);
}
function logsim($m, $in, $res) { q("INSERT INTO sim_log (module,input,result) VALUES (?,?,?)", [$m, json_encode($in), $res]); }
function tier($v) { return $v <= 5000000 ? [1, 'Kepala Lab'] : ($v <= 20000000 ? [2, 'Pimpinan'] : [3, 'Direktur']); }

function netNeeds($qty, $usePo) {
  $r = [];
  foreach (['A', 'B'] as $k) {
    $m = inv($k); $gross = $qty * BOM[$k]; $in = $usePo ? $m['incoming'] : 0;
    $r[] = ['k' => $k, 'gross' => $gross, 'stock' => $m['stock'], 'in' => $in, 'net' => max($gross - $m['stock'] - $in, 0), 'lead' => $m['lead']];
  }
  return $r;
}
function receive($item, $qty, $autoj) {
  $m = inv($item);
  if ($qty <= 0) return 'Jumlah diterima harus lebih dari 0.';
  if ($qty > $m['incoming']) return "<b>Ditolak:</b> jumlah diterima ($qty) melebihi sisa pesanan Bahan $item ({$m['incoming']} unit).";
  q("UPDATE inventory SET stock=stock+?, incoming=incoming-? WHERE code=?", [$qty, $qty, $item]);
  $left = $qty;
  foreach (q("SELECT * FROM purchase_orders WHERE item=? AND recv<qty ORDER BY id", [$item])->fetchAll() as $d) {
    if ($left <= 0) break;
    $t = min($left, $d['qty'] - $d['recv']);
    q("UPDATE purchase_orders SET recv=recv+? WHERE id=?", [$t, $d['id']]);
    $left -= $t;
  }
  if ($autoj) jr("Penerimaan $qty unit Bahan $item (kredit, otomatis dari pembelian)", 'Persediaan Bahan Baku', 'Utang Usaha', $qty * PRICE[$item], 'otomatis');
  return null;
}

/* ---------- state (kunci = id elemen HTML) ---------- */
function state() {
  $j = q("SELECT * FROM journal ORDER BY id")->fetchAll();
  $ac = q("SELECT * FROM accounts ORDER BY code")->fetchAll();
  $bal = function ($n, $normal) use ($j) {
    $b = $n === 'Kas' ? CASH0 : 0;
    foreach ($j as $x) {
      if ($x['debit'] === $n) $b += $normal === 'debit' ? $x['amt'] : -$x['amt'];
      if ($x['credit'] === $n) $b += $normal === 'debit' ? -$x['amt'] : $x['amt'];
    }
    return $b;
  };
  $s = [];
  $s['p1-stockview'] = inv('PRODUK')['stock'];
  $s['p1-rawview'] = inv('RAW')['stock'];

  $t = '<table class="data"><tr><th>#</th><th>Pelanggan</th><th>Qty</th><th>Total</th><th>Status</th><th>Waktu</th></tr>';
  foreach (q("SELECT * FROM orders ORDER BY id DESC LIMIT 8")->fetchAll() as $o)
    $t .= "<tr><td>{$o['id']}</td><td>" . h($o['customer']) . "</td><td>{$o['qty']}</td><td>" . rp($o['total']) . "</td><td>{$o['status']}</td><td>{$o['created_at']}</td></tr>";
  $s['p1-orders'] = $t . '</table>';

  $b = '';
  foreach (q("SELECT id,label FROM backups ORDER BY id DESC LIMIT 8")->fetchAll() as $x)
    $b .= "<div class='logitem'><span>" . h($x['label']) . "</span><button class='btn ghost sm' data-act='p2_restore' data-extra='{$x['id']}' data-out='p2-result'>Pulihkan</button></div>";
  $s['p2-backuplist'] = $b ?: '<span class="hint">Belum ada backup.</span>';

  $s['p4-coa'] = '<table class="data"><tr><th>Kode</th><th>Akun</th><th>Tipe</th><th>Saldo Normal</th></tr>' .
    implode('', array_map(fn($a) => "<tr><td>{$a['code']}</td><td>{$a['name']}</td><td>{$a['type']}</td><td>{$a['normal']}</td></tr>", $ac)) . '</table>';

  $t = '<table class="data"><tr><th>#</th><th>Keterangan</th><th>Debit</th><th>Kredit</th><th>Nominal</th></tr>';
  foreach ($j as $x) {
    $pill = $x['tag'] ? " <span class='pill " . ($x['tag'] === 'duplikat' ? 'bad' : 'ok') . "'>{$x['tag']}</span>" : '';
    $t .= "<tr><td>{$x['id']}</td><td>" . h($x['descr']) . "$pill</td><td>{$x['debit']}</td><td>{$x['credit']}</td><td class='num'>" . rp($x['amt']) . "</td></tr>";
  }
  $s['p4-journal'] = $t . '</table>';

  $t = '';
  foreach ($ac as $a) {
    $t .= "<h4>{$a['name']} <span class='badge'>{$a['normal']}</span></h4><table class='data'><tr><th>Keterangan</th><th>Debit</th><th>Kredit</th></tr>";
    if ($a['name'] === 'Kas') $t .= "<tr><td><em>Saldo awal</em></td><td colspan='2' class='num'>" . rp(CASH0) . "</td></tr>";
    foreach ($j as $x) {
      if ($x['debit'] === $a['name']) $t .= "<tr><td>" . h($x['descr']) . "</td><td class='num'>" . rp($x['amt']) . "</td><td>—</td></tr>";
      if ($x['credit'] === $a['name']) $t .= "<tr><td>" . h($x['descr']) . "</td><td>—</td><td class='num'>" . rp($x['amt']) . "</td></tr>";
    }
    $t .= "</table>" . kv('Saldo akhir', rp($bal($a['name'], $a['normal'])));
  }
  $s['p4-ledger'] = $t;

  $td = $tk = 0;
  $t = '<table class="data"><tr><th>Akun</th><th>Debit</th><th>Kredit</th></tr>';
  foreach ($ac as $a) {
    $v = $bal($a['name'], $a['normal']); $isD = $a['normal'] === 'debit';
    $d = $isD ? max($v, 0) : max(-$v, 0); $k = $isD ? max(-$v, 0) : max($v, 0);
    $td += $d; $tk += $k;
    $t .= "<tr><td>{$a['name']}</td><td class='num'>" . ($d ? rp($d) : '—') . "</td><td class='num'>" . ($k ? rp($k) : '—') . "</td></tr>";
  }
  $t .= "<tr><th>Total</th><th class='num'>" . rp($td) . "</th><th class='num'>" . rp($tk) . "</th></tr></table>";
  if (round($td) != round($tk)) $t .= "<p class='bad-t'>Neraca saldo tidak seimbang.</p>";
  $s['p4-trial'] = $t;

  $t = '<table class="data"><tr><th>Komponen</th><th>Stok</th><th>Pesanan belum diterima</th><th>Waktu pengadaan</th></tr>';
  foreach (['A', 'B'] as $k) { $m = inv($k); $t .= "<tr><td>Bahan $k</td><td>{$m['stock']}</td><td>{$m['incoming']}</td><td>{$m['lead']} hari kerja</td></tr>"; }
  $s['p5-master'] = $t . '</table>';

  $t = '<table class="data"><tr><th>No. PO</th><th>Bahan</th><th>Qty</th><th>Diterima</th><th>Sisa</th><th>ETA</th><th>Status</th></tr>';
  foreach (q("SELECT * FROM purchase_orders ORDER BY id")->fetchAll() as $d) {
    $l = $d['qty'] - $d['recv'];
    $st = $l == 0 ? 'Diterima penuh' : ($d['recv'] > 0 ? 'Diterima sebagian' : 'PO terbit');
    $t .= "<tr><td>{$d['no_po']}</td><td>{$d['item']}</td><td>{$d['qty']}</td><td>{$d['recv']}</td><td>$l</td><td>{$d['eta']} hr</td><td>$st</td></tr>";
  }
  $s['p5-docs'] = $t . '</table>';
  return $s;
}

/* ---------- actions ---------- */
$a = $_POST['action'] ?? 'state';
$P = $_POST; $res = null;
try {
  $db->beginTransaction();
  switch ($a) {
    case 'p1_run':
      $qty = (int)($P['p1-qty'] ?? 0); $cust = h(trim($P['p1-cust'] ?? '') ?: 'Pelanggan'); $cond = $P['p1-cond'] ?? 'normal';
      if ($qty <= 0) { $res = ['bad', 'Jumlah pesanan harus lebih dari 0.']; break; }
      $s = inv('PRODUK')['stock']; $r = inv('RAW')['stock']; $need = $qty * 3; $tot = $qty * 150000;
      $ok = $qty <= $s && $need <= $r;
      $html = kv('Pesanan', "$qty unit — $cust") . kv('Gudang (stok produk)', "$s → " . max($s - $qty, 0) . " unit") .
              kv('Produksi (bahan baku)', "$need dari $r tersedia") . kv('Keuangan', 'Dr Piutang Usaha / Cr Penjualan ' . rp($tot));
      $cls = 'ok';
      if (!$ok) { $cls = 'bad'; $html .= '<p><b>Peringatan:</b> stok produk/bahan baku tidak mencukupi — pesanan ditolak.</p>'; }
      if (isset(COND[$cond])) { $html .= '<hr><p><b>Dampak: ' . COND[$cond][0] . '</b><br>' . COND[$cond][1] . '</p>'; $cls = 'warn'; }
      q("INSERT INTO orders (customer,qty,total,kondisi,status) VALUES (?,?,?,?,?)", [$cust, $qty, $tot, $cond, $ok ? 'diproses' : 'ditolak']);
      if ($ok) {
        $oid = $db->lastInsertId();
        q("UPDATE inventory SET stock=stock-? WHERE code='PRODUK'", [$qty]);
        q("UPDATE inventory SET stock=stock-? WHERE code='RAW'", [$need]);
        jr("Penjualan pesanan #$oid ke $cust", 'Piutang Usaha', 'Penjualan', $tot, 'otomatis');
      }
      $res = [$cls, $html]; break;

    case 'p1_reset':
      q("UPDATE inventory SET stock=120 WHERE code='PRODUK'"); q("UPDATE inventory SET stock=500 WHERE code='RAW'");
      $res = ['ok', 'Stok dikembalikan ke kondisi awal (riwayat pesanan tetap tersimpan).']; break;

    case 'p2_backup':
      $snap = ['inventory' => q("SELECT * FROM inventory")->fetchAll(), 'journal' => q("SELECT * FROM journal")->fetchAll(), 'purchase_orders' => q("SELECT * FROM purchase_orders")->fetchAll()];
      q("INSERT INTO backups (label,snapshot) VALUES (?,?)", ['Snapshot ' . date('d/m/Y H:i:s'), json_encode($snap)]); break;

    case 'p2_clear': q("DELETE FROM backups"); break;

    case 'p2_restore':
      $b = q("SELECT * FROM backups WHERE id=?", [(int)$P['id']])->fetch();
      if (!$b) { $res = ['bad', 'Snapshot tidak ditemukan.']; break; }
      $snap = json_decode($b['snapshot'], true);
      foreach (['inventory', 'journal', 'purchase_orders'] as $tb) {
        q("DELETE FROM $tb");
        foreach ($snap[$tb] as $row) {
          $cols = array_keys($row);
          q("INSERT INTO $tb (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")", array_values($row));
        }
      }
      $res = ['ok', 'Database dipulihkan ke <b>' . h($b['label']) . '</b>: persediaan, jurnal, dan PO dikembalikan ke kondisi snapshot.']; break;

    case 'p2_sim':
      $d = DIS[$P['p2-sim']] ?? DIS['db'];
      logsim('P2', ['gangguan' => $P['p2-sim']], $d[0]);
      $res = ['bad', kv('Titik kegagalan', $d[0]) . "<p><b>Dampak:</b> $d[1]</p><p><b>Tindakan:</b> $d[2]</p>"]; break;

    case 'p2_scale':
      $u = (int)$P['p2-users']; $bg = (int)$P['p2-budget']; $ratio = $u / 100;
      $big = $ratio * 100 > $bg + 100;
      $v = $big ? 'Kapasitas perlu naik ±' . round(($ratio - 1) * 100) . "% namun anggaran hanya naik $bg% — scaling vertikal penuh tidak cukup. Rekomendasi: scaling horizontal bertahap pada server aplikasi, caching data master, antrian untuk proses berat, redundansi penuh ditunda ke fase berikutnya."
                  : 'Kenaikan anggaran sepadan dengan kenaikan pengguna — scaling bertahap dengan monitoring performa sudah cukup.';
      logsim('P2', ['users' => $u, 'budget' => $bg], $big ? 'anggaran kurang' : 'cukup');
      $res = [$big ? 'warn' : 'ok', kv('Rasio pengguna', number_format($ratio, 1) . "× (100 → $u)") . kv('Kenaikan anggaran', "$bg%") . "<p>$v</p>"]; break;

    case 'p3_check':
      $o = (float)$P['p3-old']; $n = (float)$P['p3-new']; $t1 = tier($o); $t2 = tier($n); $same = $t1[0] === $t2[0];
      logsim('P3', ['old' => $o, 'new' => $n], $same ? 'tidak perlu ulang' : 'persetujuan ulang');
      $res = [$same ? 'ok' : 'bad', kv('Jenjang awal', "$t1[1] (" . rp($o) . ")") . kv('Jenjang nilai baru', "$t2[1] (" . rp($n) . ")") .
        '<p><b>Hasil:</b> ' . ($same ? 'Persetujuan sebelumnya masih berlaku.' : "Persetujuan harus diulang — dibutuhkan otorisasi $t2[1].") . '</p>']; break;

    case 'p4_add':
      $amt = (float)$P['p4-amt']; $t = TPL[$P['p4-type']] ?? null;
      if ($t && $amt > 0) jr($t[0], $t[1], $t[2], $amt); break;

    case 'p4_dup':
      $o = q("SELECT * FROM journal WHERE debit='Utang Usaha' AND credit='Kas' AND tag='' ORDER BY id LIMIT 1")->fetch();
      if (!$o) { $res = ['bad', 'Tidak ada pembayaran utang pemasok untuk diduplikasi.']; break; }
      jr($o['descr'], $o['debit'], $o['credit'], $o['amt'], 'duplikat', $o['id']);
      $res = ['bad', '<b>Terdeteksi anomali:</b> pembayaran ' . rp($o['amt']) . " tercatat dua kali (#{$o['id']} dan #" . $db->lastInsertId() . ").<br>Kas berkurang terlalu banyak dan Utang Usaha lebih rendah dari seharusnya. Klik “Buat jurnal koreksi”."]; break;

    case 'p4_fix':
      $d = q("SELECT * FROM journal WHERE tag='duplikat' AND id NOT IN (SELECT ref_id FROM journal WHERE tag='koreksi' AND ref_id IS NOT NULL) ORDER BY id LIMIT 1")->fetch();
      if (!$d) { $res = ['warn', 'Tidak ada duplikasi yang perlu dikoreksi.']; break; }
      jr("Jurnal pembalik koreksi duplikasi #{$d['id']}", $d['credit'], $d['debit'], $d['amt'], 'koreksi', $d['id']);
      $res = ['ok', "<b>Koreksi tercatat:</b> Dr {$d['credit']} / Cr {$d['debit']} " . rp($d['amt']) . ". Transaksi asli dan duplikat <em>tidak dihapus</em> — jejak audit tetap utuh."]; break;

    case 'p5_calc':
      $rows = netNeeds((int)$P['p5-qty'], !empty($P['p5-usepo'])); $dl = (float)$P['p5-deadline2'];
      $html = '<table class="data"><tr><th>Komponen</th><th>Bruto</th><th>Stok</th><th>PO dipakai</th><th>Bersih</th><th>Lead</th><th>Status</th></tr>';
      $late = []; $buy = [];
      foreach ($rows as $r) {
        $st = $r['net'] <= 0 ? 'Aman (tanpa beli)' : ($r['lead'] > $dl ? 'Terlambat ' . ($r['lead'] - $dl) . ' hr' : 'Aman');
        if ($r['net'] > 0) { $buy[] = "{$r['net']} unit Bahan {$r['k']}"; if ($r['lead'] > $dl) $late[] = $r['k']; }
        $html .= "<tr><td>Bahan {$r['k']}</td><td>{$r['gross']}</td><td>{$r['stock']}</td><td>{$r['in']}</td><td><b>{$r['net']}</b></td><td>{$r['lead']} hr</td><td>$st</td></tr>";
      }
      $html .= '</table>';
      if ($late) $html .= '<p class="bad-t"><b>Peringatan:</b> waktu pengadaan Bahan ' . implode(' dan ', $late) . " melebihi batas $dl hari kerja — lihat Tahap 5.</p>";
      $html .= '<p><b>Rekomendasi PR:</b> ' . ($buy ? implode(' dan ', $buy) : 'tidak diperlukan.') . '</p>';
      $res = [$late ? 'bad' : ($buy ? 'warn' : 'ok'), $html]; break;

    case 'p5_order':
      $needs = array_filter(netNeeds((int)$P['p5-qty'], !empty($P['p5-usepo'])), fn($r) => $r['net'] > 0);
      if (!$needs) { $res = ['ok', 'Tidak ada kekurangan material — PR/PO tidak perlu dibuat.']; break; }
      $dl = (float)$P['p5-deadline2']; $html = ''; $late = [];
      foreach ($needs as $r) {
        $no = 'PO-' . str_pad(q("SELECT COUNT(*) c FROM purchase_orders")->fetch()['c'] + 1, 3, '0', STR_PAD_LEFT);
        q("INSERT INTO purchase_orders (no_po,item,qty,eta) VALUES (?,?,?,?)", [$no, $r['k'], $r['net'], $r['lead']]);
        q("UPDATE inventory SET incoming=incoming+? WHERE code=?", [$r['net'], $r['k']]);
        if ($r['lead'] > $dl) $late[] = $r['k'];
        $html .= kv("$no — PR → disetujui → PO terbit", "{$r['net']} unit Bahan {$r['k']} (" . rp($r['net'] * PRICE[$r['k']]) . ")");
      }
      if ($late) $html .= '<p class="bad-t"><b>Peringatan:</b> Bahan ' . implode(' dan ', $late) . " tiba lebih lama dari $dl hari kerja. Gunakan Tahap 5.</p>";
      $res = [$late ? 'bad' : 'ok', $html]; break;

    case 'p5_recvall':
      $html = '';
      foreach (['A', 'B'] as $k) {
        $qn = inv($k)['incoming'];
        if ($qn > 0 && receive($k, $qn, !empty($P['p5-autojournal'])) === null) $html .= kv("Penerimaan PO Bahan $k", "+$qn unit");
      }
      $res = [$html ? 'ok' : 'warn', $html ?: 'Tidak ada PO terbuka untuk diterima.']; break;

    case 'p5_recv':
      $item = $P['p5-recv-item'] === 'B' ? 'B' : 'A'; $qty = (int)$P['p5-recv-qty'];
      $err = receive($item, $qty, !empty($P['p5-autojournal']));
      if ($err) { $res = ['bad', $err]; break; }
      $m = inv($item);
      $res = ['ok', kv('Diterima', "$qty unit Bahan $item") . kv('Stok sekarang', $m['stock']) . kv('Sisa belum diterima', $m['incoming']) .
        '<p>Status PO: ' . ($m['incoming'] > 0 ? 'diterima sebagian' : 'diterima penuh') . (!empty($P['p5-autojournal']) ? '. Jurnal utang otomatis tercatat di Keuangan.' : '') . '</p>']; break;

    case 'p5_risk':
      $lead = (int)$P['p5-leadtime']; $dl = (int)$P['p5-deadline2'];
      q("UPDATE inventory SET lead=? WHERE code='B'", [$lead]);
      q("UPDATE purchase_orders SET eta=? WHERE item='B' AND recv<qty", [$lead]);
      logsim('P5', ['lead' => $lead, 'deadline' => $dl], $lead > $dl ? 'terlambat' : 'aman');
      $html = kv('Waktu kirim pemasok', "$lead hari") . kv('Batas produksi', "$dl hari");
      if ($lead > $dl) {
        $html .= '<p><b>Risiko:</b> terlambat ' . ($lead - $dl) . " hari. <b>Rekomendasi:</b> cari pemasok alternatif ≤ $dl hari, pakai stok dari gudang/lokasi lain, atau jadwalkan ulang produksi. Pertimbangkan biaya tiap opsi.</p>" .
                  '<button class="btn" data-act="p5_applyalt" data-in="p5-altlead,p5-deadline2" data-out="p5-riskresult">Terapkan pemasok alternatif</button>';
        $res = ['bad', $html];
      } else $res = ['ok', $html . '<p><b>Hasil:</b> pemasok masih memenuhi tenggat; tetap pantau konfirmasi pengiriman.</p>'];
      break;

    case 'p5_applyalt':
      $alt = (int)$P['p5-altlead']; $dl = (int)$P['p5-deadline2'];
      q("UPDATE inventory SET lead=? WHERE code='B'", [$alt]);
      q("UPDATE purchase_orders SET eta=? WHERE item='B' AND recv<qty", [$alt]);
      $res = [$alt <= $dl ? 'ok' : 'bad', "<b>Pemasok alternatif diterapkan:</b> lead time Bahan B menjadi $alt hari; PO terbuka diperbarui. " . ($alt <= $dl ? "Memenuhi batas $dl hari." : "Masih melebihi batas $dl hari.")]; break;
  }
  $db->commit();
  echo json_encode(['res' => $res, 'state' => state()]);
} catch (Throwable $e) {
  if ($db->inTransaction()) $db->rollBack();
  http_response_code(500);
  echo json_encode(['res' => ['bad', 'Kesalahan server: ' . h($e->getMessage())], 'state' => new stdClass]);
}
