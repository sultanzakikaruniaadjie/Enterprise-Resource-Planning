<?php
$access = [
  'Penjualan' => ['rw','r','r','n','r'], 'Gudang' => ['r','rw','r','r','r'], 'Produksi' => ['r','r','rw','r','n'],
  'Pembelian' => ['n','r','r','rw','r'], 'Keuangan' => ['r','n','n','r','rw'],
];
$col = ['rw' => 'var(--good)', 'r' => 'var(--warn)', 'n' => 'var(--border)'];
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Simulator Praktikum ERP</title>
<style>
:root{--bg:#f3f5f4;--sf:#fff;--sf2:#eceff0;--bd:#d7dcdb;--ink:#13201c;--dim:#56645f;--ac:#0f6e5c;--acink:#fff;--good:#1a7f4e;--warn:#9a6208;--bad:#a93224;--gs:rgba(26,127,78,.12);--ws:rgba(180,114,12,.14);--bs:rgba(169,50,36,.12)}
@media(prefers-color-scheme:dark){:root{--bg:#0e1513;--sf:#161f1c;--sf2:#1c2622;--bd:#2c3832;--ink:#e8efea;--dim:#a2b0a9;--ac:#4bcba6;--acink:#06211b;--good:#56cf93;--warn:#e3ac4c;--bad:#e2766a;--gs:rgba(86,207,147,.14);--ws:rgba(227,172,76,.14);--bs:rgba(226,118,106,.14)}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.5 system-ui,sans-serif}
nav{position:sticky;top:0;display:flex;gap:6px;overflow-x:auto;padding:10px 16px;background:var(--sf);border-bottom:1px solid var(--bd);z-index:5}
nav b{align-self:center;margin-right:10px}nav button{border:1px solid var(--bd);background:var(--sf2);color:var(--dim);border-radius:99px;padding:7px 13px;cursor:pointer;white-space:nowrap}
nav button.on{background:var(--ac);color:var(--acink);border-color:var(--ac)}
main{max-width:980px;margin:auto;padding:22px 16px 70px}.panel{display:none}.panel.on{display:block}
h1{font-size:21px;margin:0 0 4px}h3{font-size:15px;margin:0 0 10px}h4{font-size:13px;margin:14px 0 6px}
.card{background:var(--sf);border:1px solid var(--bd);border-radius:14px;padding:16px 18px;margin:14px 0}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:720px){.g2{grid-template-columns:1fr}}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}.f{display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;color:var(--dim)}
input,select{font:inherit;padding:8px 10px;border:1px solid var(--bd);border-radius:8px;background:var(--sf);color:var(--ink);min-width:150px}
.btn{font:inherit;font-weight:600;padding:9px 15px;border-radius:9px;border:1px solid var(--ac);background:var(--ac);color:var(--acink);cursor:pointer}
.btn.ghost{background:none;color:var(--ac)}.btn.danger{background:var(--bad);border-color:var(--bad);color:#fff}.btn.sm{padding:3px 9px;font-size:12px}
.res{margin-top:12px;padding:12px 14px;border:1px dashed var(--bd);border-radius:10px;background:var(--sf2)}
.res.ok{border:1px solid var(--good);background:var(--gs)}.res.warn{border:1px solid var(--warn);background:var(--ws)}.res.bad{border:1px solid var(--bad);background:var(--bs)}
.res p{margin:8px 0 0}.res hr{border:0;border-top:1px solid var(--bd)}.bad-t{color:var(--bad)}
.kv{display:flex;justify-content:space-between;gap:12px;padding:3px 0}.kv b{font-family:ui-monospace,monospace}
table.data{width:100%;border-collapse:collapse;font-size:13px}.data th{text-align:left;font-size:11px;text-transform:uppercase;color:var(--dim);padding:6px 9px;border-bottom:1px solid var(--bd)}
.data td{padding:7px 9px;border-bottom:1px solid var(--bd)}.num{text-align:right;font-family:ui-monospace,monospace}
.sc{overflow-x:auto}.hint,.note{font-size:12.5px;color:var(--dim)}ol,ul{margin:0;padding-left:20px}li{margin-bottom:5px}
.pill{padding:1px 8px;border-radius:99px;font-size:11px;font-weight:600}.pill.ok{background:var(--gs);color:var(--good)}.pill.bad{background:var(--bs);color:var(--bad)}
.badge{font-size:10.5px;background:var(--sf2);padding:2px 6px;border-radius:5px}
.logitem{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:6px 9px;margin-bottom:6px;border-radius:7px;background:var(--sf2);font-size:12.5px}
.tabs{display:flex;gap:6px;margin-bottom:12px}.tabs button{border:1px solid var(--bd);background:var(--sf);color:var(--dim);border-radius:99px;padding:6px 12px;cursor:pointer}.tabs button.on{background:var(--ink);color:var(--bg)}
.dot{display:inline-block;width:10px;height:10px;border-radius:50%}.tp{display:none}.tp.on{display:block}
.hi{color:var(--warn);font-weight:700}
</style></head><body>
<nav><b>Simulator ERP</b>
  <button class="on" data-p="p1">P1 Evolusi ERP</button><button data-p="p2">P2 Arsitektur</button><button data-p="p3">P3 Proses Bisnis</button>
  <button data-p="p4">P4 Keuangan</button><button data-p="p5">P5 SCM</button></nav>
<main>

<section class="panel on" id="p1">
  <h1>Praktikum 1 — Evolusi dan Integrasi ERP</h1>
  <div class="card"><h3>Sebelum ERP: aplikasi terpisah</h3>
    <table class="data"><tr><th>Departemen</th><th>Sistem</th><th>Masalah</th></tr>
      <tr><td>Penjualan</td><td>Spreadsheet</td><td>Data pesanan terlambat diperbarui</td></tr>
      <tr><td>Gudang</td><td>Aplikasi stok</td><td>Stok berbeda dengan catatan pembelian</td></tr>
      <tr><td>Produksi</td><td>Formulir manual</td><td>Jadwal produksi sering berubah</td></tr>
      <tr><td>Keuangan</td><td>Aplikasi akuntansi</td><td>Transaksi harus dimasukkan ulang</td></tr></table>
    <p class="note">Setelah ERP: Penjualan, Gudang, Produksi, dan Keuangan membaca/menulis ke <b>satu basis data bersama</b> (database MySQL ini). Data master: produk, pelanggan, bahan baku, BOM, chart of accounts.</p></div>
  <div class="card"><h3>Simulasi transaksi pesanan pelanggan</h3>
    <p class="note">Komponen Otomotif Tipe A · Rp150.000/unit · stok produk <b id="p1-stockview">…</b> unit · 3 bahan baku/unit (stok <b id="p1-rawview">…</b>). Pesanan yang diproses otomatis menjurnal ke Keuangan (P4).</p>
    <div class="row">
      <label class="f">Pelanggan<input id="p1-cust" value="Bengkel Jaya Motor"></label>
      <label class="f">Jumlah (unit)<input id="p1-qty" type="number" value="40" min="1"></label>
      <label class="f">Kondisi tambahan<select id="p1-cond"><option value="normal">Normal</option><option value="stok">Stok bahan baku tidak cukup</option><option value="ubah">Pelanggan ubah jumlah</option><option value="bayar">Pembayaran belum diterima</option><option value="telat">Produksi terlambat</option></select></label>
      <button class="btn" data-act="p1_run" data-in="p1-cust,p1-qty,p1-cond" data-out="p1-result">Simulasikan</button>
      <button class="btn ghost" data-act="p1_reset" data-out="p1-result">Reset stok</button></div>
    <div class="res" id="p1-result"><span class="hint">Hasil akan tampil di sini.</span></div></div>
  <div class="card"><h3>Riwayat pesanan (dari database)</h3><div class="sc" id="p1-orders"></div></div>
</section>

<section class="panel" id="p2">
  <h1>Praktikum 2 — Arsitektur Teknis ERP (100 pengguna)</h1>
  <div class="card"><h3>Arsitektur</h3>
    <ol><li>Pengguna kantor (LAN) &amp; jarak jauh (Internet + VPN)</li><li>Firewall + Load Balancer</li><li><span class="hi">Server Aplikasi ERP</span> (single point of failure pada desain dasar)</li><li>Server Database</li><li>Sistem Pencadangan (Backup)</li></ol></div>
  <div class="g2">
    <div class="card"><h3>Matriks hak akses</h3><div class="sc"><table class="data"><tr><th>Modul</th><th>Penj.</th><th>Gud.</th><th>Prod.</th><th>Pemb.</th><th>Keu.</th></tr>
      <?php foreach ($access as $m => $r): ?><tr><td><?= $m ?></td><?php foreach ($r as $v): ?><td><span class="dot" style="background:<?= $col[$v] ?>"></span></td><?php endforeach ?></tr><?php endforeach ?></table></div>
      <p class="note"><span class="dot" style="background:var(--good)"></span> Baca &amp; tulis · <span class="dot" style="background:var(--warn)"></span> Baca saja · <span class="dot" style="background:var(--border)"></span> Tanpa akses</p></div>
    <div class="card"><h3>Backup &amp; pemulihan (nyata)</h3>
      <p class="note">Backup menyimpan snapshot persediaan, jurnal, dan PO. “Pulihkan” mengembalikan data ke snapshot itu.</p>
      <div class="row"><button class="btn" data-act="p2_backup">Backup sekarang</button><button class="btn ghost" data-act="p2_clear">Bersihkan</button></div>
      <div id="p2-backuplist" style="margin-top:12px"></div></div></div>
  <div class="card"><h3>Simulasi gangguan sistem</h3>
    <div class="row"><label class="f">Kondisi<select id="p2-sim"><option value="db">Database tidak dapat diakses</option><option value="app">Server aplikasi gangguan</option><option value="net">Internet terputus</option><option value="unauth">Pengguna tidak berwenang buka menu keuangan</option><option value="lost">Data hilang akibat backup gagal</option></select></label>
      <button class="btn" data-act="p2_sim" data-in="p2-sim" data-out="p2-result">Tampilkan penanganan</button></div>
    <div class="res" id="p2-result"><span class="hint">Pilih kondisi gangguan.</span></div></div>
  <div class="card"><h3>100 → 800 pengguna, anggaran naik 15%</h3>
    <div class="row"><label class="f">Pengguna baru<input id="p2-users" type="number" value="800"></label><label class="f">Kenaikan anggaran (%)<input id="p2-budget" type="number" value="15"></label>
      <button class="btn" data-act="p2_scale" data-in="p2-users,p2-budget" data-out="p2-scaleresult">Evaluasi kapasitas</button></div>
    <div class="res" id="p2-scaleresult"><span class="hint">Hasil evaluasi.</span></div></div>
</section>

<section class="panel" id="p3">
  <h1>Praktikum 3 — Proses Bisnis &amp; Alur Persetujuan</h1>
  <div class="g2">
    <div class="card"><h3>As-is</h3><ol><li>Dosen ajukan permintaan</li><li>Kalab verifikasi (perkiraan)</li><li>Gudang cek stok</li><li>Pembelian pilih pemasok</li><li>Pimpinan menyetujui</li><li>Gudang terima barang</li><li>Keuangan proses pembayaran</li></ol></div>
    <div class="card"><h3>To-be</h3><ol><li>Dosen input di ERP</li><li>Sistem cek stok otomatis</li><li>Kalab setujui (jika kurang)</li><li>PO diterbitkan sistem</li><li>Pimpinan setujui jika &gt; ambang</li><li>Gudang terima, stok ter-update</li><li>Pembayaran otomatis dari PO</li></ol></div></div>
  <div class="card"><h3>Aturan bisnis</h3><ul><li>Permintaan harus punya identitas pengaju tercatat.</li><li>Permintaan diperiksa terhadap stok gudang otomatis.</li><li>PO di atas ambang wajib disetujui pihak berwenang.</li><li>Pembayaran hanya jika permintaan, PO, dan bukti terima cocok (3 dokumen).</li></ul></div>
  <div class="card"><h3>Simulasi nilai pembelian berubah</h3>
    <p class="note">≤ Rp5.000.000 Kepala Lab · Rp5.000.001–20.000.000 Pimpinan · &gt; Rp20.000.000 Direktur</p>
    <div class="row"><label class="f">Nilai PO disetujui (Rp)<input id="p3-old" type="number" value="4500000"></label><label class="f">Nilai PO baru (Rp)<input id="p3-new" type="number" value="6200000"></label>
      <button class="btn" data-act="p3_check" data-in="p3-old,p3-new" data-out="p3-result">Cek persetujuan ulang</button></div>
    <div class="res" id="p3-result"><span class="hint">Masukkan nilai lama dan baru.</span></div></div>
</section>

<section class="panel" id="p4">
  <h1>Praktikum 4 — Modul Keuangan</h1>
  <p class="note">Saldo awal kas Rp30.000.000; lima transaksi skenario sudah dijurnal di database.</p>
  <div class="card"><h3>Tahap 1 — Chart of accounts</h3><div class="sc" id="p4-coa"></div></div>
  <div class="card"><h3>Tahap 2 — Input transaksi</h3>
    <div class="row"><label class="f">Jenis<select id="p4-type"><option value="buy">Pembelian bahan baku (kredit)</option><option value="payvendor">Pembayaran utang pemasok</option><option value="sell">Penjualan produk (kredit)</option><option value="recv">Penerimaan pembayaran pelanggan</option><option value="opex">Beban operasional (tunai)</option></select></label>
      <label class="f">Nominal (Rp)<input id="p4-amt" type="number" value="1000000"></label>
      <button class="btn" data-act="p4_add" data-in="p4-type,p4-amt">Tambah &amp; jurnal</button></div></div>
  <div class="card"><h3>Tahap 3 — Jurnal, buku besar, neraca saldo</h3>
    <div class="tabs"><button class="on" data-t="p4-journal">Jurnal</button><button data-t="p4-ledger">Buku Besar</button><button data-t="p4-trial">Neraca Saldo</button></div>
    <div class="tp on sc" id="p4-journal"></div><div class="tp sc" id="p4-ledger"></div><div class="tp sc" id="p4-trial"></div></div>
  <div class="card"><h3>Tahap 4 — Pengujian kesalahan</h3>
    <div class="row"><button class="btn danger" data-act="p4_dup" data-out="p4-errbox">Simulasikan duplikasi pembayaran</button><button class="btn" data-act="p4_fix" data-out="p4-errbox">Buat jurnal koreksi</button></div>
    <div class="res" id="p4-errbox"><span class="hint">Belum ada kesalahan disimulasikan.</span></div></div>
</section>

<section class="panel" id="p5">
  <h1>Praktikum 5 — SCM &amp; Pembelian Berbasis Kebutuhan Material</h1>
  <p class="note">Per unit produk: 3 Bahan A + 2 Bahan B. Target produksi 6 hari kerja. Harga asumsi: A Rp10.000, B Rp15.000.</p>
  <div class="card"><h3>Tahap 1 — Data master &amp; persediaan</h3><div class="sc" id="p5-master"></div></div>
  <div class="card"><h3>Tahap 2–3 — Kebutuhan bersih &amp; pembelian</h3>
    <div class="row"><label class="f">Rencana produksi (unit)<input id="p5-qty" type="number" value="300"></label><label class="f">Batas waktu (hari kerja)<input id="p5-deadline2" type="number" value="6"></label>
      <label class="f"><span><input type="checkbox" id="p5-usepo" checked style="min-width:0"> Pakai pesanan belum diterima</span></label>
      <button class="btn" data-act="p5_calc" data-in="p5-qty,p5-deadline2,p5-usepo" data-out="p5-result">Hitung kebutuhan bersih</button></div>
    <div class="res" id="p5-result"><span class="hint">Hasil perhitungan.</span></div></div>
  <div class="card"><h3>Dokumen pengadaan PR → PO → GR</h3>
    <div class="row"><button class="btn" data-act="p5_order" data-in="p5-qty,p5-deadline2,p5-usepo" data-out="p5-autoresult">Order semua kekurangan</button>
      <button class="btn" data-act="p5_recvall" data-in="p5-autojournal" data-out="p5-autoresult">Terima semua PO</button>
      <label class="f"><span><input type="checkbox" id="p5-autojournal" style="min-width:0"> Catat utang otomatis ke Keuangan (P4)</span></label></div>
    <div class="sc" style="margin-top:10px" id="p5-docs"></div><div class="res" id="p5-autoresult"><span class="hint">Klik “Order semua kekurangan”.</span></div></div>
  <div class="card"><h3>Tahap 4 — Penerimaan barang</h3>
    <div class="row"><label class="f">Komponen<select id="p5-recv-item"><option>A</option><option>B</option></select></label><label class="f">Jumlah diterima<input id="p5-recv-qty" type="number" value="100"></label>
      <button class="btn" data-act="p5_recv" data-in="p5-recv-item,p5-recv-qty,p5-autojournal" data-out="p5-recvresult">Catat penerimaan</button></div>
    <div class="res" id="p5-recvresult"><span class="hint">Penerimaan menambah stok dan mengurangi pesanan belum diterima.</span></div></div>
  <div class="card"><h3>Tahap 5 — Perubahan kondisi pemasok</h3>
    <div class="row"><label class="f">Waktu kirim Bahan B (hari)<input id="p5-leadtime" type="number" value="8"></label><label class="f">Pemasok alternatif (hari)<input id="p5-altlead" type="number" value="5"></label>
      <button class="btn" data-act="p5_risk" data-in="p5-leadtime,p5-deadline2" data-out="p5-riskresult">Analisis keputusan</button></div>
    <div class="res" id="p5-riskresult"><span class="hint">Rekomendasi berdasar waktu kirim vs tenggat.</span></div></div>
</section>
</main>
<script>
const $ = id => document.getElementById(id);
async function call(action, data = {}) {
  const f = new FormData(); f.append('action', action);
  for (const k in data) f.append(k, data[k]);
  try { return await (await fetch('api.php', {method: 'POST', body: f})).json(); }
  catch (e) { return {res: ['bad', 'Tidak dapat terhubung ke api.php / database.'], state: {}}; }
}
function apply(r, out) {
  if (r.res && out) { $(out).className = 'res show ' + r.res[0]; $(out).innerHTML = r.res[1]; }
  for (const k in r.state) if ($(k)) $(k).innerHTML = r.state[k];
}
document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const d = {};
  (b.dataset.in ? b.dataset.in.split(',') : []).forEach(i => d[i] = $(i).type === 'checkbox' ? ($(i).checked ? 1 : '') : $(i).value);
  if (b.dataset.extra) d.id = b.dataset.extra;
  apply(await call(b.dataset.act, d), b.dataset.out);
});
document.querySelectorAll('nav button').forEach(b => b.onclick = () => {
  document.querySelectorAll('nav button,.panel').forEach(x => x.classList.remove('on'));
  b.classList.add('on'); $(b.dataset.p).classList.add('on'); scrollTo(0, 0);
});
document.querySelectorAll('.tabs button').forEach(b => b.onclick = () => {
  document.querySelectorAll('.tabs button,.tp').forEach(x => x.classList.remove('on'));
  b.classList.add('on'); $(b.dataset.t).classList.add('on');
});
call('state').then(r => apply(r));
</script></body></html>
