<?php
session_start();

// Vérification si admin connecté
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// Connexion à la base de données
require_once 'db.php';

// Récupération des commandes avec informations client
$query = "SELECT c.*, cl.nom, cl.prenom, cl.email, cl.telephone 
          FROM commande c 
          LEFT JOIN client cl ON c.id_client = cl.id 
          ORDER BY c.date_commande DESC";
$result = $conn->query($query);

// Statistiques commandes
$stats_query = "SELECT 
    COUNT(*) as total_commandes,
    SUM(total) as revenu_total,
    AVG(total) as panier_moyen
    FROM commande";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub - Gestion Commandes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #667eea;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 1.8em;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .container {
            max-width: 1600px;
            margin: 40px auto;
            padding: 0 40px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .page-title {
            color: white;
            font-size: 2.5em;
            font-weight: 700;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .mini-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .mini-stat {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .mini-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .mini-stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
        }

        .mini-stat:nth-child(1) .mini-stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .mini-stat:nth-child(2) .mini-stat-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .mini-stat:nth-child(3) .mini-stat-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .mini-stat-content h3 {
            font-size: 2em;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .mini-stat-content p {
            font-size: 0.9em;
            color: #6b7280;
            font-weight: 500;
        }

        .orders-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95em;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border-left: 5px solid #667eea;
        }

        .order-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .order-id {
            font-size: 1.3em;
            font-weight: 700;
            color: #1f2937;
        }

        .order-date {
            color: #6b7280;
            font-size: 0.9em;
        }

        .order-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .order-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 1.1em;
        }

        .info-content h4 {
            font-size: 0.85em;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .info-content p {
            font-size: 0.95em;
            color: #1f2937;
            font-weight: 600;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #f3f4f6;
        }

        .order-total {
            font-size: 1.5em;
            font-weight: 700;
            color: #667eea;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view:hover {
            background: #3b82f6;
            color: white;
        }

        .btn-print {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-print:hover {
            background: #f59e0b;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }

            .order-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .order-body {
                grid-template-columns: 1fr;
            }

            .order-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">
                <i class="fas fa-shopping-cart"></i> Gestion Commandes
            </div>
        </div>
        <a href="admin_dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Retour au Dashboard
        </a>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Liste des Commandes</h1>
        </div>

        <div class="mini-stats">
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo $stats['total_commandes']; ?></h3>
                    <p>Commandes Total</p>
                </div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo number_format($stats['revenu_total'], 2); ?> DT</h3>
                    <p>Revenu Total</p>
                </div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo number_format($stats['panier_moyen'], 2); ?> DT</h3>
                    <p>Panier Moyen</p>
                </div>
            </div>
        </div>

        <div class="orders-container">
            <div class="filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher une commande...">
                </div>
            </div>

            <div id="ordersContainer">
                <?php if($result->num_rows > 0): ?>
                    <?php while($order = $result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Commande #<?php echo $order['id']; ?></div>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-info">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Client</h4>
                                    <p><?php echo htmlspecialchars($order['nom'] . ' ' . $order['prenom']); ?></p>
                                </div>
                            </div>

                            <div class="order-info">
                                <div class="info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Email</h4>
                                    <p><?php echo htmlspecialchars($order['email']); ?></p>
                                </div>
                            </div>

                            <div class="order-info">
                                <div class="info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Téléphone</h4>
                                    <p><?php echo htmlspecialchars($order['telephone']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                Total: <?php echo number_format($order['total'], 2); ?> DT
                            </div>
                            <div class="order-actions">
                                <button class="btn-action btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i> Voir Détails
                                </button>
                                <button class="btn-action btn-print" onclick="printOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-print"></i> Imprimer
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Aucune commande</h3>
                        <p>Il n'y a pas encore de commandes dans le système</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Recherche commandes
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const cards = document.querySelectorAll('.order-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        function viewOrder(id) {
            alert(`Affichage des détails de la commande #${id} - À implémenter`);
        }

        function printOrder(id) {
            alert(`Impression de la commande #${id} - À implémenter`);
        }
    </script>
</body>
</html>