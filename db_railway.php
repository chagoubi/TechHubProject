<?php
// ===========================================
// db_railway.php - Railway Connection
// ===========================================

// 1️⃣ Jib credentials mn Railway (environment variables)
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQLDATABASE') ?: 'techhub_tn';
$port = getenv('MYSQLPORT') ?: '3306';

// 2️⃣ Connect
$conn = new mysqli($host, $user, $pass, $db, $port);

// 3️⃣ Check connection
if ($conn->connect_error) {
    die("❌ Connexion ratée: " . $conn->connect_error);
}

// 4️⃣ UTF-8 (accents w emoji)
$conn->set_charset("utf8");

// 5️⃣ Fonction protection SQL injection
function secure_input($data){
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// 6️⃣ Dashboard statistics
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM client");
    $total_users = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $conn->query("SELECT COUNT(*) as total FROM produit");
    $total_products = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $conn->query("SELECT COUNT(*) as total FROM commande");
    $total_orders = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $conn->query("SELECT IFNULL(SUM(total), 0) as revenue FROM commande");
    $total_revenue = $result ? $result->fetch_assoc()['revenue'] : 0;

} catch(Exception $e) {
    $total_users = 0;
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
}
?>