<?php
// ===========================================
// db_railway.php - Railway MySQL Connection
// ===========================================

// Variables exactes men Railway
$host = getenv('MYSQLHOST');        // (private domain)
$user = getenv('MYSQLUSER');        // root
$pass = getenv('MYSQLPASSWORD');    // MYSQL_ROOT_PASSWORD
$db   = getenv('MYSQLDATABASE');    // railway (default)
$port = getenv('MYSQLPORT');        // 3306

// Vérification des variables
if (!$host || !$user || !$db || !$port) {
    die("❌ Missing Railway environment variables. Please configure them first.");
}

try {
    // Connexion MySQL
    $conn = new mysqli($host, $user, $pass, $db, $port);

    // Test d'erreur
    if ($conn->connect_error) {
        throw new Exception("MySQL Connection Failed → " . $conn->connect_error);
    }

    // Encodage UTF-8
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("UTF-8 Error → " . $conn->error);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    die("❌ Impossible de se connecter à la base de données. Réessayez plus tard.");
}


// ===========================================
// Fonction anti-SQL injection
// ===========================================
function secure_input($data){
    global $conn;
    if ($data === null) return null;

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}


// ===========================================
// Statistiques Dashboard (optionnel)
// ===========================================
$total_users = 0;
$total_products = 0;
$total_orders = 0;
$total_revenue = 0;

try {
    // Vérifier existence tables
    $tables = ['client', 'produit', 'commande'];
    $exist = true;

    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$check || $check->num_rows === 0) {
            $exist = false;
        }
    }

    if ($exist) {
        // Users
        $r = $conn->query("SELECT COUNT(*) AS total FROM client");
        $total_users = $r ? $r->fetch_assoc()['total'] : 0;

        // Products
        $r = $conn->query("SELECT COUNT(*) AS total FROM produit");
        $total_products = $r ? $r->fetch_assoc()['total'] : 0;

        // Orders
        $r = $conn->query("SELECT COUNT(*) AS total FROM commande");
        $total_orders = $r ? $r->fetch_assoc()['total'] : 0;

        // Revenue
        $r = $conn->query("SELECT IFNULL(SUM(total), 0) AS revenue FROM commande WHERE statut != 'annulée'");
        $total_revenue = $r ? $r->fetch_assoc()['revenue'] : 0;
    }

} catch(Exception $e) {
    error_log("Stats error: " . $e->getMessage());
}


// ===========================================
// Close connection at end
// ===========================================
function close_connection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}
register_shutdown_function('close_connection');

?>
