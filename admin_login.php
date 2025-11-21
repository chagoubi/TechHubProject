<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

// Si admin déjà connecté, redirection vers dashboard
if(isset($_SESSION['admin_id'])){
    header("Location: admin_dashboard.php");
    exit();
}

if(isset($_POST['admin_login'])){
    $email = $_POST['email'];
    $pass  = $_POST['motdepasse'];

    $query = $conn->prepare("SELECT * FROM admin WHERE email=?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if($result->num_rows > 0){
        $admin = $result->fetch_assoc();
        if(password_verify($pass, $admin['motdepasse'])){
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['nom'];
            $_SESSION['admin_email'] = $admin['email'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Mot de passe incorrect";
        }
    } else {
        $error = "Accès refusé - Admin non trouvé";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animations de fond dynamiques */
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            animation: float 20s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(50px, 50px) scale(1.1); }
        }

        /* Bouton retour élégant */
        .back-button {
            position: fixed;
            top: 30px;
            left: 30px;
            z-index: 1000;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            color: white;
            padding: 12px 28px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateX(-8px) scale(1.05);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
        }

        /* Container principal avec glassmorphism */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            border-radius: 32px;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.25),
                        0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            overflow: hidden;
            max-width: 480px;
            width: 100%;
            position: relative;
            z-index: 1;
            transform: translateY(0);
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header moderne */
        .login-header {
            padding: 50px 40px 35px;
            text-align: center;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 3px solid rgba(255, 255, 255, 0.2);
        }

        .admin-icon {
            width: 110px;
            height: 110px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            border: 4px solid rgba(255, 255, 255, 0.4);
            position: relative;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2); }
            50% { transform: scale(1.05); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); }
        }

        .admin-icon i {
            font-size: 3.5em;
            color: white;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        .login-header h1 {
            color: white;
            font-size: 2.2em;
            margin-bottom: 12px;
            font-weight: 700;
            text-shadow: 0 3px 15px rgba(0,0,0,0.2);
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.05em;
            font-weight: 500;
        }

        /* Corps du formulaire */
        .login-body {
            padding: 45px 40px 40px;
            background: white;
        }

        .alert {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #fca5a5;
            color: #dc2626;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 14px;
            animation: shake 0.5s;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .alert i {
            font-size: 1.4em;
        }

        .form-group {
            margin-bottom: 28px;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: #1f2937;
            font-weight: 600;
            font-size: 0.95em;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 22px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.3em;
            z-index: 1;
        }

        .form-group input {
            width: 100%;
            padding: 18px 22px 18px 58px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            color: #1f2937;
            font-size: 1em;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }

        .form-group input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .form-group input:focus {
            outline: none;
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1),
                        0 4px 16px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        /* Bouton de connexion moderne */
        .btn-login {
            width: 100%;
            padding: 19px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
            margin-top: 35px;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 18px 40px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active {
            transform: translateY(-2px) scale(1.01);
        }

        /* Badge sécurité moderne */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
            padding: 14px;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 12px;
            color: #065f46;
            font-size: 0.9em;
            font-weight: 600;
            border: 1px solid #6ee7b7;
        }

        .security-badge i {
            color: #10b981;
            font-size: 1.2em;
        }

        /* Footer moderne */
        .login-footer {
            text-align: center;
            padding: 25px 20px;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border-top: 2px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.9em;
            font-weight: 500;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .back-button {
                top: 20px;
                left: 20px;
            }

            .btn-back {
                padding: 10px 22px;
                font-size: 0.95em;
            }

            .login-container {
                border-radius: 24px;
                margin: 10px;
            }

            .login-header {
                padding: 40px 30px 30px;
            }

            .admin-icon {
                width: 90px;
                height: 90px;
            }

            .admin-icon i {
                font-size: 3em;
            }

            .login-body {
                padding: 35px 28px 30px;
            }

            .login-header h1 {
                font-size: 1.8em;
            }

            .form-group input {
                padding: 16px 20px 16px 54px;
            }
        }

        /* Micro-interactions */
        .form-group {
            animation: fadeInUp 0.5s ease-out backwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .btn-login { animation: fadeInUp 0.5s ease-out 0.3s backwards; }
        .security-badge { animation: fadeInUp 0.5s ease-out 0.4s backwards; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Bouton retour -->
    <div class="back-button">
        <a href="login.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Retour
        </a>
    </div>

    <!-- Container de connexion -->
    <div class="login-container">
        <div class="login-header">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Administration</h1>
            <p>Accès réservé aux administrateurs</p>
        </div>

        <div class="login-body">
            <?php if(isset($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Administrateur
                    </label>
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-at"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="admin@techhub.tn"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="motdepasse">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-key"></i>
                        <input 
                            type="password" 
                            id="motdepasse" 
                            name="motdepasse" 
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>

                <button type="submit" name="admin_login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Connexion Sécurisée
                </button>

                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Connexion cryptée SSL/TLS</span>
                </div>
            </form>
        </div>

        <div class="login-footer">
            <i class="fas fa-copyright"></i> 2024 TechHub Tunisia - Tous droits réservés
        </div>
    </div>
</body>
</html>