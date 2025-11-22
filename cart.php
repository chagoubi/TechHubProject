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

// Mettre à jour quantité
if(isset($_POST['update_quantity'])){
    $product_id = $_POST['product_id'];
    $new_quantity = max(1, intval($_POST['quantity']));
    
    if(isset($_SESSION['cart'][$product_id])){
        $_SESSION['cart'][$product_id] = $new_quantity;
    }
}

// Supprimer produit
if(isset($_POST['remove_item'])){
    $product_id = $_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
}

// Vider le panier
if(isset($_POST['clear_cart'])){
    $_SESSION['cart'] = [];
}

// Récupérer produits du panier
$cart_items = [];
$total = 0;
$subtotal = 0;
$livraison = 0;

if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
    $product_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', array_map('intval', $product_ids));
    
    $result = $conn->query("SELECT * FROM produit WHERE id IN ($ids_string)");
    
    while($product = $result->fetch_assoc()){
        $quantity = $_SESSION['cart'][$product['id']];
        $prix = $product['prix'];
        
        // Appliquer promotion si existe
        if($product['promotion'] == 1 && !empty($product['prix_promo'])){
            $prix = $product['prix_promo'];
        }
        
        $item_total = $prix * $quantity;
        $subtotal += $item_total;
        
        $cart_items[] = [
            'id' => $product['id'],
            'nom' => $product['nom'],
            'categorie' => $product['categorie'],
            'image' => $product['image'],
            'prix' => $prix,
            'prix_original' => $product['prix'],
            'promotion' => $product['promotion'],
            'quantity' => $quantity,
            'stock' => $product['stock'],
            'total' => $item_total
        ];
    }
}

