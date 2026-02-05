<?php
session_start();
$_SESSION['last_activity'] = time();

// --- KONEKSI DATABASE ---
include '../koneksi.php';
if (!$koneksi) die("Koneksi gagal: " . mysqli_connect_error());

/* ================= AJAX AUTOCOMPLETE PENGIRIM ================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'pengirim') {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['q'] ?? '');

    $sql = mysqli_query($koneksi, "
        SELECT id_pengirim, nama_pengirim, no_hp_pengirim, alamat_pengirim
        FROM pengirim
        WHERE nama_pengirim LIKE '%$keyword%'
        ORDER BY nama_pengirim ASC
        LIMIT 10
    ");

    $data = [];
    while ($row = mysqli_fetch_assoc($sql)) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/* ================= AJAX AUTOCOMPLETE PENERIMA ================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'penerima') {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['q'] ?? '');

    $sql = mysqli_query($koneksi, "
        SELECT id_penerima, nama_penerima, no_hp_penerima, alamat_penerima
        FROM penerima
        WHERE nama_penerima LIKE '%$keyword%'
        ORDER BY nama_penerima ASC
        LIMIT 10
    ");

    $data = [];
    while ($row = mysqli_fetch_assoc($sql)) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ========================
// CEK LOGIN & ANTI SESSION HIJACK
// ========================
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: /ui/selamat_datang.php");
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
        header("Location: /ui/selamat_datang.php?timeout=1");
        exit;
    }
}
$_SESSION['last_activity'] = time(); // update timestamp

// --- Generate Nomor Resi ---
function generateResi() {
    $prefix = "RESI" . date("Ymd");
    global $koneksi;
    $query = "SELECT no_resi FROM penerimaan WHERE no_resi LIKE '$prefix%' ORDER BY no_resi DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    $last = mysqli_fetch_assoc($result);
    if ($last) {
        $lastNumber = substr($last["no_resi"], -4);
        $newNumber = str_pad($lastNumber + 1, 4, "0", STR_PAD_LEFT);
    } else {
        $newNumber = "0001";
    }
    return $prefix . $newNumber;
}

// --- Proses Simpan ---
    if (isset($_POST['simpan'])) {
        if (!empty($_POST['id_pengirim'])) {
        // pakai pengirim existing
        $id_pengirim = $_POST['id_pengirim'];
    } else {
        // pengirim baru
        $nama_pengirim   = $_POST['nama_pengirim'];
        $alamat_pengirim = $_POST['alamat_pengirim'];
        $hp_pengirim     = $_POST['hp_pengirim'];

        mysqli_query($koneksi, "
            INSERT INTO pengirim(nama_pengirim, alamat_pengirim, no_hp_pengirim)
            VALUES ('$nama_pengirim', '$alamat_pengirim', '$hp_pengirim')
        ");

        $id_pengirim = mysqli_insert_id($koneksi);
    }

    if (!empty($_POST['id_penerima'])) {
    // penerima existing
    $id_penerima = $_POST['id_penerima'];
} else {
    // penerima baru
    $nama_penerima   = $_POST['nama_penerima'];
    $alamat_penerima = $_POST['alamat_penerima'];
    $hp_penerima     = $_POST['hp_penerima'];

    mysqli_query($koneksi, "
        INSERT INTO penerima(nama_penerima, alamat_penerima, no_hp_penerima)
        VALUES ('$nama_penerima', '$alamat_penerima', '$hp_penerima')
    ");

    $id_penerima = mysqli_insert_id($koneksi);
}


    // Kolom tambahan 
    $nama_barang     = $_POST['nama_barang'];
    $jenis_barang    = $_POST['jenis_barang'];
    $kategori_barang = $_POST['kategori_barang'];
    $harga_pengiriman = $_POST['harga_pengiriman'];

    $berat        = $_POST['berat'];
    $nilai_barang = $_POST['nilai_barang'];
    $resi = generateResi();

    mysqli_query($koneksi, "INSERT INTO penerimaan
        (no_resi, id_pengirim, id_penerima, tgl_diterima, nama_barang, jenis_barang, kategori_barang, berat, harga_pengiriman, nilai_barang, status_penerimaan) 
        VALUES 
        ('$resi', '$id_pengirim', '$id_penerima', NOW(), '$nama_barang', '$jenis_barang', '$kategori_barang', '$berat', '$harga_pengiriman', '$nilai_barang', 'DITERIMA')");

    $id_penerimaan = mysqli_insert_id($koneksi);

    mysqli_query($koneksi, "INSERT INTO tracking_status(id_penerimaan, status, lokasi, waktu, keterangan)
            VALUES ('$id_penerimaan', 'Paket diterima di gudang', 'Gudang Pusat', NOW(), 'Barang berhasil diterima')");

    echo "<script>
            alert('Penerimaan Berhasil! Nomor Resi: $resi');
            window.location='penerimaan_barang.php';
          </script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Penerimaan Barang</title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #00bcd4, #3f51b5); margin: 0; padding: 20px; }
    .header { position: fixed; top: 0; left: 0; width: 100%; background-color: white; padding: 10px 30px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; }
    .header img { height:80px; }
    .header-text { flex-grow:1; text-align:center; line-height:1.2; color:white; }
    .header-text h2 { margin:0; font-size:22px; font-weight:bold; color:black;}
    .header-text p { margin:2px 0; font-size:28px; font-weight:bold; }
    .container { display: flex; gap: 20px; margin-top: 150px; max-width: 1500px; margin-left: auto; margin-right: auto; }
    .left { flex: 0 0 250px; padding: 20px; background: rgba(255,255,255,0.9); border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .right { flex: 1; padding: 20px; background: rgba(255,255,255,0.9); border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-height: 1000px; overflow-y: auto; }
    input, textarea { width:90%; padding:10px 12px; margin-bottom:12px; border-radius:10px; border:1px solid #ccc; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
    input:focus, textarea:focus { outline:none; border-color:#0275d8; box-shadow: 0 0 5px rgba(2,117,216,0.5); }
    button { padding:10px 20px; background:#0275d8; color:white; border:none; border-radius:10px; cursor:pointer; transition:0.3s; }
    button:hover { background:#025aa5; }
    table { width:100%; border-collapse: collapse; font-size:13px;}
    th, td { border:1px solid #ccc; padding:8px; text-align:left; }
    th { background:#f2f2f2; text-align:center; }
    /* Lebar masing-masing kolom tabel penerimaan */
    .table-penerimaan th:nth-child(1) { width: 10%; background:#f2f2f2; text-align:center; }   /* No Resi */
    .table-penerimaan th:nth-child(2) { width: 7%; background:#f2f2f2; text-align:center; }  /* Pengirim */
    .table-penerimaan th:nth-child(3) { width: 7%; background:#f2f2f2; text-align:center; }  /* Penerima */
    .table-penerimaan th:nth-child(4) { width: 12%; background:#f2f2f2; text-align:center; }  /* Nama Barang */
    .table-penerimaan th:nth-child(5) { width: 8%; background:#f2f2f2; text-align:center; }   /* Jenis */
    .table-penerimaan th:nth-child(6) { width: 10%; background:#f2f2f2; text-align:center; }  /* Kategori */
    .table-penerimaan th:nth-child(7) { width: 6%; background:#f2f2f2; text-align:center; }   /* Berat */
    .table-penerimaan th:nth-child(8) { width: 10%; background:#f2f2f2; text-align:center; }  /* Harga Kirim */
    .table-penerimaan th:nth-child(9) { width: 10%; background:#f2f2f2; text-align:center; }  /* Nilai Barang */
    .table-penerimaan th:nth-child(10) { width: 12%; background:#f2f2f2; text-align:center; } /* Tgl Diterima */
    .table-penerimaan th:nth-child(11) { width: 3%; background:#f2f2f2; text-align:center; }  /* Status */
    .table-penerimaan th:nth-child(12) { width: 8%; background:#f2f2f2; text-align:center; }  /* Opsi */
    td.right { text-align:right; }
    footer { margin-top:50px; text-align:center; color:#fff; font-size:13px; padding:15px 0; }
</style>
</head>
<body>

<div class="header">
    <div class="header-text">
        <p><img src="../image/elang.png" alt="Logo E-Lang"></p>
        <h2>PENERIMAAN BARANG</h2>
        <div id="waktu" style="font-weight:bold; font-size:16px; color:black;"></div>
    </div>
    <div style="position: fixed; top:10px; right: 15px;">
        <a href="../menu.php" style="color:white; background:#0275d8; padding:8px 15px; border-radius:20px; font-weight:bold;">Kembali ke Menu</a>
    </div>
</div>

<div class="container">
    <div class="left">
        <h3>Input Penerimaan Barang</h3>
        <form method="POST">
            <h4>Data Pengirim</h4>

            <input type="hidden" name="id_pengirim" id="id_pengirim">

            <div style="position:relative;">
                <input type="text" 
                    name="nama_pengirim" 
                    id="nama_pengirim"
                    placeholder="Nama Pengirim"
                    autocomplete="off"
                    required>

                <div id="list_pengirim"
                    style="position:absolute;
                            top:100%;
                            left:0;
                            right:0;
                            background:#fff;
                            border:1px solid #ccc;
                            z-index:999;
                            display:none;
                            max-height:200px;
                            overflow-y:auto;">
                </div>
            </div>

            <textarea name="alamat_pengirim" id="alamat_pengirim" readonly style="background:#f5f5f5; cursor:not-allowed;" placeholder="Alamat Terisi Otomatis" required></textarea>
            <input type="text" name="hp_pengirim" id="hp_pengirim" readonly style="background:#f5f5f5; cursor:not-allowed;" placeholder="No HP Pengirim" required>

            <h4>Data Penerima</h4>

            <input type="hidden" name="id_penerima" id="id_penerima">

            <div style="position:relative;">
                <input type="text" 
                    name="nama_penerima"
                    id="nama_penerima"
                    placeholder="Nama Penerima"
                    autocomplete="off"
                    required>

                <div id="list_penerima"
                    style="position:absolute;
                            top:100%;
                            left:0;
                            right:0;
                            background:#fff;
                            border:1px solid #ccc;
                            z-index:999;
                            display:none;
                            max-height:200px;
                            overflow-y:auto;">
                </div>
            </div>

            <textarea name="alamat_penerima" id="alamat_penerima" readonly style="background:#f5f5f5; cursor:not-allowed;" placeholder="Alamat Penerima" required></textarea>
            <input type="text" name="hp_penerima" id="hp_penerima" readonly style="background:#f5f5f5; cursor:not-allowed;" placeholder="No HP Penerima" required>

            <h4>Detail Barang</h4>

            <input type="text" name="nama_barang" placeholder="Nama Barang" required>

            <select name="jenis_barang" required 
            style="width:100%; padding:10px 12px; margin-bottom:12px; border-radius:10px; border:1px solid #ccc;">
            <option value="">-- Pilih Jenis Barang --</option>
            <option value="Elektronik">Elektronik</option>
            <option value="Pakaian">Pakaian</option>
            <option value="Dokumen">Dokumen</option>
            <option value="Suku Cadang">Suku Cadang</option>
            <option value="Makanan">Makanan</option>
            <option value="Lainnya">Lainnya</option>
            </select>

            <select name="kategori_barang" required 
            style="width:100%; padding:10px 12px; margin-bottom:12px; border-radius:10px; border:1px solid #ccc;">
            <option value="">-- Pilih Kategori Barang --</option>
            <option value="Fragile">Fragile / Mudah Pecah</option>
            <option value="Cairan">Cairan</option>
            <option value="Standar">Standar</option>
            <option value="Mudah Terbakar">Mudah Terbakar</option>
            <option value="Berbahaya">Bahan Berbahaya</option>
            </select>

            <input type="number" step="0.01" name="berat" placeholder="Berat Barang (Kg)" required>

            <input type="number" step="0.01" name="harga_pengiriman" placeholder="Biaya Pengiriman (Rp)" required>

            <input type="number" step="0.01" name="nilai_barang" placeholder="Harga Barang (Rp)" required>

            <button type="submit" name="simpan">Simpan Penerimaan</button>
        </form>
    </div>

    <div class="right">
        <h3>Rekaman Penerimaan Barang</h3>
        <table class="table-penerimaan">
            <thead>
                <tr>
                    <th style="width:15%;">No Resi</th>
                    <th style="width:10%;">Pengirim</th>
                    <th style="width:10%;">Penerima</th>
                    <th style="width:15%;">Nama Barang</th>
                    <th style="width:10%;">Jenis</th>
                    <th style="width:10%;">Kategori</th>
                    <th style="width:10%;">Berat (Kg)</th>
                    <th style="width:10%;">Tgl Diterima</th>
                    <th style="width:10%;">Status</th>
                    <th style="width:10%;">Opsi</th>
                </tr>
            </thead>
            <tbody>
<?php
$query = "SELECT 
            p.no_resi,

            pg.nama_pengirim,
            pg.no_registrasi AS reg_pengirim,

            pr.nama_penerima,
            pr.no_registrasi AS reg_penerima,

            p.nama_barang, 
            p.jenis_barang, 
            p.kategori_barang,
            p.berat, 
            p.harga_pengiriman, 
            p.nilai_barang, 
            p.tgl_diterima, 
            p.status_penerimaan
          FROM penerimaan p
          JOIN pengirim pg ON p.id_pengirim = pg.id_pengirim
          JOIN penerima pr ON p.id_penerima = pr.id_penerima
          ORDER BY p.tgl_diterima DESC";

$result = mysqli_query($koneksi, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $resi = $row['no_resi'];
        echo "<tr>
                <td>{$resi}</td>
                <td>{$row['nama_pengirim']}</td>
                <td>{$row['nama_penerima']}</td>
                <td>{$row['nama_barang']}</td>
                <td>{$row['jenis_barang']}</td>
                <td>{$row['kategori_barang']}</td>
                <td class='right'>{$row['berat']}</td>
                <td>{$row['tgl_diterima']}</td>
                <td>{$row['status_penerimaan']}</td>
                <td>
                    <a href=\"edit_penerimaan.php?no_resi=".urlencode($resi)."\" 
                       onclick=\"window.open(this.href,'popup','width=600,height=700,scrollbars=yes'); return false;\" style=\"margin-right:5px;\">Edit</a>
                    <a href=\"hapus_penerimaan.php?no_resi=".urlencode($resi)."\" 
                       onclick=\"return confirm('Yakin mau hapus Resi ".htmlspecialchars($resi)." ?');\" style=\"color:red;\">Hapus</a>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='12'>Data tidak ditemukan</td></tr>";
}
?>
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
    idleTimer = setTimeout(() => {
        alert("Session berakhir karena tidak ada aktivitas.");
        window.location.href = "/ui/logout.php";
    }, idleLimit);
}

window.onload = resetTimer;
document.onmousemove = resetTimer;
document.onkeypress = resetTimer;
document.onscroll = resetTimer;
document.onclick = resetTimer;

<?php if(isset($_GET['timeout']) && $_GET['timeout']==1): ?>
    alert("Session berakhir! Silakan login ulang.");
<?php endif; ?>
</script>

<script>
setInterval(() => {
    fetch("heartbeat.php"); // panggil server tiap 30 detik ngasih tau klo halaman lagi ada aktifitas
}, 30000);
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$('#nama_pengirim').on('keyup', function () {
    let keyword = $(this).val();

    if (keyword.length >= 2) {
        $.getJSON('penerimaan_barang.php', {
            ajax: 'pengirim',
            q: keyword
        }, function (data) {

            let html = '';
            if (data.length === 0) {
                html = '<div style="padding:8px;color:#888;">Data tidak ditemukan</div>';
            } else {
                data.forEach(item => {
                    html += `
                        <div class="item-pengirim"
                             style="padding:8px; cursor:pointer;"
                             data-id="${item.id_pengirim}"
                             data-nama="${item.nama_pengirim}"
                             data-hp="${item.no_hp_pengirim}"
                             data-alamat="${item.alamat_pengirim}">
                             ${item.nama_pengirim}
                        </div>
                    `;
                });
            }

            $('#list_pengirim').html(html).show();
        });
    } else {
        $('#list_pengirim').hide();
    }
});

// klik pilihan
$(document).on('click', '.item-pengirim', function () {
    $('#id_pengirim').val($(this).data('id'));
    $('#nama_pengirim').val($(this).data('nama'));
    $('#hp_pengirim').val($(this).data('hp'));
    $('#alamat_pengirim').val($(this).data('alamat'));

    $('#list_pengirim').hide();
});

// klik di luar → tutup list
$(document).click(function(e){
    if (!$(e.target).closest('#nama_pengirim, #list_pengirim').length) {
        $('#list_pengirim').hide();
    }
});
</script>

<script>
$('#nama_penerima').on('keyup', function () {
    let keyword = $(this).val();

    if (keyword.length >= 2) {
        $.getJSON('penerimaan_barang.php', {
            ajax: 'penerima',
            q: keyword
        }, function (data) {

            let html = '';
            if (data.length === 0) {
                html = '<div style="padding:8px;color:#888;">Data tidak ditemukan</div>';
            } else {
                data.forEach(item => {
                    html += `
                        <div class="item-penerima"
                             style="padding:8px; cursor:pointer;"
                             data-id="${item.id_penerima}"
                             data-nama="${item.nama_penerima}"
                             data-hp="${item.no_hp_penerima}"
                             data-alamat="${item.alamat_penerima}">
                             ${item.nama_penerima}
                        </div>
                    `;
                });
            }

            $('#list_penerima').html(html).show();
        });
    } else {
        $('#list_penerima').hide();
    }
});

// klik pilihan penerima
$(document).on('click', '.item-penerima', function () {
    $('#id_penerima').val($(this).data('id'));
    $('#nama_penerima').val($(this).data('nama'));
    $('#hp_penerima').val($(this).data('hp'));
    $('#alamat_penerima').val($(this).data('alamat'));

    $('#list_penerima').hide();
});
</script>

</body>
</html>
