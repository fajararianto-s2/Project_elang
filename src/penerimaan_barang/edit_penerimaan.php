<?php
session_start();

// --- KONEKSI DATABASE ---
include '../koneksi.php';
if (!$koneksi) die("Koneksi gagal: " . mysqli_connect_error());

// --- CEK PARAMETER NO RESI ---
if (!isset($_GET['no_resi'])) {
    echo "<script>alert('No Resi tidak ditemukan'); window.close();</script>";
    exit;
}
$no_resi = $_GET['no_resi'];

// --- Ambil data penerimaan ---
$query = "SELECT p.*, pg.nama_pengirim, pg.alamat_pengirim, pg.no_hp_pengirim,
                 pr.nama_penerima, pr.alamat_penerima, pr.no_hp_penerima
          FROM penerimaan p
          JOIN pengirim pg ON p.id_pengirim = pg.id_pengirim
          JOIN penerima pr ON p.id_penerima = pr.id_penerima
          WHERE p.no_resi = '$no_resi'";
$result = mysqli_query($koneksi, $query);
if (!$result) die("Query gagal: " . mysqli_error($koneksi));
if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('No Resi tidak ditemukan'); window.close();</script>";
    exit;
}
$data = mysqli_fetch_assoc($result);

// --- Proses Update ---
if (isset($_POST['update'])) {
    $nama_pengirim   = $_POST['nama_pengirim'];
    $alamat_pengirim = $_POST['alamat_pengirim'];
    $hp_pengirim     = $_POST['hp_pengirim'];
    $nama_penerima   = $_POST['nama_penerima'];
    $alamat_penerima = $_POST['alamat_penerima'];
    $hp_penerima     = $_POST['hp_penerima'];
    $berat           = $_POST['berat'];
    $nilai_barang    = $_POST['nilai_barang'];

    mysqli_query($koneksi, "UPDATE pengirim 
        SET nama_pengirim='$nama_pengirim', alamat_pengirim='$alamat_pengirim', no_hp_pengirim='$hp_pengirim'
        WHERE id_pengirim={$data['id_pengirim']}");
    mysqli_query($koneksi, "UPDATE penerima 
        SET nama_penerima='$nama_penerima', alamat_penerima='$alamat_penerima', no_hp_penerima='$hp_penerima'
        WHERE id_penerima={$data['id_penerima']}");
    mysqli_query($koneksi, "UPDATE penerimaan
        SET berat='$berat', nilai_barang='$nilai_barang'
        WHERE no_resi='$no_resi'");

    echo "<script>
        alert('Data Penerimaan Berhasil Diperbarui!');
        window.opener.location.reload(); 
        window.close();
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Penerimaan Barang</title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#e0f7fa; padding:20px; }
.form-container { max-width:400px; margin:auto; 
    background: linear-gradient(135deg, #2196f3, #64b5f6); padding:20px; border-radius:15px; 
    box-shadow:0 4px 10px rgba(0,0,0,0.2); color:white; }
input, textarea { width:95%; padding:10px; margin-bottom:12px; border-radius:10px; border:1px solid #ccc; }
button { padding:10px 20px; background:#ff9800; color:white; border:none; border-radius:10px; cursor:pointer; }
button:hover { background:#fb8c00; }
h3,h4 { margin:0 0 10px 0; }
</style>
</head>
<body>

<div class="form-container">
    <h3>Edit Penerimaan Barang</h3>
    <form method="POST">
        <h4>Data Pengirim</h4>
        <input type="text" name="nama_pengirim" value="<?= htmlspecialchars($data['nama_pengirim']) ?>" required>
        <textarea name="alamat_pengirim" required><?= htmlspecialchars($data['alamat_pengirim']) ?></textarea>
        <input type="text" name="hp_pengirim" value="<?= htmlspecialchars($data['no_hp_pengirim']) ?>" required>

        <h4>Data Penerima</h4>
        <input type="text" name="nama_penerima" value="<?= htmlspecialchars($data['nama_penerima']) ?>" required>
        <textarea name="alamat_penerima" required><?= htmlspecialchars($data['alamat_penerima']) ?></textarea>
        <input type="text" name="hp_penerima" value="<?= htmlspecialchars($data['no_hp_penerima']) ?>" required>

        <h4>Detail Barang</h4>
        <input type="number" step="0.01" name="berat" value="<?= $data['berat'] ?>" required>
        <input type="number" step="0.01" name="nilai_barang" value="<?= $data['nilai_barang'] ?>" required>

        <button type="submit" name="update">Update Data</button>
    </form>
</div>

<script>
// ========================
// POPUP SESSION HANDLING
// ========================

// ambil referensi parent
let parentRef = window.opener;

// cek tiap 2 detik apakah parent sudah logout
let checkParentInterval = setInterval(() => {
    if (!parentRef || parentRef.sessionTimeout) {
        alert("Parent sudah logout, popup akan ditutup.");
        window.close();
    }
}, 2000);

// jika parent aktif, kirim sinyal aktivitas ke parent
function resetParentTimer() {
    if (parentRef && !parentRef.sessionTimeout && typeof parentRef.resetTimer === "function") {
        parentRef.resetTimer(); // reset timer parent
    }
}

// event dianggap aktivitas user
window.onload = resetParentTimer;
document.onmousemove = resetParentTimer;
document.onkeypress = resetParentTimer;
document.onclick = resetParentTimer;
document.onscroll = resetParentTimer;
</script>

</body>
</html>
