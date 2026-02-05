<?php
session_start();
include '../koneksi.php'; // koneksi database

// --- CEK LOGIN ---
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true) {
    echo "<script>alert('Akses ditolak! Silakan login dulu.'); window.location='selamat_datang.php';</script>";
    exit;
}

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// --- CEK NO RESI DARI URL ---
if (!isset($_GET['no_resi'])) {
    echo "<script>alert('No Resi tidak ditemukan'); window.location='penerimaan_barang.php';</script>";
    exit;
}

$no_resi = $_GET['no_resi'];

// --- CEK APAKAH RESI ADA ---
$q = mysqli_query($koneksi, "SELECT id_penerimaan FROM penerimaan WHERE no_resi='$no_resi'");
if (mysqli_num_rows($q) == 0) {
    echo "<script>alert('No Resi tidak ditemukan!'); window.location='penerimaan_barang.php';</script>";
    exit;
}

$d = mysqli_fetch_assoc($q);
$id_penerimaan = $d['id_penerimaan'];

// --- HAPUS PENEMPATAN BARANG TERKAIT ---
mysqli_query($koneksi, "DELETE FROM penempatan_barang WHERE id_penerimaan='$id_penerimaan'");

// --- HAPUS TRACKING TERKAIT ---
mysqli_query($koneksi, "DELETE FROM tracking_status WHERE id_penerimaan='$id_penerimaan'");

// --- HAPUS DATA PENERIMAAN ---
mysqli_query($koneksi, "DELETE FROM penerimaan WHERE id_penerimaan='$id_penerimaan'");

// --- NOTIFIKASI ---
echo "<script>
        alert('Resi $no_resi dan seluruh data terkait berhasil dihapus.');
        window.location='penerimaan_barang.php';
      </script>";
?>
