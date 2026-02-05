<?php
session_start();

require_once("/var/www/html/koneksi.php");

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION["login"])) {
    header("Location: ../index.php");
    exit;
}

/* ================= AUTO  ================= */
function generateNoRegistrasi($koneksi, $tipe = 'S'){
    $tahun = date("Y");

    $prefix = ($tipe == 'S') ? 'S-REG' : 'D-REG';

    // cari nomor terakhir tahun ini
    $q = mysqli_query($koneksi,"
        SELECT no_registrasi
        FROM ".($tipe=='S'?'pengirim':'penerima')."
        WHERE no_registrasi LIKE '$prefix-$tahun-%'
        ORDER BY id_".($tipe=='S'?'pengirim':'penerima')." DESC
        LIMIT 1
    ");

    if(mysqli_num_rows($q)>0){
        $d = mysqli_fetch_assoc($q);
        $last = (int) substr($d['no_registrasi'], -4);
        $next = $last + 1;
    } else {
        $next = 1;
    }

    return $prefix."-".$tahun."-".str_pad($next,4,'0',STR_PAD_LEFT);
}

/* ================= SIMPAN PENGIRIM ================= */
if (isset($_POST['simpan_pengirim'])) {
    $nama   = mysqli_real_escape_string($koneksi,$_POST['nama_pengirim']);
    $hp     = mysqli_real_escape_string($koneksi,$_POST['no_hp_pengirim']);
    $alamat = mysqli_real_escape_string($koneksi,$_POST['alamat_pengirim']);

    $cek = mysqli_query($koneksi,"
        SELECT 1 FROM pengirim 
        WHERE nama_pengirim='$nama' AND no_hp_pengirim='$hp'
    ");

    if(mysqli_num_rows($cek)==0){
        $today = date("Ymd");
        $q = mysqli_query($koneksi,"
            SELECT COUNT(*) jml FROM pengirim WHERE DATE(tgl_daftar)=CURDATE()
        ");
        $n = mysqli_fetch_assoc($q)['jml'] + 1;

        $no_reg = generateNoRegistrasi($koneksi, 'S');

mysqli_query($koneksi,"
INSERT INTO pengirim
(no_registrasi,nama_pengirim,no_hp_pengirim,alamat_pengirim,tgl_daftar)
VALUES
('$no_reg','$nama','$hp','$alamat',NOW())
");

        header("Location: pengirim_penerima.php?msg=pengirim_ok");
        exit;
    } else {
        header("Location: pengirim_penerima.php?msg=pengirim_duplikat");
        exit;
    }
}

/* ================= SIMPAN PENERIMA ================= */
if (isset($_POST['simpan_penerima'])) {
    $nama   = mysqli_real_escape_string($koneksi,$_POST['nama_penerima']);
    $hp     = mysqli_real_escape_string($koneksi,$_POST['no_hp_penerima']);
    $alamat = mysqli_real_escape_string($koneksi,$_POST['alamat_penerima']);

    $cek = mysqli_query($koneksi,"
        SELECT 1 FROM penerima 
        WHERE nama_penerima='$nama' AND no_hp_penerima='$hp'
    ");

    if(mysqli_num_rows($cek)==0){
        $today = date("Ymd");
        $q = mysqli_query($koneksi,"
            SELECT COUNT(*) jml FROM penerima WHERE DATE(tgl_daftar)=CURDATE()
        ");
        $n = mysqli_fetch_assoc($q)['jml'] + 1;

        $no_reg = generateNoRegistrasi($koneksi, 'D');

mysqli_query($koneksi,"
INSERT INTO penerima
(no_registrasi,nama_penerima,no_hp_penerima,alamat_penerima,tgl_daftar)
VALUES
('$no_reg','$nama','$hp','$alamat',NOW())
");

        header("Location: pengirim_penerima.php?msg=penerima_ok");
        exit;
    } else {
        header("Location: pengirim_penerima.php?msg=penerima_duplikat");
        exit;
    }
}

if (isset($_POST['update_data'])) {
    $id     = mysqli_real_escape_string($koneksi,$_POST['id']);
    $jenis  = mysqli_real_escape_string($koneksi,$_POST['jenis']);
    $nama   = mysqli_real_escape_string($koneksi,$_POST['nama']);
    $no_hp  = mysqli_real_escape_string($koneksi,$_POST['no_hp']);
    $alamat = mysqli_real_escape_string($koneksi,$_POST['alamat']);

    if ($jenis == 'Pengirim') {
        mysqli_query($koneksi, "
            UPDATE pengirim 
            SET nama_pengirim='$nama',
                no_hp_pengirim='$no_hp',
                alamat_pengirim='$alamat'
            WHERE id_pengirim='$id'
        ");
    } else {
        mysqli_query($koneksi, "
            UPDATE penerima 
            SET nama_penerima='$nama',
                no_hp_penerima='$no_hp',
                alamat_penerima='$alamat'
            WHERE id_penerima='$id'
        ");
    }

    header("Location: pengirim_penerima.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id    = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    $jenis = mysqli_real_escape_string($koneksi, $_GET['jenis']);

    if ($jenis == 'Pengirim') {
        mysqli_query($koneksi, "DELETE FROM pengirim WHERE id_pengirim='$id'");
    } else {
        mysqli_query($koneksi, "DELETE FROM penerima WHERE id_penerima='$id'");
    }

    header("Location: pengirim_penerima.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<meta charset="UTF-8">
<title>Registrasi Pengirim & Penerima</title>

<style>
body{
    font-family:'Segoe UI',Tahoma,Verdana;
    background:linear-gradient(135deg,#00bcd4,#3f51b5);
    margin:0;
    padding:20px;
}
.header{
    position:fixed;
    top:0;left:0;width:100%;
    background:#fff;
    padding:10px 30px;
    text-align:center;
    z-index:999;
}
.header img{height:80px;}
.header h2{margin:0;font-size:22px; font-weight:bold; color:black}
#waktu{font-weight:bold;margin-top:5px}

.container{
    display:flex;
    gap:20px;
    margin-top:155px;
    max-width:1500px;
    margin-left:auto;
    margin-right:auto;
}
.left{
    flex:0 0 280px;
    background:rgba(255,255,255,.95);
    padding:20px;
    border-radius:15px;
}
.right{
    flex:1;
    background:rgba(255,255,255,.95);
    padding:20px;
    border-radius:15px;
    max-height:900px;
    overflow-y:auto;
}

input, textarea, button{
    width:100%;
    padding:10px;
    margin-bottom:10px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:14px;
}
button{
    background:#0275d8;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}
th,td{
    border:1px solid #ccc;
    padding:8px;
}
th{
    background:#f2f2f2;
    text-align:center;
}

.alert{
    max-width:1500px;
    margin:10px auto;
    padding:10px;
    border-radius:10px;
    text-align:center;
    font-weight:bold;
}
.ok{background:#d4edda;color:#155724;}
.err{background:#f8d7da;color:#721c24;}

h3 {
    font-size: 18px;
}

</style>
</head>

<body>

<div class="header">
    <img src="../image/elang.png">
    <h2>REGISTRASI DATA PENGIRIM & PENERIMA</h2>
    <div id="waktu"></div>
    <div style="position:fixed;top:15px;right:20px">
        <a href="../menu.php"
           style="background:#0275d8;color:#fff;
           padding:8px 15px;border-radius:20px;
           text-decoration:none;font-weight:bold">
           Kembali ke Menu
        </a>
    </div>
</div>

<?php
if(isset($_GET['msg'])){
    if($_GET['msg']=='pengirim_ok') echo "<div class='alert ok'>Pengirim berhasil disimpan</div>";
    if($_GET['msg']=='penerima_ok') echo "<div class='alert ok'>Penerima berhasil disimpan</div>";
    if($_GET['msg']=='pengirim_duplikat') echo "<div class='alert err'>Pengirim sudah terdaftar</div>";
    if($_GET['msg']=='penerima_duplikat') echo "<div class='alert err'>Penerima sudah terdaftar</div>";
}
?>

<div class="container">

<div class="left">
<h3>Registrasi Pengirim</h3>
<form method="post">
<input name="nama_pengirim" placeholder="Nama Pengirim" required>
<textarea name="alamat_pengirim" placeholder="Alamat Pengirim"></textarea>
<input name="no_hp_pengirim" placeholder="No HP Pengirim">
<button name="simpan_pengirim">Simpan Pengirim</button>
</form>

<hr>

<h3>Registrasi Penerima</h3>
<form method="post">
<input name="nama_penerima" placeholder="Nama Penerima" required>
<textarea name="alamat_penerima" placeholder="Alamat Penerima"></textarea>
<input name="no_hp_penerima" placeholder="No HP Penerima">
<button name="simpan_penerima">Simpan Penerima</button>
</form>
</div>

<div class="right">
<h3>Data Pengirim & Penerima</h3>
<table>
<thead>
<tr>
<th style="width:5%;">Jenis</th>
<th style="width:15%;">No Registrasi</th>
<th style="width:15%;">Nama</th>
<th style="width:20%;">Alamat</th>
<th style="width:15%;">No HP</th>
<th style="width:15%;">Tgl Daftar</th>
<th style="width:5%;">Opsi</th>
</tr>
</thead>

<tbody>
<?php
$q = mysqli_query($koneksi,"
SELECT 
    'Pengirim' AS jenis,
    id_pengirim AS id,
    no_registrasi,
    nama_pengirim AS nama,
    no_hp_pengirim AS hp,
    alamat_pengirim AS alamat,
    tgl_daftar
FROM pengirim

UNION ALL

SELECT 
    'Penerima' AS jenis,
    id_penerima AS id,
    no_registrasi,
    nama_penerima AS nama,
    no_hp_penerima AS hp,
    alamat_penerima AS alamat,
    tgl_daftar
FROM penerima

ORDER BY tgl_daftar DESC
");

while ($d = mysqli_fetch_assoc($q)) {
?>
<tr>
    <td><?= $d['jenis'] ?></td>
    <td><?= $d['no_registrasi'] ?></td>
    <td><?= $d['nama'] ?></td>
    <td><?= $d['alamat'] ?></td>
    <td><?= $d['hp'] ?></td>
    <td><?= $d['tgl_daftar'] ?></td>
    <td style="text-align:center">
        <a href="javascript:void(0)"
           onclick="editData(
               '<?= $d['id'] ?>',
               '<?= $d['jenis'] ?>',
               '<?= htmlspecialchars($d['nama']) ?>',
               '<?= htmlspecialchars($d['hp']) ?>',
               '<?= htmlspecialchars($d['alamat']) ?>'
           )"
           style="color:#0275d8;font-weight:bold">
           Edit
        </a>
        |
        <a href="javascript:void(0)"
           onclick="hapusData('<?= $d['id'] ?>','<?= $d['jenis'] ?>')"
           style="color:red;font-weight:bold">
           Hapus
        </a>
    </td>
</tr>
<?php } ?>
</tbody>

</table>

</div>

</div>

<script>
function updateWaktu(){
 const h=["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
 const b=["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
 let n=new Date();
 document.getElementById("waktu").innerHTML=
 h[n.getDay()]+", "+n.getDate()+" "+b[n.getMonth()]+" "+n.getFullYear()+
 " | "+n.toLocaleTimeString();
}
setInterval(updateWaktu,1000);updateWaktu();
</script>

<script>
function editData(id, jenis, nama, no_hp, alamat) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_jenis').value = jenis;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_no_hp').value = no_hp;
    document.getElementById('edit_alamat').value = alamat;

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function hapusData(id, jenis) {
    if (confirm('Yakin ingin menghapus data ' + jenis + ' ini?')) {
        window.location.href = 'pengirim_penerima.php?hapus=' + id + '&jenis=' + jenis;
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form method="post">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <input type="hidden" name="jenis" id="edit_jenis">

          <div class="mb-2">
            <label>Nama</label>
            <input type="text" name="nama" id="edit_nama" class="form-control" required>
          </div>

          <div class="mb-2">
            <label>No HP</label>
            <input type="text" name="no_hp" id="edit_no_hp" class="form-control">
          </div>

          <div class="mb-2">
            <label>Alamat</label>
            <textarea name="alamat" id="edit_alamat" class="form-control"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="update_data" class="btn btn-primary">
            Simpan Perubahan
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

</body>
</html>
