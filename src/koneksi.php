<?php
$host = "db";       // Nama service di docker-compose
$user = "root";     // User default
$pass = "password"; // WAJIB DIISI "password" (sesuai MYSQL_ROOT_PASSWORD)
$db   = "my_db";    // Sesuaikan dengan nama database di phpMyAdmin

try {
    // Pastikan variabel $pass dimasukkan ke sini
    $koneksi = new mysqli($host, $user, $pass, $db); 
    
    if ($koneksi->connect_errno) {
        throw new Exception("Koneksi gagal: " . $koneksi->connect_error);
    }
} catch (Exception $e) {
    die("Waduh, koneksi gagal nih: " . $e->getMessage());
}