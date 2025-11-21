<?php
session_start();

// Vérification si admin connecté
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// Si admin yclicki 3la déconnexion
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// Connexion à la base de données
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub - Dashboard Admin</title>
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

        /* Header */
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

        .welcome {
            color: #1f2937;
            font-weight: 600;
        }

        .welcome span {
            color: #667eea;
        }

        .btn-logout {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        /* Container principal */
        .container {
            max-width: 1400px;
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

        /* Cards statistiques */
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
            border: 2px solid rgba(255, 255, 255, 0.5);
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

        /* Menu d'actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 35px;
            text-align: center;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.3s;
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .action-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        .action-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .action-card:hover .action-icon {
            animation: bounce 0.6s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .action-title {
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .action-desc {
            color: #6b7280;
            font-size: 0.95em;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 20px;
            }

            .container {
                padding: 0 20px;
            }

            .page-title {
                font-size: 2em;
            }

            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo">
                <i class="fas fa-shield-alt"></i> TechHub Admin
            </div>
            <div class="welcome">
                Bienvenue, <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            </div>
        </div>
        <a href="?logout=1" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i>
            Déconnexion
        </a>
    </div>

    <!-- Container principal -->
    <div class="container">
        <h1 class="page-title">Tableau de Bord Administrateur</h1>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-title">Total Utilisateurs</div>
                <div class="stat-number"><?php echo number_format($total_users); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-title">Produits</div>
                <div class="stat-number"><?php echo number_format($total_products); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-title">Commandes</div>
                <div class="stat-number"><?php echo number_format($total_orders); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-title">Revenus</div>
                <div class="stat-number"><?php echo number_format($total_revenue, 2); ?> DT</div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="actions-grid">
            <a href="admin_users.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="action-title">Gérer Utilisateurs</div>
                <div class="action-desc">Ajouter, modifier ou supprimer des utilisateurs</div>
            </a>

            <a href="admin_products.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="action-title">Gérer Produits</div>
                <div class="action-desc">Gérer le catalogue de produits</div>
            </a>

            <a href="admin_orders.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="action-title">Commandes</div>
                <div class="action-desc">Consulter et gérer les commandes</div>
            </a>

            <a href="admin_stats.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="action-title">Statistiques</div>
                <div class="action-desc">Voir les rapports détaillés</div>
            </a>
        </div>
    </div>
</body>
</html>