<?php
session_start();

// ========================
// CEK LOGIN & ANTI HIJACK
// ========================
if (!isset($_SESSION["login"]) || $_SESSION["login"] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: selamat_datang.php");
    exit;
}

// ========================
// AUTO LOGOUT SERVER-SIDE
// ========================
$max_idle = 600000; // 10 menit
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $max_idle) {
        session_unset();
        session_destroy();
        // set flag timeout untuk client-side alert
        header("Location: selamat_datang.php?timeout=1");
        exit;
    }
}
$_SESSION['last_activity'] = time();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard E-Lang Logistic</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #00bcd4, #3f51b5); margin:0; padding:0; }
        .header { background-color:#fff; padding:10px 30px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #ccc; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
        .header img { height:100px; }
        .header-text { flex-grow:1; text-align:center; line-height:1.5; }
        .header-text h2 { margin:0; font-size:20px; font-weight:bold; }
        .header-text p { margin:2px 0; font-size:30px; font-weight:bold; }
        .menu-container { display:flex; justify-content:center; flex-wrap:wrap; margin-top:60px; gap:40px; }
        .menu-box { width:200px; height:180px; border-radius:15px; color:white; text-align:center; text-decoration:none; display:flex; flex-direction:column; justify-content:center; align-items:center; box-shadow:0 4px 6px rgba(0,0,0,0.3); transition:0.3s; }
        .menu-box:hover { transform:scale(1.07); opacity:0.9; }
        .menu-box img { width:100px; height:100px; margin-bottom:15px; }
        .green {background: linear-gradient(135deg, #00bcd4, #3f51b5);}
        .blue {background: linear-gradient(135deg, #00bcd4, #3f51b5);}
        .orange {background: linear-gradient(135deg, #00bcd4, #3f51b5);}
        .menu-text { font-size:17px; font-weight:bold; }
        footer { margin-top:50px; text-align:center; color:#fff; font-size:13px; padding:15px 0; }
        footer hr { width:100%; border:0; border-top:1px solid #ccc; margin-bottom:8px; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-text">
            <p> <img src="image/elang.png" alt="Logo E-Lang"> </p>
            <h2>WAREHOUSE MANAGEMENT CONTROL</h2>
            <div id="waktu" style="font-weight:bold; font-size:16px; color:black;"></div>
        </div>
        <div style="width:60px"></div>
        
        <div style="position: fixed; top:10px; right:15px;">
            <a href="logout.php" style="color:white; background:#e30a03ff; padding:8px 15px; border-radius:20px; font-weight:bold;">Sign Out</a>
        </div>
    </div>

    <!-- Menu -->
    <div class="menu-container">
        <a href="pengirim_penerima/pengirim_penerima.php" class="menu-box orange">
            <img src="image/pp.png" alt="Pengirim_Penerima">
            <div class="menu-text">Data Pengirim & Penerima</div>
        </a>
        <a href="penerimaan_barang/penerimaan_barang.php" class="menu-box green">
            <img src="image/penerimaanbarang2.png" alt="Penerimaan Barang">
            <div class="menu-text">Menu Penerimaan Barang</div>
        </a>
        <a href="penempatan_barang/penempatan_barang.php" class="menu-box blue">
            <img src="image/penempatanbarang.png" alt="penempatan_barang">
            <div class="menu-text">Menu Penempatan Barang</div>
        </a>
    </div>

    <!-- Footer -->
    <footer>
        <img src="../image/lunla.png" alt="Logo" style="width:30px; vertical-align:middle; margin-right:2px;">
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
        window.location.href = "logout.php";
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

</body>
</html>
