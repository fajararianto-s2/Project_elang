<?php
session_start();
$_SESSION['last_activity'] = time();

include '../koneksi.php';

// ========================
// CEK LOGIN & ANTI SESSION HIJACK
// ========================
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

// ========================
// AUTO LOGOUT SERVER-SIDE (1 MENIT)
// ========================
$max_idle = 600000; // 1 menit
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $max_idle) {
        session_unset();
        session_destroy();
        header("Location: ../index.php?timeout=1");
        exit;
    }
}
$_SESSION['last_activity'] = time(); // update timestamp


// ========================
// HANDLE: update lokasi via dropdown per-row (POST)
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_lokasi') {
    $id_penempatan = isset($_POST['id_penempatan']) ? intval($_POST['id_penempatan']) : 0;
    $lokasi_baru   = isset($_POST['lokasi_baru']) ? intval($_POST['lokasi_baru']) : 0;

    if ($id_penempatan > 0 && $lokasi_baru > 0) {
        $stmt = $koneksi->prepare("UPDATE penempatan_barang SET id_lokasi = ? WHERE id_penempatan = ?");
        $stmt->bind_param("ii", $lokasi_baru, $id_penempatan);
        if ($stmt->execute()) {
            echo "<script>window.location='penempatan_barang.php';</script>";
            exit;
        } else {
            $err = $stmt->error;
            echo "<script>alert('Gagal update lokasi: ". addslashes($err) ."'); window.location='penempatan_barang.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Input tidak valid'); window.location='penempatan_barang.php';</script>";
        exit;
    }

    // update zona di tabel lokasi_penyimpanan (berdasarkan id_lokasi)
    $upd = $koneksi->prepare("
        UPDATE lokasi_penyimpanan l
        JOIN penempatan_barang p ON l.id_lokasi = p.id_lokasi
        SET l.zona = ?
        WHERE p.id_penempatan = ?
    ");
    $upd->bind_param("si", $zona_baru, $id_penempatan);
    $upd->execute();
    $upd->close();

    echo "<script>window.location='penempatan_barang.php';</script>";
    exit;
}
// HANDLE EDIT LOKASI & ZONA DARI POPUP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_lokasi_zona') {
    $id_lokasi = intval($_POST['id_lokasi']);
    $zona_baru = mysqli_real_escape_string($koneksi, $_POST['zona_baru']);

    $upd = $koneksi->prepare("UPDATE lokasi_penyimpanan SET zona = ? WHERE id_lokasi = ?");
    $upd->bind_param("si", $zona_baru, $id_lokasi);
    $upd->execute();
    $upd->close();

    echo "<script>window.location='penempatan_barang.php';</script>";
    exit;
}

// ========================
// PROSES SIMPAN LOKASI
// ========================
if (isset($_POST['simpan_lokasi'])) {
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_lokasi']);
    $zona = mysqli_real_escape_string($koneksi, $_POST['zona']);

    $ins = $koneksi->prepare("INSERT INTO lokasi_penyimpanan (kode_lokasi, zona) VALUES (?, ?)");
    $ins->bind_param("ss", $kode, $zona);
    $ins->execute();
    $ins->close();

    echo "<script>alert('Lokasi berhasil disimpan!'); window.location='penempatan_barang.php';</script>";
    exit;
}

// ========================
// PROSES SIMPAN PENEMPATAN BARANG
// ========================
if (isset($_POST['simpan_penempatan'])) {
    $id_penerimaan = intval($_POST['id_penerimaan']);
    $id_lokasi     = intval($_POST['id_lokasi']);

    // CEK: apakah resi sudah pernah ditempatkan?
    $cek = $koneksi->prepare("SELECT COUNT(*) FROM penempatan_barang WHERE id_penerimaan = ?");
    $cek->bind_param("i", $id_penerimaan);
    $cek->execute();
    $cek->bind_result($jumlah);
    $cek->fetch();
    $cek->close();

    if ($jumlah > 0) {
        echo "<script>alert('No resi ini sudah pernah ditempatkan!'); window.location='penempatan_barang.php';</script>";
        exit;
    }

    $sql = "INSERT INTO penempatan_barang
            (id_penerimaan, id_lokasi, waktu_masuk, waktu_keluar, status_penempatan)
            VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'MASUK')";

    $ins2 = $koneksi->prepare($sql);
    $ins2->bind_param("ii", $id_penerimaan, $id_lokasi);
    $ins2->execute();
    $ins2->close();

    echo "<script>alert('Penempatan barang berhasil dicatat!'); window.location='penempatan_barang.php';</script>";
    exit;
}

