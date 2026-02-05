<?php
session_start();
require "koneksi.php"; // file koneksi DB

/* ============================================================
   1. CEK LIMIT LOGIN – BRUTE FORCE PROTECTION
   ============================================================ */
$ip = $_SERVER['REMOTE_ADDR'];
$max_attempt = 5;
$lock_time   = 10 * 60; // 10 menit

$cek = $koneksi->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?");
$cek->bind_param("s", $ip);
$cek->execute();
$cek->store_result();
$cek->bind_result($attempts, $last_attempt);
$cek->fetch();

if ($cek->num_rows > 0) {
    if ($attempts >= $max_attempt && (time() - $last_attempt) < $lock_time) {
        die("<script>alert('Terlalu banyak percobaan login. Coba lagi nanti.'); window.location='login.php';</script>");
    }
}

/* ============================================================
   2. CEK CSRF TOKEN
   ============================================================ */
if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    die("Akses ilegal! (Invalid CSRF Token)");
}

/* ============================================================
   3. FILTER INPUT – ANTI XSS
   ============================================================ */
$username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
$password = trim($_POST['password']);

if ($username === "" || $password === "") {
    die("<script>alert('Username & Password wajib diisi'); window.location='login.php';</script>");
}

/* ============================================================
   4. CEK USER – ANTI SQL INJECTION
   ============================================================ */
$stmt = $koneksi->prepare("SELECT id, username, password FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    record_attempt($koneksi, $ip);
    gagal();
}

$stmt->bind_result($id, $user_ambil, $password_hash);
$stmt->fetch();

/* ============================================================
   5. VERIFIKASI PASSWORD HASH
   ============================================================ */
if (!password_verify($password, $password_hash)) {
    record_attempt($koneksi, $ip);
    gagal();
}

/* ============================================================
   6. JIKA LOGIN SUKSES
   ============================================================ */
session_regenerate_id(true);

$_SESSION["login"] = true;
$_SESSION["user_id"] = $id;
$_SESSION["username"] = $user_ambil;

// reset gagal login
$del = $koneksi->prepare("DELETE FROM login_attempts WHERE ip = ?");
$del->bind_param("s", $ip);
$del->execute();

echo "<script>
alert('Anda berhasil login');
window.location = 'menu.php';
</script>";
exit;



/* ============================================================
   7. FUNCTION – CATAT GAGAL LOGIN
   ============================================================ */
function record_attempt($koneksi, $ip) {
    $time = time();

    $cek = $koneksi->prepare("SELECT attempts FROM login_attempts WHERE ip = ?");
    $cek->bind_param("s", $ip);
    $cek->execute();
    $cek->store_result();
    $cek->bind_result($attempts);
    $cek->fetch();

    if ($cek->num_rows > 0) {
        $attempts++;
        $up = $koneksi->prepare("UPDATE login_attempts SET attempts = ?, last_attempt = ? WHERE ip = ?");
        $up->bind_param("iis", $attempts, $time, $ip);
        $up->execute();
    } else {
        $attempts = 1;
        $ins = $koneksi->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, ?, ?)");
        $ins->bind_param("sii", $ip, $attempts, $time);
        $ins->execute();
    }
}

/* ============================================================
   8. FUNCTION – ALERT GAGAL
   ============================================================ */
function gagal() {
    echo "<script>alert('Username atau password salah'); window.location='index.php';</script>";
    exit;
}

// reset session lama
session_start();
session_unset();
session_destroy();
session_start(); // buat session baru
