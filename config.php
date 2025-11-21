<?php
// ===========================================
// config.php - Auto-detect environment
// ===========================================

// Ken fi Railway → ista3mel db_railway.php
if (getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST')) {
    echo "🚂 Mode: Railway Production<br>";
    require_once 'db_railway.php';
} 
// Sinon → ista3mel db.php (WAMP local)
else {
    echo "💻 Mode: WAMP Local<br>";
    require_once 'db.php';
}
?>