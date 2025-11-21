<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php'
// Vérifier si client connecté
if(!isset($_SESSION['client_id'])){
    header("Location: login.php");
    exit;
}

// Ajouter au panier
if(isset($_POST['add_to_cart'])){
    $product_id = $_POST['product_id'];
    $quantity   = $_POST['quantity'];

    // Créer panier si n'existe pas
    if(!isset($_SESSION['cart'])){
        $_SESSION['cart'] = [];
    }

    // Ajouter ou mettre à jour quantité
    if(isset($_SESSION['cart'][$product_id])){
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }

    $message = "✓ Produit ajouté au panier !";
}

// Récupérer UNIQUEMENT les produits en promotion
$result = $conn->query("SELECT * FROM produit WHERE promotion = 1 AND prix_promo IS NOT NULL ORDER BY categorie, nom");

// Vérifier si la requête a réussi
if(!$result){
    die("Erreur de requête: " . $conn->error);
}

// Compter items dans le panier
$cart_count = 0;
if(isset($_SESSION['cart'])){
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions - TechHub Tunisia</title>
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
        }

        /* Header */
        .header {
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .top-bar {
            background: rgba(0,0,0,0.2);
            padding: 10px 0;
            font-size: 0.9em;
        }

        .top-bar-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2em;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .cart-btn, .logout-btn, .back-btn {
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

        .cart-btn:hover, .logout-btn:hover, .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .cart-badge {
            background: #ff4757;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }

        /* Alert Message */
        .alert-success {
            max-width: 1400px;
            margin: 20px auto;
            padding: 15px 20px;
            background: rgba(212, 237, 218, 0.95);
            color: #155724;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s;
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Promo Banner */
        .promo-banner {
            max-width: 1400px;
            margin: 30px auto;
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .promo-banner h1 {
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            animation: pulse 2s infinite;
        }

        .promo-banner p {
            font-size: 1.3em;
            opacity: 0.95;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Products Grid */
        .products-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff4757, #ff6348);
        }

        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f8f9fa;
        }

        .product-badge {
            position: absolute;
            top: 20px;
            right: 15px;
            background: #ff4757;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            animation: pulse 2s infinite;
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.4);
        }

        .savings-badge {
            position: absolute;
            top: 20px;
            left: 15px;
            background: #2ecc71;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .product-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            color: #667eea;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            min-height: 50px;
        }

        .product-specs {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
            flex: 1;
        }

        .product-specs i {
            color: #667eea;
            margin-right: 5px;
        }

        .spec-item {
            margin-bottom: 5px;
        }

        .price-container {
            margin-bottom: 15px;
        }

        .product-price {
            font-size: 2em;
            font-weight: bold;
            color: #ff4757;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .price-original {
            font-size: 0.5em;
            color: #999;
            text-decoration: line-through;
        }

        .discount-percentage {
            background: #ff4757;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.5em;
            font-weight: bold;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }

        .add-to-cart-btn {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .add-to-cart-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.4);
        }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
        }

        /* Footer */
        .footer {
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            .header-main {
                flex-direction: column;
                gap: 15px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }

            .promo-banner h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="top-bar">
            <div class="top-bar-content">
                <div><i class="fas fa-shipping-fast"></i> Livraison gratuite à partir de 200 DT</div>
                <div><i class="fas fa-phone"></i> Support: +216 12 345 678</div>
            </div>
        </div>
        <div class="header-main">
            <div class="logo">
                <i class="fas fa-fire"></i>
                Promotions TechHub
            </div>
            <div class="header-actions">
                <span>Bienvenue, <strong><?php echo htmlspecialchars($_SESSION['client_name']); ?></strong></span>
                <a href="index.php" class="back-btn">
                    <i class="fas fa-home"></i>
                    Boutique
                </a>
                <a href="cart.php" class="cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    Panier
                    <?php if($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <?php if(isset($message)): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Promo Banner -->
    <div class="promo-banner">
        <h1><i class="fas fa-fire"></i> PROMOTIONS EXCEPTIONNELLES <i class="fas fa-fire"></i></h1>
        <p>Ne manquez pas nos offres à prix réduits - Stock limité !</p>
    </div>

    <!-- Products -->
    <div class="products-container">
        <div class="products-grid">
            <?php 
            if($result && $result->num_rows > 0):
                while($product = $result->fetch_assoc()): 
                    $prix_normal = $product['prix'];
                    $prix_promo = $product['prix_promo'];
                    $economie = $prix_normal - $prix_promo;
                    $pourcentage = round(($economie / $prix_normal) * 100);
                    
                    // Nettoyer le chemin de l'image
                    $image_name = $product['image'];
                    if(strpos($image_name, '\\') !== false) {
                        $image_name = basename($image_name);
                    }
                    $image_path = 'images/' . $image_name;
            ?>
                <div class="product-card">
                    <div style="position: relative;">
                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                             class="product-image" 
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22280%22 height=%22220%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22280%22 height=%22220%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22sans-serif%22 font-size=%2216%22 fill=%22%23999%22%3EImage non disponible%3C/text%3E%3C/svg%3E';">
                        <span class="product-badge"><i class="fas fa-fire"></i> -<?php echo $pourcentage; ?>%</span>
                        <span class="savings-badge"><i class="fas fa-tag"></i> Économisez <?php echo number_format($economie, 2); ?> DT</span>
                    </div>
                    <div class="product-info">
                        <div class="product-category"><?php echo htmlspecialchars($product['categorie']); ?></div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['nom']); ?></h3>
                        
                        <div class="product-specs">
                            <?php if(!empty($product['processeur'])): ?>
                                <div class="spec-item"><i class="fas fa-microchip"></i> <?php echo htmlspecialchars($product['processeur']); ?></div>
                            <?php endif; ?>
                            <?php if(!empty($product['ram'])): ?>
                                <div class="spec-item"><i class="fas fa-memory"></i> <?php echo htmlspecialchars($product['ram']); ?></div>
                            <?php endif; ?>
                            <?php if(!empty($product['stockage'])): ?>
                                <div class="spec-item"><i class="fas fa-hdd"></i> <?php echo htmlspecialchars($product['stockage']); ?></div>
                            <?php endif; ?>
                            <?php if(!empty($product['ecran'])): ?>
                                <div class="spec-item"><i class="fas fa-desktop"></i> <?php echo htmlspecialchars($product['ecran']); ?></div>
                            <?php endif; ?>
                            <?php if(!empty($product['batterie'])): ?>
                                <div class="spec-item"><i class="fas fa-battery-full"></i> <?php echo htmlspecialchars($product['batterie']); ?></div>
                            <?php endif; ?>
                            <?php if(empty($product['processeur']) && empty($product['ram']) && !empty($product['description'])): ?>
                                <div class="spec-item"><?php echo htmlspecialchars($product['description']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="price-container">
                            <div class="product-price">
                                <?php echo number_format($prix_promo, 2); ?> DT
                                <span class="price-original"><?php echo number_format($prix_normal, 2); ?> DT</span>
                            </div>
                        </div>
                        
                        <?php if($product['stock'] > 0): ?>
                            <form method="post" class="product-actions">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                                <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                    <i class="fas fa-cart-plus"></i>
                                    Profiter
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="text-align: center; padding: 12px; background: #f0f0f0; border-radius: 10px; color: #666;">
                                <i class="fas fa-times-circle"></i> Rupture de stock
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                endwhile;
            else: 
            ?>
                <div class="empty-state">
                    <i class="fas fa-sad-tear"></i>
                    <h3>Aucune promotion disponible</h3>
                    <p>Il n'y a pas de promotions en cours pour le moment.</p>
                    <p style="margin-top: 20px;">
                        <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-arrow-left"></i> Retour à la boutique
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 TechHub Tunisia - Votre boutique high-tech de confiance</p>
        <p style="margin-top: 10px; font-size: 0.9em; opacity: 0.8;">
            <i class="fas fa-shield-alt"></i> Paiement sécurisé | 
            <i class="fas fa-truck"></i> Livraison rapide | 
            <i class="fas fa-undo"></i> Retour gratuit sous 14 jours
        </p>
    </div>
</body>
</html>