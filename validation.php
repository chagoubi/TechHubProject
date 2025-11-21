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

// Vérifier si panier vide
if(!isset($_SESSION['cart']) || empty($_SESSION['cart'])){
    header("Location: panier.php");
    exit;
}

$client_id = $_SESSION['client_id'];

// Récupérer infos client
$client_query = $conn->prepare("SELECT * FROM client WHERE id = ?");
$client_query->bind_param("i", $client_id);
$client_query->execute();
$client = $client_query->get_result()->fetch_assoc();

// Générer numéro de commande unique
$order_number = 'TH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

// Récupérer produits du panier
$cart_items = [];
$subtotal = 0;

if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])){
    $product_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', array_map('intval', $product_ids));
    
    $result = $conn->query("SELECT * FROM produit WHERE id IN ($ids_string)");
    
    while($product = $result->fetch_assoc()){
        $quantity = $_SESSION['cart'][$product['id']];
        $prix = $product['prix'];
        
        if($product['promotion'] == 1 && !empty($product['prix_promo'])){
            $prix = $product['prix_promo'];
        }
        
        $item_total = $prix * $quantity;
        $subtotal += $item_total;
        
        $cart_items[] = [
            'nom' => $product['nom'],
            'prix' => $prix,
            'quantity' => $quantity,
            'total' => $item_total
        ];
    }
}

