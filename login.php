<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php'
if(isset($_POST['login'])){
    $email = secure_input($_POST['email']);
    $pass  = $_POST['motdepasse'];

    $query = $conn->prepare("SELECT * FROM client WHERE email=?");
    $query->bind_param("s",$email);
    $query->execute();
    $result = $query->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        if(password_verify($pass, $user['motdepasse'])){
            $_SESSION['client_id'] = $user['id'];
            $_SESSION['client_name'] = $user['nom'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Mot de passe incorrect";
        }
    } else {
        $error = "Email non trouvé";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub Tunisia - Connexion</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Bouton Admin en haut */
        .admin-access {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn-admin {
            background: rgba(255, 255, 255, 0.95);
            color: #764ba2;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            background: white;
            border-color: #764ba2;
        }

        .btn-admin i {
            font-size: 1.2em;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .left-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo i {
            font-size: 1.2em;
        }

        .left-section h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .left-section p {
            font-size: 1.1em;
            line-height: 1.6;
            opacity: 0.9;
        }

        .features {
            margin-top: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .feature-item i {
            font-size: 1.5em;
            background: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 10px;
        }

        .right-section {
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .right-section h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .alert {
            background: #fee;
            color: #c33;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e0e0e0;
        }

        .divider::before { left: 0; }
        .divider::after { right: 0; }

        .register-link {
            text-align: center;
            color: #666;
            margin-top: 20px;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .left-section {
                display: none;
            }

            .admin-access {
                top: 10px;
                right: 10px;
            }

            .btn-admin {
                padding: 10px 20px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <!-- Bouton d'accès admin -->
    <div class="admin-access">
        <a href="admin_login.php" class="btn-admin">
            <i class="fas fa-user-shield"></i>
            Espace Admin
        </a>
    </div>

    <div class="container">
        <div class="left-section">
            <div class="logo">
                <i class="fas fa-laptop-code"></i>
                TechHub
            </div>
            <h2>Bienvenue sur TechHub Tunisia</h2>
            <p>Votre boutique high-tech de confiance en Tunisie</p>
            
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-shipping-fast"></i>
                    <span>Livraison rapide partout en Tunisie</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Paiement 100% sécurisé</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-headset"></i>
                    <span>Support client 24/7</span>
                </div>
            </div>
        </div>

        <div class="right-section">
            <h2>Connexion</h2>
            <p class="subtitle">Connectez-vous pour accéder à votre compte</p>

            <?php if(isset($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="email">Adresse Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="saisir votre email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="motdepasse">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="motdepasse" name="motdepasse" placeholder="saisir votre mot de passe" required>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <div class="divider">ou</div>

            <div class="register-link">
                Pas encore de compte ? <a href="register.php">Créer un compte</a>
            </div>
        </div>
    </div>
</body>
</html>