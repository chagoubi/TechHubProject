<?php
// ===========================================
// db.php
// Connexion à la base de données TechHub Tunisia
// ===========================================

// 1️⃣ Paramètres de connexion MySQL
$host = "localhost";     // serveur MySQL (localhost si WAMP)
$user = "root";          // utilisateur MySQL par défaut
$pass = "";              // mot de passe MySQL par défaut (vide sur WAMP)
$db   = "techhub_tn";    // nom de la base de données

// 2️⃣ Création de la connexion
$conn = new mysqli($host, $user, $pass, $db);

// 3️⃣ Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de connexion : " . $conn->connect_error);
}

// 4️⃣ Définir le charset UTF-8 pour gérer les accents et français
$conn->set_charset("utf8");

// 5️⃣ Fonction helper pour sécuriser les entrées des formulaires
function secure_input($data){
    global $conn; // ⭐ IMPORTANT - récupère la connexion
    $data = trim($data);                // supprimer espaces inutiles
    $data = stripslashes($data);        // supprimer les backslashes
    $data = htmlspecialchars($data);    // convertir caractères spéciaux en HTML
    $data = mysqli_real_escape_string($conn, $data); // ⭐ Protection SQL injection
    return $data;
}

// 6️⃣ Récupération des statistiques pour le dashboard admin
try {
    // Total clients (utilisateurs)
    $result = $conn->query("SELECT COUNT(*) as total FROM client");
    $total_users = $result ? $result->fetch_assoc()['total'] : 0;

    // Total produits
    $result = $conn->query("SELECT COUNT(*) as total FROM produit");
    $total_products = $result ? $result->fetch_assoc()['total'] : 0;

    // Total commandes
    $result = $conn->query("SELECT COUNT(*) as total FROM commande");
    $total_orders = $result ? $result->fetch_assoc()['total'] : 0;

    // Total revenus (somme de tous les totaux des commandes)
    $result = $conn->query("SELECT IFNULL(SUM(total), 0) as revenue FROM commande");
    $total_revenue = $result ? $result->fetch_assoc()['revenue'] : 0;

} catch(Exception $e) {
    // Valeurs par défaut en cas d'erreur
    $total_users = 0;
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
}
?>