$livraison = $subtotal >= 200 ? 0 : 15;
$total = $subtotal + $livraison;
$date_commande = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - <?php echo $order_number; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            padding: 30px 20px;
        }

        .success-animation {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.6s;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: #4caf50;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: scaleIn 0.6s ease-out;
        }

        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success-text {
            color: white;
            margin-top: 20px;
        }

        .success-text h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .success-text p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .invoice-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .invoice-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: fadeInUp 0.6s;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .invoice-header {
            background: #667eea;
            color: white;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .invoice-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -5%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .invoice-header-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .company-info {
            flex: 1;
        }

        .company-logo {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .company-details {
            opacity: 0.95;
            line-height: 1.8;
            font-size: 0.95em;
        }

        .invoice-number {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 15px;
            text-align: right;
            backdrop-filter: blur(10px);
        }

        .invoice-number h2 {
            font-size: 1.5em;
            margin-bottom: 5px;
        }

        .invoice-number p {
            opacity: 0.95;
            font-size: 0.9em;
        }

        .invoice-body {
            padding: 40px;
        }

        .client-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }

        .client-info h3 {
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
        }

        .client-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }

        .detail-item i {
            color: #667eea;
            width: 20px;
        }

        .detail-item strong {
            color: #555;
            min-width: 80px;
        }

        .products-section {
            margin: 30px 0;
        }

        .section-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }

        .products-table thead {
            background: #667eea;
            color: white;
        }

        .products-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .products-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }

        .products-table tbody tr:last-child {
            border-bottom: none;
        }

        .products-table tbody tr:hover {
            background: #f8f9fa;
        }

        .products-table td {
            padding: 15px;
            color: #333;
        }

        .product-name {
            font-weight: 600;
            color: #333;
        }

        .summary-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
            border: 2px solid #e0e0e0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1.1em;
            color: #333;
        }

        .summary-row.total {
            border-top: 3px solid #667eea;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 1.6em;
            font-weight: bold;
            color: #667eea;
        }

        .free-badge {
            background: #4caf50;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
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

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .footer-note {
            text-align: center;
            padding: 30px;
            color: #999;
            border-top: 1px solid #e0e0e0;
            margin-top: 30px;
        }

        .footer-note i {
            color: #ff4757;
            margin: 0 5px;
        }

        .loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            z-index: 9999;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .invoice-header-content {
                flex-direction: column;
                gap: 20px;
            }

            .invoice-number {
                width: 100%;
                text-align: left;
            }

            .client-details {
                grid-template-columns: 1fr;
            }

            .actions {
                grid-template-columns: 1fr;
            }

            .products-table {
                font-size: 0.9em;
            }

            .products-table th,
            .products-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Génération du PDF...</p>
    </div>

    <div class="success-animation" id="successAnim">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <div class="success-text">
            <h1>Commande Validée !</h1>
            <p>Votre commande a été enregistrée avec succès</p>
        </div>
    </div>

    <div class="invoice-wrapper">
        <div class="invoice-container" id="invoice">
            <!-- Header -->
            <div class="invoice-header">
                <div class="invoice-header-content">
                    <div class="company-info">
                        <div class="company-logo">
                            <i class="fas fa-laptop-code"></i>
                            TechHub Tunisia
                        </div>
                        <div class="company-details">
                            <p>📍 Avenue Habib Bourguiba, Tunis 1000</p>
                            <p>📞 +216 71 123 456</p>
                            <p>✉️ contact@techhub.tn</p>
                        </div>
                    </div>
                    <div class="invoice-number">
                        <h2><?php echo $order_number; ?></h2>
                        <p>📅 <?php echo $date_commande; ?></p>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="invoice-body">
                <!-- Client Info -->
                <div class="client-info">
                    <h3><i class="fas fa-user-circle"></i> Informations Client</h3>
                    <div class="client-details">
                        <div class="detail-item">
                            <i class="fas fa-user"></i>
                            <span><strong>Nom:</strong> <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <span><strong>Email:</strong> <?php echo htmlspecialchars($client['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-phone"></i>
                            <span><strong>Téléphone:</strong> <?php echo htmlspecialchars($client['telephone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong>Adresse:</strong> <?php echo htmlspecialchars($client['adresse']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="products-section">
                    <h3 class="section-title"><i class="fas fa-shopping-bag"></i> Détails de la commande</h3>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th style="text-align: center;">Quantité</th>
                                <th style="text-align: right;">Prix Unitaire</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cart_items as $item): ?>
                            <tr>
                                <td class="product-name"><?php echo htmlspecialchars($item['nom']); ?></td>
                                <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                                <td style="text-align: right;"><?php echo number_format($item['prix'], 2); ?> DT</td>
                                <td style="text-align: right;"><strong><?php echo number_format($item['total'], 2); ?> DT</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="summary-section">
                    <div class="summary-row">
                        <span>Sous-total</span>
                        <span><strong><?php echo number_format($subtotal, 2); ?> DT</strong></span>
                    </div>
                    <div class="summary-row">
                        <span>Frais de livraison</span>
                        <span>
                            <?php if($livraison == 0): ?>
                                <span class="free-badge">✓ GRATUITE</span>
                            <?php else: ?>
                                <strong><?php echo number_format($livraison, 2); ?> DT</strong>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="summary-row total">
                        <span>Total à payer</span>
                        <span><?php echo number_format($total, 2); ?> DT</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="actions" id="actions">
                    <button onclick="downloadPDF()" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i>
                        Télécharger PDF
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i>
                        Retour à l'accueil
                    </a>
                </div>

                <!-- Footer -->
                <div class="footer-note">
                    <p>Merci pour votre confiance <i class="fas fa-heart"></i></p>
                    <p style="margin-top: 10px; font-size: 0.9em;">
                        Pour toute question: contact@techhub.tn | +216 71 123 456
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function downloadPDF() {
            const loading = document.getElementById('loading');
            const actions = document.getElementById('actions');
            const successAnim = document.getElementById('successAnim');
            
            loading.classList.add('active');
            actions.style.display = 'none';
            successAnim.style.display = 'none';

            try {
                const { jsPDF } = window.jspdf;
                const invoice = document.getElementById('invoice');
                
                await new Promise(resolve => setTimeout(resolve, 200));
                
                const canvas = await html2canvas(invoice, {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    backgroundColor: '#ffffff',
                    windowWidth: 900
                });

                const imgData = canvas.toDataURL('image/jpeg', 0.95);
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                const imgWidth = 210;
                const pageHeight = 297;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                if (imgHeight <= pageHeight) {
                    pdf.addImage(imgData, 'JPEG', 0, 0, imgWidth, imgHeight);
                } else {
                    let heightLeft = imgHeight;
                    let position = 0;
                    
                    pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft > 0) {
                        position = heightLeft - imgHeight;
                        pdf.addPage();
                        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }
                }

                pdf.save('Facture_<?php echo $order_number; ?>.pdf');
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la génération du PDF. Veuillez réessayer.');
            } finally {
                loading.classList.remove('active');
                actions.style.display = 'grid';
                successAnim.style.display = 'block';
            }
        }

        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s';
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>