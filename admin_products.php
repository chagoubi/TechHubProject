<?php
session_start();

// Vérification si admin connecté
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// Connexion à la base de données
require_once 'db.php';

// Traitement des actions
if(isset($_GET['action']) && isset($_GET['id'])){
    $id = intval($_GET['id']);
    
    if($_GET['action'] == 'delete'){
        $stmt = $conn->prepare("DELETE FROM produit WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_products.php?success=deleted");
        exit();
    }
}

// Récupération de tous les produits avec catégorie
$query = "SELECT p.*, c.nom as categorie_nom 
          FROM produit p 
          LEFT JOIN categorie c ON p.categorie = c.id 
          ORDER BY p.id DESC";
$result = $conn->query($query);

// Statistiques produits
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(stock) as total_stock,
    AVG(prix) as prix_moyen
    FROM produit";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub - Gestion Produits</title>
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

        .btn-add {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .alert {
            background: rgba(16, 185, 129, 0.95);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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

        .products-container {
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

        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-color: #667eea;
        }

        .product-image {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4em;
            color: #9ca3af;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-body {
            padding: 20px;
        }

        .product-category {
            display: inline-block;
            padding: 5px 12px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .product-title {
            font-size: 1.1em;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-desc {
            font-size: 0.85em;
            color: #6b7280;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .product-price {
            font-size: 1.4em;
            font-weight: 700;
            color: #667eea;
        }

        .product-stock {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85em;
            color: #6b7280;
        }

        .stock-badge {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
        }

        .stock-badge.low {
            background: #f59e0b;
        }

        .stock-badge.out {
            background: #ef4444;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .btn-action {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-edit:hover {
            background: #3b82f6;
            color: white;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }

            .page-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">
                <i class="fas fa-boxes"></i> Gestion Produits
            </div>
        </div>
        <a href="admin_dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Retour au Dashboard
        </a>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Catalogue Produits</h1>
            <a href="#" class="btn-add" onclick="alert('Fonctionnalité à implémenter')">
                <i class="fas fa-plus"></i>
                Ajouter un Produit
            </a>
        </div>

        <?php if(isset($_GET['success'])): ?>
        <div class="alert">
            <i class="fas fa-check-circle"></i>
            <span>Action effectuée avec succès!</span>
        </div>
        <?php endif; ?>

        <div class="mini-stats">
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Produits Total</p>
                </div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo number_format($stats['total_stock']); ?></h3>
                    <p>Unités en Stock</p>
                </div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo number_format($stats['prix_moyen'], 0); ?> DT</h3>
                    <p>Prix Moyen</p>
                </div>
            </div>
        </div>

        <div class="products-container">
            <div class="filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher un produit...">
                </div>
                <select class="filter-select" id="categoryFilter">
                    <option value="">Toutes catégories</option>
                    <option value="PC">PC</option>
                    <option value="Smartphone">Smartphone</option>
                    <option value="Power Bank">Power Bank</option>
                    <option value="Imprimante">Imprimante</option>
                    <option value="Ecouteur">Écouteur</option>
                </select>
            </div>

            <div class="products-grid" id="productsGrid">
                <?php while($product = $result->fetch_assoc()): 
                    // Nettoyer le chemin de l'image - EXACTEMENT comme dans index.php
                    $image_name = $product['image'];
                    // Si le chemin contient des backslashes (chemin Windows), extraire le nom du fichier
                    if(strpos($image_name, '\\') !== false) {
                        $image_name = basename($image_name);
                    }
                    $image_path = 'images/' . $image_name;
                ?>
                <div class="product-card" data-category="<?php echo htmlspecialchars($product['categorie_nom']); ?>">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($product['nom']); ?>"
                             onerror="this.parentElement.innerHTML='<i class=\'fas fa-laptop\'></i>';">
                    </div>
                    <div class="product-body">
                        <span class="product-category">
                            <?php echo htmlspecialchars($product['categorie_nom']); ?>
                        </span>
                        <h3 class="product-title"><?php echo htmlspecialchars($product['nom']); ?></h3>
                        <p class="product-desc"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                        
                        <div class="product-footer">
                            <div class="product-price"><?php echo number_format($product['prix'], 2); ?> DT</div>
                            <div class="product-stock">
                                <span class="stock-badge <?php echo $product['stock'] < 3 ? 'low' : ''; ?> <?php echo $product['stock'] == 0 ? 'out' : ''; ?>"></span>
                                Stock: <?php echo $product['stock']; ?>
                            </div>
                        </div>

                        <div class="product-actions">
                            <button class="btn-action btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $product['id']; ?>)">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        // Recherche produits
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        // Filtre par catégorie
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const category = this.value;
            const cards = document.querySelectorAll('.product-card');
            
            cards.forEach(card => {
                if(category === '' || card.dataset.category === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        function confirmDelete(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer ce produit?')) {
                window.location.href = `admin_products.php?action=delete&id=${id}`;
            }
        }

        function editProduct(id) {
            alert(`Fonctionnalité "Modifier produit" pour le produit #${id} - À implémenter`);
        }
    </script>
</body>
</html>