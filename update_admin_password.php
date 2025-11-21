<?php
include 'config.php'
$email = "ranya.chagoubi@techhub.tn";
$new_password = "admin123";

// Hash el password el jdid
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update fil database
$query = $conn->prepare("UPDATE admin SET motdepasse = ? WHERE email = ?");
$query->bind_param("ss", $hashed_password, $email);

if($query->execute()) {
    if($query->affected_rows > 0) {
        echo "✅ Mot de passe modifié avec succès!<br><br>";
        echo "📧 Email: " . $email . "<br>";
        echo "🔐 Nouveau mot de passe: " . $new_password . "<br><br>";
        echo "🔒 Hash généré: " . $hashed_password;
    } else {
        echo "⚠️ Email non trouvé dans la base de données!";
    }
} else {
    echo "❌ Erreur: " . $conn->error;
}
?>