<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

// Vérifier si client connecté
if(!isset($_SESSION['client_id'])){
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['client_id'];

// Récupérer le nom du client
$client_query = $conn->prepare("SELECT nom, prenom FROM client WHERE id = ?");
$client_query->bind_param("i", $client_id);
$client_query->execute();
$client_info = $client_query->get_result()->fetch_assoc();

// Filtres
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête
$query = "SELECT * FROM commande WHERE client_id = ?";
$params = array($client_id);
$types = "i";

// Filtre par date
if($date_filter == 'today'){
    $query .= " AND DATE(date_commande) = CURDATE()";
} elseif($date_filter == 'week'){
    $query .= " AND date_commande >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif($date_filter == 'month'){
    $query .= " AND date_commande >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

// Filtre par statut
if($status_filter != 'all'){
    $query .= " AND statut = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Recherche
if(!empty($search)){
    $query .= " AND numero_commande LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$query .= " ORDER BY date_commande DESC";

// Préparer et exécuter
$stmt = $conn->prepare($query);
if(count($params) > 0){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$commandes = $stmt->get_result();

// Statistiques
$stats_query = $conn->prepare("SELECT 
    COUNT(*) as total_commandes,
    COALESCE(SUM(total), 0) as total_depense,
    SUM(CASE WHEN statut = 'En cours' THEN 1 ELSE 0 END) as en_cours,
    SUM(CASE WHEN statut = 'Livrée' THEN 1 ELSE 0 END) as livrees
    FROM commande WHERE client_id = ?");
$stats_query->bind_param("i", $client_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Commandes - TechHub Tunisia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 50px;
        }

        .header {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 1.8em;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .page-title {
            font-size: 2.5em;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            color: white;
        }

        .stat-icon.orders { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.total { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.pending { background: linear-gradient(135deg, #ffc371 0%, #ff5f6d 100%); }
        .stat-icon.delivered { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .stat-info h3 {
            font-size: 2em;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 0.95em;
        }

        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .filters-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .filter-group label {
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-filter {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-reset {
            padding: 12px 25px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-reset:hover {
            background: #667eea;
            color: white;
        }

        .orders-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s;
        }

        .order-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-number {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-date {
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge.en-cours {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.livree {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.annulee {
            background: #f8d7da;
            color: #721c24;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 0.85em;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .detail-value {
            font-size: 1.1em;
            color: #333;
            font-weight: 600;
        }

        .order-products {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .order-products strong {
            color: #333;
            margin-bottom: 10px;
            display: block;
        }

        .product-line {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }

        .product-line:last-child {
            border-bottom: none;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state i {
            font-size: 5em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.8em;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .order-header {
                flex-direction: column;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-laptop-code"></i>
                TechHub Tunisia
            </div>
            <div class="nav-actions">
                <span>Bienvenue, <strong><?php echo htmlspecialchars($client_info['prenom']); ?></strong></span>
                <a href="index.php" class="nav-btn">
                    <i class="fas fa-shopping-bag"></i>
                    Boutique
                </a>
                <a href="cart.php" class="nav-btn">
                    <i class="fas fa-shopping-cart"></i>
                    Panier
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-history"></i>
                Historique de mes Commandes
            </h1>
            <p class="page-subtitle">Suivez l'état de vos commandes et consultez votre historique d'achats</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_commandes']; ?></h3>
                    <p>Commandes totales</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_depense'], 0); ?> DT</h3>
                    <p>Total dépensé</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['en_cours']; ?></h3>
                    <p>En cours</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon delivered">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['livrees']; ?></h3>
                    <p>Livrées</p>
                </div>
            </div>
        </div>

        <div class="filters-section">
            <h2 class="filters-title">
                <i class="fas fa-filter"></i>
                Filtrer les commandes
            </h2>
            <form method="get" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Période</label>
                        <select name="date">
                            <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>Toutes les dates</option>
                            <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                            <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>7 derniers jours</option>
                            <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>30 derniers jours</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-info-circle"></i> Statut</label>
                        <select name="status">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                            <option value="En cours" <?php echo $status_filter == 'En cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="Livrée" <?php echo $status_filter == 'Livrée' ? 'selected' : ''; ?>>Livrée</option>
                            <option value="Annulée" <?php echo $status_filter == 'Annulée' ? 'selected' : ''; ?>>Annulée</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Rechercher</label>
                        <input type="text" name="search" placeholder="Numéro de commande..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i>
                            Rechercher
                        </button>
                        <a href="historique.php" class="btn-reset">
                            <i class="fas fa-redo"></i>
                            Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="orders-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i>
                Mes Commandes
                <?php if($commandes->num_rows > 0): ?>
                    <span style="font-size: 0.7em; color: #667eea;">(<?php echo $commandes->num_rows; ?> résultat<?php echo $commandes->num_rows > 1 ? 's' : ''; ?>)</span>
                <?php endif; ?>
            </h2>

            <?php if($commandes->num_rows > 0): ?>
                <?php while($commande = $commandes->fetch_assoc()): 
                    $details_query = $conn->prepare("SELECT cd.*, p.nom as produit_nom 
                        FROM commande_detail cd
                        JOIN produit p ON cd.produit_id = p.id
                        WHERE cd.commande_id = ?");
                    $details_query->bind_param("i", $commande['id']);
                    $details_query->execute();
                    $details = $details_query->get_result();
                    
                    $status_class = strtolower(str_replace('é', 'e', $commande['statut']));
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-number">
                                    <i class="fas fa-receipt"></i>
                                    <?php echo htmlspecialchars($commande['numero_commande']); ?>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <i class="fas fa-<?php echo $commande['statut'] == 'Livrée' ? 'check-circle' : ($commande['statut'] == 'Annulée' ? 'times-circle' : 'clock'); ?>"></i>
                                <?php echo htmlspecialchars($commande['statut']); ?>
                            </span>
                        </div>

                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label"><i class="fas fa-box"></i> Articles</span>
                                <span class="detail-value"><?php echo $details->num_rows; ?> produit<?php echo $details->num_rows > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><i class="fas fa-coins"></i> Montant Total</span>
                                <span class="detail-value" style="color: #667eea;"><?php echo number_format($commande['total'], 2); ?> DT</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><i class="fas fa-credit-card"></i> Mode de paiement</span>
                                <span class="detail-value"><?php echo htmlspecialchars($commande['mode_paiement']); ?></span>
                            </div>
                        </div>

                        <div class="order-products">
                            <strong><i class="fas fa-shopping-bag"></i> Produits commandés:</strong>
                            <?php 
                            $details->data_seek(0);
                            while($detail = $details->fetch_assoc()): 
                            ?>
                                <div class="product-line">
                                    • <?php echo htmlspecialchars($detail['produit_nom']); ?> 
                                    <span style="color: #999;">x<?php echo $detail['quantite']; ?></span>
                                    <span style="float: right; color: #667eea; font-weight: 600;">
                                        <?php echo number_format($detail['prix_unitaire'] * $detail['quantite'], 2); ?> DT
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="order-actions">
                            <a href="facture.php?order_id=<?php echo $commande['id']; ?>" class="btn-action btn-view">
                                <i class="fas fa-file-invoice"></i>
                                Voir la facture
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucune commande trouvée</h3>
                    <p>Vous n'avez pas encore passé de commande</p>
                    <a href="index.php" class="btn-filter" style="display: inline-flex; margin-top: 20px;">
                        <i class="fas fa-shopping-bag"></i>
                        Découvrir nos produits
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
