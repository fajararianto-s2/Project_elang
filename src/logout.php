<?php
session_start();
session_unset();
session_destroy();
?>

<script>
alert("Anda berhasil logout.");
window.location = "index.php";
</script>
