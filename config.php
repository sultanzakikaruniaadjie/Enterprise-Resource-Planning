<?php
// Sesuaikan dengan hosting Anda
$DB_HOST = 'localhost';
$DB_NAME = 'erp_sim';
$DB_USER = 'root';
$DB_PASS = '';

$db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
