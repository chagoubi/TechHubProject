<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php'

$errors = [];
$success = "";

if(isset($_POST['register'])){
    // Récupération et nettoyage des données
    $nom = secure_input($_POST['nom']);
    $prenom = secure_input($_POST['prenom']);
    $email = secure_input($_POST['email']);
    $telephone = secure_input($_POST['telephone']);
    $adresse = secure_input($_POST['adresse']);
    $password = $_POST['motdepasse'];
    $confirm_password = $_POST['confirm_motdepasse'];

    // Validation du nom
    if(empty($nom) || strlen($nom) < 2){
        $errors[] = "Le nom doit contenir au moins 2 caractères";
    }

    // Validation du prénom
    if(empty($prenom) || strlen($prenom) < 2){
        $errors[] = "Le prénom doit contenir au moins 2 caractères";
    }

    // Validation de l'email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "L'adresse email n'est pas valide";
    } else {
        // Vérifier si l'email existe déjà
        $check_email = $conn->prepare("SELECT id FROM client WHERE email=?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        if($result->num_rows > 0){
            $errors[] = "Cet email est déjà utilisé";
        }
    }

    // Validation du téléphone (format tunisien: 8 chiffres)
    if(!preg_match("/^[0-9]{8}$/", $telephone)){
        $errors[] = "Le numéro de téléphone doit contenir 8 chiffres";
    }

    // Validation de l'adresse
    if(empty($adresse) || strlen($adresse) < 10){
        $errors[] = "L'adresse doit contenir au moins 10 caractères";
    }

    // Validation du mot de passe
    if(strlen($password) < 6){
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }

    // Vérification de la confirmation du mot de passe
    if($password !== $confirm_password){
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Si pas d'erreurs, insertion dans la base
    if(empty($errors)){
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO client (nom, prenom, email, motdepasse, telephone, adresse) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nom, $prenom, $email, $hashed_password, $telephone, $adresse);
        
        if($stmt->execute()){
            $success = "Compte créé avec succès ! Redirection...";
            // Redirection après 2 secondes
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Erreur lors de la création du compte: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub Tunisia - Inscription</title>
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
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
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

        .benefits {
            margin-top: 30px;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.95em;
        }

        .benefit-item i {
            font-size: 1.3em;
        }

        .right-section {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 90vh;
            overflow-y: auto;
        }

        .right-section h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 25px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .alert-error ul {
            margin-left: 20px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #2e7d32;
            animation: slideIn 0.5s;
        }

        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.9em;
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

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95em;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input.error {
            border-color: #c33;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.85em;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-weak { background: #f44336; width: 33%; }
        .strength-medium { background: #ff9800; width: 66%; }
        .strength-strong { background: #4caf50; width: 100%; }

        .btn-register {
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
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            color: #666;
            margin-top: 20px;
            font-size: 0.95em;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .left-section {
                display: none;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="logo">
                <i class="fas fa-laptop-code"></i>
                TechHub
            </div>
            <h2>Rejoignez TechHub Tunisia</h2>
            <p>Créez votre compte et profitez de tous nos avantages</p>
            
            <div class="benefits">
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Accès à des milliers de produits high-tech</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Offres exclusives pour les membres</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Suivi de vos commandes en temps réel</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Programme de fidélité et points cadeaux</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Service après-vente de qualité</span>
                </div>
            </div>
        </div>

        <div class="right-section">
            <h2>Créer un compte</h2>
            <p class="subtitle">Remplissez le formulaire pour vous inscrire</p>

            <?php if(!empty($errors)): ?>
                <div class="alert-error">
                    <strong><i class="fas fa-exclamation-triangle"></i> Erreurs de validation :</strong>
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="post" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nom" name="nom" placeholder="Votre nom" required 
                                   value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required
                                   value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Adresse Email *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="exemple@email.com" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="telephone">Numéro de téléphone *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="telephone" name="telephone" placeholder="12345678" maxlength="8" required
                               value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse complète *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea id="adresse" name="adresse" placeholder="Rue, ville, code postal..." required><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="motdepasse">Mot de passe *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="motdepasse" name="motdepasse" placeholder="Min. 6 caractères" required>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthBar"></div>
                            </div>
                            <small id="strengthText"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_motdepasse">Confirmer mot de passe *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_motdepasse" name="confirm_motdepasse" placeholder="Répétez le mot de passe" required>
                        </div>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-register">
                    <i class="fas fa-user-plus"></i> Créer mon compte
                </button>
            </form>

            <div class="login-link">
                Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
            </div>
        </div>
    </div>

    <script>
        // Validation du mot de passe en temps réel
        const passwordInput = document.getElementById('motdepasse');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            strengthBar.className = 'strength-fill';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Faible';
                strengthText.style.color = '#f44336';
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Moyen';
                strengthText.style.color = '#ff9800';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Fort';
                strengthText.style.color = '#4caf50';
            }
        });

        // Validation du téléphone (8 chiffres seulement)
        document.getElementById('telephone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8);
        });
    </script>
</body>
</html>