// Calculer livraison (gratuite si > 200 DT)
$livraison = $subtotal >= 200 ? 0 : 15;
$total = $subtotal + $livraison;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - TechHub Tunisia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .nav-btn.history {
            background: rgba(76, 175, 80, 0.3);
            border-color: rgba(76, 175, 80, 0.5);
        }

        .nav-btn.history:hover {
            background: rgba(76, 175, 80, 0.5);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        /* Page Title */
        .page-title {
            grid-column: 1 / -1;
            font-size: 2.5em;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeInDown 0.5s;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cart Items */
        .cart-items {
            animation: fadeInLeft 0.5s;
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .cart-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .cart-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .cart-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scaleY(0);
            transition: transform 0.3s;
        }

        .cart-item:hover::before {
            transform: scaleY(1);
        }

        .item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .item-category {
            color: #667eea;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .item-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .promo-badge {
            background: #ff4757;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75em;
            font-weight: bold;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .item-price-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .item-price {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        .item-price.promo {
            color: #ff4757;
        }

        .price-original {
            font-size: 0.9em;
            color: #999;
            text-decoration: line-through;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 25px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: #667eea;
            color: white;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .quantity-btn:hover {
            background: #764ba2;
            transform: scale(1.1);
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 1.1em;
        }

        .item-total {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
        }

        .remove-btn {
            background: #ff4757;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            font-size: 0.9em;
        }

        .remove-btn:hover {
            background: #ee5a6f;
            transform: scale(1.05);
        }

        /* Summary */
        .cart-summary {
            animation: fadeInRight 0.5s;
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .summary-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            color: #666;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row:last-of-type {
            border-bottom: 2px solid #667eea;
            margin-bottom: 15px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.8em;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
        }

        .free-shipping {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .shipping-progress {
            background: #f0f0f0;
            height: 8px;
            border-radius: 10px;
            margin: 15px 0;
            overflow: hidden;
        }

        .shipping-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .clear-btn {
            width: 100%;
            padding: 12px;
            background: transparent;
            color: #ff4757;
            border: 2px solid #ff4757;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .clear-btn:hover {
            background: #ff4757;
            color: white;
        }

        /* Empty Cart */
        .empty-cart {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .empty-cart i {
            font-size: 5em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-cart h2 {
            color: #666;
            margin-bottom: 10px;
        }

        .empty-cart p {
            color: #999;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: relative;
                top: 0;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .nav-actions {
                flex-wrap: wrap;
                justify-content: center;
            }

            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 15px;
            }

            .item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
            }

            .item-image {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-laptop-code"></i>
                TechHub Tunisia
            </div>
            <div class="nav-actions">
                <a href="index.php" class="nav-btn">
                    <i class="fas fa-shopping-bag"></i>
                    Boutique
                </a>
                <a href="historique.php" class="nav-btn history">
                    <i class="fas fa-history"></i>
                    Historique
                </a>
            </div>
        </div>
    </div>

    <!-- Container -->
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-shopping-cart"></i>
            Mon Panier
            <?php if(!empty($cart_items)): ?>
                <span style="font-size: 0.5em; color: #667eea;">(<?php echo count($cart_items); ?> article<?php echo count($cart_items) > 1 ? 's' : ''; ?>)</span>
            <?php endif; ?>
        </h1>

        <?php if(empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Votre panier est vide</h2>
                <p>Découvrez nos produits et ajoutez-les à votre panier</p>
                <a href="index.php" class="checkout-btn" style="max-width: 300px; margin: 0 auto;">
                    <i class="fas fa-store"></i>
                    Commencer mes achats
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach($cart_items as $item): 
                    $image_name = basename($item['image']);
                    $image_path = 'images/' . $image_name;
                ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($item['nom']); ?>" 
                             class="item-image"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22120%22 height=%22120%22/%3E%3C/svg%3E';">
                        
                        <div class="item-details">
                            <div>
                                <div class="item-header">
                                    <div>
                                        <div class="item-category"><?php echo htmlspecialchars($item['categorie']); ?></div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['nom']); ?></div>
                                    </div>
                                    <?php if($item['promotion'] == 1): ?>
                                        <span class="promo-badge"><i class="fas fa-fire"></i> PROMO</span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-price-section">
                                    <span class="item-price <?php echo $item['promotion'] == 1 ? 'promo' : ''; ?>">
                                        <?php echo number_format($item['prix'], 2); ?> DT
                                    </span>
                                    <?php if($item['promotion'] == 1): ?>
                                        <span class="price-original"><?php echo number_format($item['prix_original'], 2); ?> DT</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="item-actions">
                            <form method="post" class="quantity-control">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="update_quantity" class="quantity-btn" 
                                        onclick="this.form.quantity.value = Math.max(1, parseInt(this.form.quantity.value) - 1);">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                       class="quantity-input" readonly>
                                <button type="submit" name="update_quantity" class="quantity-btn"
                                        onclick="this.form.quantity.value = Math.min(<?php echo $item['stock']; ?>, parseInt(this.form.quantity.value) + 1);">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </form>

                            <div class="item-total"><?php echo number_format($item['total'], 2); ?> DT</div>

                            <form method="post">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="remove-btn">
                                    <i class="fas fa-trash"></i>
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="cart-summary">
                <div class="summary-card">
                    <div class="summary-title">
                        <i class="fas fa-receipt"></i>
                        Récapitulatif
                    </div>

                    <?php if($subtotal < 200): 
                        $remaining = 200 - $subtotal;
                        $progress = ($subtotal / 200) * 100;
                    ?>
                        <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9em;">
                            <i class="fas fa-info-circle"></i>
                            Plus que <strong><?php echo number_format($remaining, 2); ?> DT</strong> pour la livraison gratuite !
                        </div>
                        <div class="shipping-progress">
                            <div class="shipping-progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                        </div>
                    <?php else: ?>
                        <div class="free-shipping">
                            <i class="fas fa-truck"></i> Livraison GRATUITE !
                        </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Sous-total</span>
                        <span><strong><?php echo number_format($subtotal, 2); ?> DT</strong></span>
                    </div>

                    <div class="summary-row">
                        <span>Livraison</span>
                        <span style="<?php echo $livraison == 0 ? 'color: #4caf50; font-weight: bold;' : ''; ?>">
                            <?php echo $livraison == 0 ? 'GRATUITE' : number_format($livraison, 2) . ' DT'; ?>
                        </span>
                    </div>

                    <div class="summary-total">
                        <span>Total</span>
                        <span><?php echo number_format($total, 2); ?> DT</span>
                    </div>

                    <a href="validation.php" class="checkout-btn">
                        <i class="fas fa-check-circle"></i>
                        Valider ma commande
                    </a>

                    <form method="post">
                        <button type="submit" name="clear_cart" class="clear-btn"
                                onclick="return confirm('Êtes-vous sûr de vouloir vider le panier ?');">
                            <i class="fas fa-trash-alt"></i>
                            Vider le panier
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>