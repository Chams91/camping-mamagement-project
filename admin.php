<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();

// Only allow admins and super_admins
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit();
}

require 'includes/auth.php';

// Handle activation/deactivation/deletion requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = $_POST['action'];
        $user_id = intval($_POST['user_id']);

        // Prevent admins from performing actions on themselves
        if ($user_id === $_SESSION['user_id'] && $action === 'deactivate') {
            $error_message = "Vous ne pouvez pas désactiver votre propre compte.";
        } elseif ($user_id === $_SESSION['user_id'] && $action === 'delete') {
            $error_message = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            if ($action === 'activate') {
                $result = activateUser($user_id);
                if ($result) {
                    $success_message = "Utilisateur activé avec succès.";
                } else {
                    $error_message = "Échec de l'activation de l'utilisateur.";
                }
            } elseif ($action === 'deactivate') {
                $result = deactivateUser($user_id);
                if ($result) {
                    $success_message = "Utilisateur désactivé avec succès.";
                } else {
                    $error_message = "Échec de la désactivation de l'utilisateur ou vous ne pouvez pas désactiver le dernier super_admin.";
                }
            } elseif ($action === 'delete') {
                $result = deleteUser($user_id);
                if ($result) {
                    $success_message = "Utilisateur supprimé avec succès.";
                } else {
                    $error_message = "Échec de la suppression de l'utilisateur ou vous ne pouvez pas supprimer le dernier super_admin.";
                }
            }
        }
    }
}

// Fetch all users
$users = getAllUsers();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Gestion des Utilisateurs</title>
    <style>
        /* Common Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        header {
            background-color: #2a593d;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #c8e6c9;
        }
        
        .nav-links span {
            color: white;
            font-size: 16px;
            margin-left: 15px;
        }

        /* Container Styles */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2a593d;
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }

        /* Table Styles */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #2a593d;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Button Styles */
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
            transition: background-color 0.3s;
            margin-right: 5px;
        }

        .activate-btn {
            background-color: #4CAF50;
        }

        .activate-btn:hover {
            background-color: #45a049;
        }

        .deactivate-btn {
            background-color: #f44336;
        }

        .deactivate-btn:hover {
            background-color: #da190b;
        }

        .delete-btn {
            background-color: #555555;
        }

        .delete-btn:hover {
            background-color: #333333;
        }

        /* Message Styles */
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .nav-links {
                flex-direction: column;
                gap: 15px;
            }

            .action-btn {
                padding: 6px 12px;
                font-size: 12px;
                margin-bottom: 5px;
            }

            h1 {
                font-size: 28px;
            }

            th, td {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Camping Nature - Admin Panel</div>
            <div class="nav-links">
                <a href="home.php">Accueil</a>
                <a href="reservation.php">Réserver</a>
                
                <?php if ($_SESSION['role'] === 'super_admin'): ?>
                    <a href="superadmin.php" style="background-color: #f44336; padding: 5px 10px; border-radius: 4px;">⚡ Super Admin</a>
                <?php endif; ?>
                
                <a href="logout.php">Déconnexion</a>
                <span>Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Gestion des Utilisateurs</h1>

        <!-- Display Success or Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= $user['is_active'] ? 'Actif' : 'Désactivé' ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="deactivate">
                                        <button type="submit" class="action-btn deactivate-btn" onclick="return confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur ?');">Désactiver</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="action-btn activate-btn">Activer</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete Button (Visible to Admins and Super Admins) -->
                                <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="action-btn delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>