// ambil data lokasi sekali (untuk dropdown)
$lokasi_list = [];
$resLok = mysqli_query($koneksi, "SELECT id_lokasi, kode_lokasi, zona FROM lokasi_penyimpanan ORDER BY kode_lokasi ASC");
while ($rk = mysqli_fetch_assoc($resLok)) $lokasi_list[] = $rk;

// AUTO UPDATE STATUS
$koneksi->query("UPDATE penempatan_barang SET status_penempatan='CEK' WHERE waktu_keluar<NOW() AND status_penempatan='MASUK'");

// ambil data penempatan + join penerimaan + lokasi
$res = mysqli_query($koneksi,
    "SELECT p.id_penempatan, p.id_penerimaan, p.id_lokasi,
            DATE_FORMAT(p.waktu_masuk, '%Y-%m-%d %H:%i') AS waktu_masuk,
            DATE_FORMAT(p.waktu_keluar, '%Y-%m-%d %H:%i') AS waktu_keluar,
            p.status_penempatan, r.no_resi, l.kode_lokasi, l.zona
     FROM penempatan_barang p
     JOIN penerimaan r ON p.id_penerimaan = r.id_penerimaan
     JOIN lokasi_penyimpanan l ON p.id_lokasi = l.id_lokasi
     ORDER BY p.waktu_masuk DESC");

// CEK ADA RESI YANG BELUM DITEMPATKAN
    $cekBelum = mysqli_query($koneksi, "
    SELECT no_resi 
    FROM penerimaan 
    WHERE id_penerimaan NOT IN (SELECT id_penerimaan FROM penempatan_barang)
    ");
    $jumlahBelum = mysqli_num_rows($cekBelum);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- BOOTSTRAP 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- BOOTSTRAP 5 JS + POPPER -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<meta charset="UTF-8">
<title>Penempatan & Lokasi Penyimpanan</title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #00bcd4, #3f51b5); margin: 0; padding: 20px; }
    .header { position: fixed; top: 0; left: 0; width: 100%; background-color: white; padding: 10px 30px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; }
    .header img{height:80px;}
    .header-text{flex-grow:1;text-align:center;}
    .header-text h2{margin:0;font-weight:bold;color:black;font-size:22px;}
    .header-text h3{margin:0;font-weight:bold;color:black;font-size:12px;}
    .header-text p{font-size:28px;font-weight:bold;margin:0;color:black;}
    .container{margin-top:150px;display:flex;gap:20px;max-width:1500px;margin-left:auto;margin-right:auto;}
    .left { flex: 0 0 300px; padding: 20px; background: rgba(255,255,255,0.9); border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .right { flex: 1; padding: 20px; background: rgba(255,255,255,0.9); border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-height: 800px; overflow-y: auto; }
    input, select{width:95%;padding:10px;margin-bottom:10px;border-radius:10px;border:1px solid #ccc;}
    button{padding:10px 20px;background:#0275d8;color:white;border:none;border-radius:10px;cursor:pointer;}
    button:hover{background:#025aa5;}
    table{width:100%;border-collapse:collapse;font-size:13px;}
    th, td{border:1px solid #ccc;padding:8px;vertical-align:middle;}
    th{background:#eee;text-align:center;}
    footer{margin-top:30px;text-align:center;color:white;}
    .select-inline{width:100%;padding:6px;border-radius:6px;}
    .form-inline{margin:0;}
    /* Modal lebih kecil */
    .modal-dialog {max-width: 500px !important;}
    /* Tabel rapet */
    .table-modal {width: 100%;border-collapse: collapse;font-size: 13px;}
    .table-modal th,
    .table-modal td {padding: 6px 8px;     /* jarak antar kolom rapet */text-align: left;border-bottom: 1px solid #ddd;}
    .table-modal th {background: #f2f2f2;font-weight: bold;}
    .table-modal tr:hover {background-color: #f9f9f9;}
    /* kolom dibagi rata */
    .table-modal th:nth-child(1),
    .table-modal td:nth-child(1) { width: 30%; }
    .table-modal th:nth-child(2),
    .table-modal td:nth-child(2) { width: 30%; }
    .table-modal th:nth-child(3),
    .table-modal td:nth-child(3) { width: 20%; }
    .table-modal th:nth-child(4),
    .table-modal td:nth-child(4) { width: 20%; }
    #modalLokasiZona table th,
    #modalLokasiZona table td {width: 33.33%;vertical-align: middle;}
    h3 {font-size: 18px;}

</style>
</head>
<body>

<div class="header">
    <div class="header-text">
        <p><img src="../image/elang.png" alt="Logo E-Lang"></p>
        <h2>PENEMPATAN BARANG</h2>
        <div id="waktu" style="top:15px; left:180px; font-weight:bold; font-size:16px; color:black;"></div>
    </div>
    <div style="position: fixed; top:10px; right: 15px;">
        <a href="../menu.php" style="color:white; background:#0275d8; padding:8px 15px; border-radius:20px; font-weight:bold;">Kembali ke Menu</a>
    </div>
</div>

<div class="container">

    <div class="left">
        <h3>Input Lokasi Penyimpanan</h3>
        <form method="POST">
            <input type="text" name="kode_lokasi" placeholder="Kode Lokasi (ex: RAK-A1)" required>
            <input type="text" name="zona" placeholder="Zona (ex: Zona Utara)" required>
            <button type="submit" name="simpan_lokasi">Simpan Lokasi</button>
        </form>

        <hr><br>

        <h3>Input Penempatan Barang</h3>
        <?php if ($jumlahBelum > 0): ?>
            <p style="color:red; font-weight:bold; font-size:14px; margin-top:-10px;">
                ⚠ Ada <?= $jumlahBelum ?> resi yang belum dilokasikan!
            </p>
        <?php endif; ?>

        <form method="POST">
            <select name="id_penerimaan" required>
                <option value="">-- Pilih No Resi --</option>
                <?php
                $q = mysqli_query($koneksi, "SELECT id_penerimaan,no_resi FROM penerimaan WHERE id_penerimaan NOT IN (SELECT id_penerimaan FROM penempatan_barang) ORDER BY tgl_diterima DESC");
                while($d=mysqli_fetch_assoc($q)){
                    echo "<option value='".intval($d['id_penerimaan'])."'>".htmlspecialchars($d['no_resi'])."</option>";
                }
                ?>
            </select>

            <select name="id_lokasi" required>
                <option value="">-- Pilih Lokasi --</option>
                <?php foreach ($lokasi_list as $lk){
                    echo "<option value='".intval($lk['id_lokasi'])."'>".htmlspecialchars($lk['kode_lokasi'])." - ".htmlspecialchars($lk['zona'])."</option>";
                } ?>
            </select>

            <button type="submit" name="simpan_penempatan">Simpan Penempatan</button>
        </form>
    </div>

    <div class="right">
        <h3>Rekap Penempatan Barang
            <!-- Tombol Modal -->
            <button type="button" class="btn btn-primary" 
            data-bs-toggle="modal" data-bs-target="#modalLokasiZona">
            Edit Lokasi & Zona
            </button>
        </h3>
        <table>
            <thead>
                <tr>
                    <th>No Resi</th><th>Lokasi</th><th>Zona</th><th>Waktu Masuk</th><th>Waktu Keluar</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
<?php while ($row = mysqli_fetch_assoc($res)) : 
    $masuk  = $row['waktu_masuk'] ?? "";
    $keluar = $row['waktu_keluar'] ?? "";
    $zero   = "0000-00-00 00:00:00";
    $formatMasuk  = ($masuk && $masuk!==$zero) ? date("Y-m-d H:i", strtotime($masuk)) : "-";
    $formatKeluar = ($keluar && $keluar!==$zero) ? date("Y-m-d H:i", strtotime($keluar)) : (($masuk && $masuk!==$zero)? date("Y-m-d H:i", strtotime("$masuk +3 days")):"-");
?>
<tr>
    <td style="text-align:center;"><?= htmlspecialchars($row['no_resi']) ?></td>
    <td>
        <form class="form-inline" method="POST">
            <input type="hidden" name="action" value="update_lokasi">
            <input type="hidden" name="id_penempatan" value="<?= intval($row['id_penempatan']) ?>">
            <select name="lokasi_baru" class="select-inline" onchange="this.form.submit()">
                <?php foreach ($lokasi_list as $lk): 
                    $sel = ($lk['id_lokasi']==$row['id_lokasi'])?'selected':''; ?>
                <option value="<?= intval($lk['id_lokasi']) ?>" <?= $sel ?>><?= htmlspecialchars($lk['kode_lokasi']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </td>
    <td style="text-align:center;"><?= htmlspecialchars($row['zona']) ?></td>
    <td style="text-align:center;"><?= $formatMasuk ?></td>
    <td style="text-align:center;"><?= $formatKeluar ?></td>
    <td style="text-align:center;"><?= htmlspecialchars($row['status_penempatan']) ?></td>
</tr>
<?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

<footer>
<img src="/ui/image/lunla.png" alt="Logo" style="width:30px; vertical-align:middle; margin-right:2px;">
&copy; <?php echo date("Y"); ?> full support by langlangbuana university
</footer>

    <!-- Waktu -->
    <script>
    function updateWaktu() {
        const hari = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
        const bulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        let now = new Date();
        let namaHari = hari[now.getDay()];
        let tanggal = now.getDate();
        let namaBulan = bulan[now.getMonth()];
        let tahun = now.getFullYear();
        let jam = now.getHours().toString().padStart(2,'0');
        let menit = now.getMinutes().toString().padStart(2,'0');
        let detik = now.getSeconds().toString().padStart(2,'0');
        let format = `${namaHari}, ${tanggal} ${namaBulan} ${tahun} | ${jam}:${menit}:${detik}`;
        document.getElementById("waktu").innerHTML = format;
    }
    setInterval(updateWaktu, 1000);
    updateWaktu();
    </script>

    <!-- Idle logout -->
    <script>
    let idleLimit = 600000; // 1 menit
    let idleTimer;

    function resetTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(autoLogout, idleLimit);
    }

    function autoLogout() {
        alert("Session berakhir karena tidak ada aktivitas.");
        window.location.href = "/ui/logout.php";
    }

    // event user activity
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeypress = resetTimer;
    document.onscroll = resetTimer;
    document.onclick = resetTimer;

    // Cek flag timeout dari PHP (session timeout sebelumnya)
    <?php if(isset($_GET['timeout']) && $_GET['timeout']==1): ?>
        alert("Session berakhir! Silakan login ulang.");
    <?php endif; ?>
    </script>

    <script>
    setInterval(() => {
        fetch("heartbeat.php"); // panggil server tiap 30 detik ngasih tau klo halaman lagi ada aktifitas
    }, 30000);
    </script>

    <div class="modal fade" id="modalLokasiZona" tabindex="-1">
    <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Edit Kode Lokasi & Zona</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Kode Lokasi</th>
              <th>Zona</th>
              <th style="width: 50px; text-align:center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lokasi_list as $lok) { ?>
            <tr>
              <td><?= htmlspecialchars($lok['kode_lokasi']) ?></td>
              <td><?= htmlspecialchars($lok['zona']) ?></td>
              <td style="text-align:center;">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_lokasi_zona">
                    <input type="hidden" name="id_lokasi" value="<?= $lok['id_lokasi'] ?>">

                    <input type="text" name="zona_baru" 
                           value="<?= htmlspecialchars($lok['zona']) ?>" 
                           class="form-control form-control-sm mb-1">

                    <button type="submit" class="btn btn-success btn-sm" style="width:100%;">
                        Save
                    </button>
                </form>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
     </div>
    </div>

</body>
</html>
