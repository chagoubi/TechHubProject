<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de connexion</h2>";

include 'db_railway.php';

if($conn){
    echo "✅ Connexion à la base de données OK !<br><br>";
    
    // Afficher les tables
    echo "<strong>Tables dans la base techhub_tn :</strong><br>";
    $result = $conn->query("SHOW TABLES");
    
    if($result){
        while($row = $result->fetch_array()){
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "❌ Erreur : " . $conn->error;
    }
} else {
    echo "❌ Connexion échouée !";
}
?>