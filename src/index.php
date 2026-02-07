<?php
session_start();

// ============= SECURITY =============

// 1. Set secure headers (perangkap hacker)
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// 2. Generate CSRF Token untuk form login
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// ================= TIMEOUT ALERT =================
// Jika redirect karena session timeout
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    echo "<script>alert('Session berakhir! Anda otomatis logout.');</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Lang Logistic</title>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #00bcd4, #3f51b5);
            color: white;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 100px;
        }

        .left {
            text-align: center;
            flex: 1;
        }
        .logo {
            width: 300px;
            margin-bottom: 10px;
        }

        .bg-kiri {
            position: absolute;
            left: 0;
            top: 0;
            height: 100vh;
            width: auto;
            opacity: 0.25;
            pointer-events: none;
        }

        h1 {
            font-size: 2.2em;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
            margin-bottom: 5px;
        }
        p {
            font-size: 0.5em;
        }

        /* ==========================
           BOX LOGIN LINGKARAN FULL
        ========================== */
        .right {
            background: rgba(255,255,255,0.12);
            width: 320px;
            height: 320px;
            border-radius: 50%;
            box-shadow: 0 0 25px rgba(0,0,0,0.35);
            backdrop-filter: blur(10px);

            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;

            padding: 25px;
        }

        .right h2 {
            margin-bottom: 0px;
        }

        .form-group {
            width: 100%;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .p1 {
            font-size: 0.7em;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 30px;
            background: rgba(255,255,255,0.8);
            outline: none;
        }

        input:focus {
            box-shadow: 0 0 10px rgba(255,255,255,0.6);
        }

        button {
            width: 100%;
            padding: 10px;
            border: none;
            background: #ff9800;
            color: white;
            font-weight: bold;
            border-radius: 30px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #ffc107;
        }

        footer {
            position: absolute;
            bottom: 30px;
            width: 100%;
            text-align: center;
            font-size: 0.7em;
            opacity: 0.7;
        }
    </style>
</head>
<body>

    <img src="/ui/image/halal.png" class="bg-kiri">

    <!-- Kolom Kiri -->
    <div class="left">
        <img src="image/elang.png" alt="Logo3" class="logo">
        <h1>WAREHOUSE MANAGEMENT CONTROL<h1>
        <p>Connecting with github</p>
        <p>Version v1.4.0</p>
    </div>

    <!-- BOX LOGIN LINGKARAN -->
    <div class="right">
        <h2>Login</h2>
        <p class="p1">Akses Terbatas Hubungi 123</p>
        <form action="proses_login.php" method="POST" autocomplete="off">
            
            <!-- CSRF SECURITY TOKEN -->
            <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit">Masuk</button>
        </form>
    </div>

    <footer>
        <img src="../image/lunla.png" alt="Logo" style="width:30px; vertical-align:middle; margin-right:2px;">
        &copy; <?php echo date("Y"); ?> full support by langlangbuana university - software development class
    </footer>

</body>
</html>
