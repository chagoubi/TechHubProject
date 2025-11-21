<?php
session_start();

// Vérification si admin connecté
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// Connexion à la base de données
require_once 'db.php';

// Statistiques générales
$stats_general = $conn->query("SELECT 
    COUNT(DISTINCT c.id) as total_commandes,
    COUNT(DISTINCT cl.id) as total_clients,
    COUNT(DISTINCT p.id) as total_produits,
    SUM(c.total) as revenu_total
    FROM commande c
    LEFT JOIN client cl ON c.id_client = cl.id
    LEFT JOIN produit p ON 1=1
")->fetch_assoc();

// Top 5 produits les plus vendus
$top_products = $conn->query("SELECT p.nom, COUNT(*) as ventes, SUM(cd.prix * cd.quantite) as revenu
    FROM commande_detail cd
    JOIN produit p ON cd.id_produit = p.id
    GROUP BY cd.id_produit
    ORDER BY ventes DESC
    LIMIT 5");

// Top 5 clients
$top_clients = $conn->query("SELECT cl.nom, cl.prenom, COUNT(c.id) as nb_commandes, SUM(c.total) as total_depense
    FROM client cl
    JOIN commande c ON cl.id = c.id_client
    GROUP BY cl.id
    ORDER BY total_depense DESC
    LIMIT 5");

// Commandes par mois (derniers 6 mois)
$monthly_orders = $conn->query("SELECT 
    DATE_FORMAT(date_commande, '%Y-%m') as mois,
    COUNT(*) as nb_commandes,
    SUM(total) as revenu
    FROM commande
    WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
    ORDER BY mois ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub - Statistiques</title>
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

        .page-title {
            color: white;
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            margin-bottom: 20px;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-card:nth-child(2) .stat-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .stat-card:nth-child(3) .stat-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .stat-card:nth-child(4) .stat-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .stat-title {
            color: #6b7280;
            font-size: 0.95em;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            color: #1f2937;
            font-size: 2.5em;
            font-weight: 700;
        }

        .stat-trend {
            margin-top: 10px;
            font-size: 0.85em;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-title i {
            color: #667eea;
        }

        .top-list {
            list-style: none;
        }

        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px;
            margin-bottom: 12px;
            background: #f9fafb;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .top-item:hover {
            background: #f3f4f6;
            transform: translateX(5px);
        }

        .item-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1em;
        }

        .item-info {
            flex: 1;
            margin-left: 15px;
        }

        .item-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .item-detail {
            font-size: 0.85em;
            color: #6b7280;
        }

        .item-value {
            font-size: 1.2em;
            font-weight: 700;
            color: #667eea;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .monthly-chart {
            margin-top: 20px;
        }

        .month-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9fafb;
            border-radius: 10px;
        }

        .month-name {
            font-weight: 600;
            color: #1f2937;
            min-width: 100px;
        }

        .month-bar {
            flex: 1;
            margin: 0 20px;
        }

        .month-value {
            font-weight: 700;
            color: #667eea;
            min-width: 120px;
            text-align: right;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .top-item {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .item-info {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">
                <i class="fas fa-chart-line"></i> Statistiques Détaillées
            </div>
        </div>
        <a href="admin_dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Retour au Dashboard
        </a>
    </div>

    <div class="container">
        <h1 class="page-title">Rapports & Analyses</h1>

        <!-- Statistiques générales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-title">Total Commandes</div>
                <div class="stat-number"><?php echo number_format($stats_general['total_commandes']); ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i> +12% ce mois
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-title">Clients Actifs</div>
                <div class="stat-number"><?php echo number_format($stats_general['total_clients']); ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i> +8% ce mois
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-title">Produits</div>
                <div class="stat-number"><?php echo number_format($stats_general['total_produits']); ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i> +3 nouveaux
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-title">Revenu Total</div>
                <div class="stat-number"><?php echo number_format($stats_general['revenu_total'], 2); ?> DT</div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i> +25% ce mois
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <!-- Top Produits -->
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-trophy"></i>
                    Top 5 Produits les Plus Vendus
                </div>
                <ul class="top-list">
                    <?php 
                    $rank = 1;
                    $max_ventes = 0;
                    $products_array = [];
                    while($product = $top_products->fetch_assoc()) {
                        $products_array[] = $product;
                        if($product['ventes'] > $max_ventes) $max_ventes = $product['ventes'];
                    }
                    foreach($products_array as $product):
                        $percent = $max_ventes > 0 ? ($product['ventes'] / $max_ventes) * 100 : 0;
                    ?>
                    <li class="top-item">
                        <div class="item-rank"><?php echo $rank++; ?></div>
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($product['nom']); ?></div>
                            <div class="item-detail"><?php echo $product['ventes']; ?> ventes</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </div>
                        <div class="item-value"><?php echo number_format($product['revenu'], 2); ?> DT</div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Top Clients -->
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-user-star"></i>
                    Top 5 Meilleurs Clients
                </div>
                <ul class="top-list">
                    <?php 
                    $rank = 1;
                    $max_depense = 0;
                    $clients_array = [];
                    while($client = $top_clients->fetch_assoc()) {
                        $clients_array[] = $client;
                        if($client['total_depense'] > $max_depense) $max_depense = $client['total_depense'];
                    }
                    foreach($clients_array as $client):
                        $percent = $max_depense > 0 ? ($client['total_depense'] / $max_depense) * 100 : 0;
                    ?>
                    <li class="top-item">
                        <div class="item-rank"><?php echo $rank++; ?></div>
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?></div>
                            <div class="item-detail"><?php echo $client['nb_commandes']; ?> commandes</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </div>
                        <div class="item-value"><?php echo number_format($client['total_depense'], 2); ?> DT</div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Évolution mensuelle -->
        <div class="chart-card">
            <div class="chart-title">
                <i class="fas fa-chart-bar"></i>
                Évolution des Commandes (6 derniers mois)
            </div>
            <div class="monthly-chart">
                <?php 
                $months_names = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
                $max_revenu = 0;
                $monthly_array = [];
                while($month = $monthly_orders->fetch_assoc()) {
                    $monthly_array[] = $month;
                    if($month['revenu'] > $max_revenu) $max_revenu = $month['revenu'];
                }
                foreach($monthly_array as $month):
                    $month_num = intval(substr($month['mois'], 5, 2));
                    $month_name = $months_names[$month_num - 1];
                    $percent = $max_revenu > 0 ? ($month['revenu'] / $max_revenu) * 100 : 0;
                ?>
                <div class="month-row">
                    <div class="month-name"><?php echo $month_name . ' ' . substr($month['mois'], 0, 4); ?></div>
                    <div class="month-bar">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                    <div class="month-value">
                        <?php echo $month['nb_commandes']; ?> cmd • 
                        <?php echo number_format($month['revenu'], 0); ?> DT
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Animation des barres au chargement
        window.addEventListener('load', function() {
            const fills = document.querySelectorAll('.progress-fill');
            fills.forEach(fill => {
                const width = fill.style.width;
                fill.style.width = '0';
                setTimeout(() => {
                    fill.style.width = width;
                }, 100);
            });
        });
    </script>
</body>
</html>