<?php
session_start();

// Vérification si admin connecté
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// Connexion à la base de données
require_once 'config.php'
// Traitement des actions (Supprimer, Modifier)
if(isset($_GET['action']) && isset($_GET['id'])){
    $id = intval($_GET['id']);
    
    if($_GET['action'] == 'delete'){
        $stmt = $conn->prepare("DELETE FROM client WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_users.php?success=deleted");
        exit();
    }
}

// Récupération de tous les utilisateurs
$query = "SELECT * FROM client ORDER BY id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub - Gestion Utilisateurs</title>
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

        /* Container principal */
        .container {
            max-width: 1400px;
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

        /* Alert messages */
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

        /* Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
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

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        thead th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        thead th:first-child {
            border-radius: 12px 0 0 0;
        }

        thead th:last-child {
            border-radius: 0 12px 0 0;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.3s;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        tbody td {
            padding: 18px 15px;
            color: #1f2937;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1em;
        }

        .user-details h4 {
            font-size: 0.95em;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .user-details p {
            font-size: 0.85em;
            color: #6b7280;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-phone {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-gov {
            background: #fef3c7;
            color: #92400e;
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
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

        /* Stats mini cards */
        .mini-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .mini-stat {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mini-stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
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
            font-size: 1.8em;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .mini-stat-content p {
            font-size: 0.85em;
            color: #6b7280;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 20px;
            }

            .page-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo">
                <i class="fas fa-users-cog"></i> Gestion Utilisateurs
            </div>
        </div>
        <a href="admin_dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Retour au Dashboard
        </a>
    </div>

    <!-- Container principal -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Liste des Utilisateurs</h1>
        </div>

        <?php if(isset($_GET['success'])): ?>
        <div class="alert">
            <i class="fas fa-check-circle"></i>
            <span>Action effectuée avec succès!</span>
        </div>
        <?php endif; ?>

        <!-- Mini statistiques -->
        <div class="mini-stats">
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo $result->num_rows; ?></h3>
                    <p>Total Clients</p>
                </div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="mini-stat-content">
                    <h3><?php echo $result->num_rows; ?></h3>
                    <p>Actifs</p>
                </div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="mini-stat-content">
                    <h3>0</h3>
                    <p>Nouveaux (7j)</p>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher un utilisateur...">
                </div>
            </div>

            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Gouvernorat</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['nom'], 0, 1)); ?>
                                </div>
                                <div class="user-details">
                                    <h4><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></h4>
                                    <p>Client depuis <?php echo date('Y'); ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge badge-phone">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['telephone']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-gov">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['gouvernorat']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn-action btn-edit" onclick="viewUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                                <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Fonction de recherche
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#usersTable tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        // Confirmation de suppression
        function confirmDelete(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')) {
                window.location.href = `admin_users.php?action=delete&id=${id}`;
            }
        }

        // Voir détails utilisateur
        function viewUser(id) {
            alert(`Fonctionnalité "Voir détails" pour l'utilisateur #${id} - À implémenter`);
        }
    </script>
</body>
</html>