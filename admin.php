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

// Initialize messages
$success_message = '';
$error_message = '';

// Handle activation/deactivation/deletion/role change requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = $_POST['action'];
        $user_id = intval($_POST['user_id']);

        // Prevent admins from performing actions on themselves
        if ($user_id === $_SESSION['user_id']) {
            if ($action === 'deactivate') {
                $error_message = "Vous ne pouvez pas désactiver votre propre compte.";
            } elseif ($action === 'delete') {
                $error_message = "Vous ne pouvez pas supprimer votre propre compte.";
            } elseif ($action === 'promote_admin' || $action === 'promote_super_admin' || $action === 'demote_admin') {
                $error_message = "Vous ne pouvez pas modifier le rôle de votre propre compte.";
            }
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
            } elseif ($action === 'promote_admin') {
                // Only super_admins can promote to admin
                if ($_SESSION['role'] === 'super_admin') {
                    $result = changeUserRole($user_id, 'admin');
                    if ($result) {
                        $success_message = "Utilisateur promu en admin avec succès.";
                    } else {
                        $error_message = "Échec de la promotion de l'utilisateur en admin.";
                    }
                } else {
                    $error_message = "Vous n'avez pas les permissions nécessaires pour promouvoir cet utilisateur en admin.";
                }
            } elseif ($action === 'demote_admin') {
                // Only super_admins can demote to user
                if ($_SESSION['role'] === 'super_admin') {
                    $result = changeUserRole($user_id, 'user');
                    if ($result) {
                        $success_message = "Admin rétrogradé en utilisateur avec succès.";
                    } else {
                        $error_message = "Échec de la rétrogradation de l'admin en utilisateur.";
                    }
                } else {
                    $error_message = "Vous n'avez pas les permissions nécessaires pour rétrograder cet admin.";
                }
            } elseif ($action === 'promote_super_admin') {
                // Only super_admins can promote to super_admin
                if ($_SESSION['role'] === 'super_admin') {
                    $result = changeUserRole($user_id, 'super_admin');
                    if ($result) {
                        $success_message = "Utilisateur promu en super admin avec succès.";
                    } else {
                        $error_message = "Échec de la promotion de l'utilisateur en super admin.";
                    }
                } else {
                    $error_message = "Vous n'avez pas les permissions nécessaires pour promouvoir cet utilisateur en super admin.";
                }
            }
        }
    }
}

// Handle location management requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location_action'])) {
    $location_action = $_POST['location_action'];

    if ($location_action === 'add_location') {
        $type = $_POST['type'];
        $price_per_night = floatval($_POST['price_per_night']);
        $capacity = intval($_POST['capacity']);

        if ($type && $price_per_night > 0 && $capacity > 0) {
            $result = addLocation($type, $price_per_night, $capacity);
            if ($result) {
                $success_message = "Emplacement ajouté avec succès.";
            } else {
                $error_message = "Échec de l'ajout de l'emplacement.";
            }
        } else {
            $error_message = "Veuillez remplir correctement tous les champs pour ajouter un emplacement.";
        }
    } elseif ($location_action === 'edit_location') {
        $id = intval($_POST['id']);
        $type = $_POST['type'];
        $price_per_night = floatval($_POST['price_per_night']);
        $capacity = intval($_POST['capacity']);

        if ($id > 0 && $type && $price_per_night > 0 && $capacity > 0) {
            $result = updateLocation($id, $type, $price_per_night, $capacity);
            if ($result) {
                $success_message = "Emplacement mis à jour avec succès.";
            } else {
                $error_message = "Échec de la mise à jour de l'emplacement.";
            }
        } else {
            $error_message = "Veuillez remplir correctement tous les champs pour mettre à jour l'emplacement.";
        }
    } elseif ($location_action === 'delete_location') {
        $id = intval($_POST['id']);

        if ($id > 0) {
            $result = deleteLocation($id);
            if ($result) {
                $success_message = "Emplacement supprimé avec succès.";
            } else {
                $error_message = "Échec de la suppression de l'emplacement.";
            }
        } else {
            $error_message = "ID d'emplacement invalide pour la suppression.";
        }
    }
}

// Fetch all users and locations
$users = getAllUsers();
$locations = getAllLocations();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Gestion</title>
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

        h1, h2 {
            color: #2a593d;
            font-weight: bold;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 20px;
            text-align: center;
        }

        h2 {
            font-size: 24px;
            margin-top: 40px;
            margin-bottom: 20px;
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
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
            margin-right: 5px;
            transition: background-color 0.3s;
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

        .promote-admin-btn {
            background-color: #2196F3;
        }

        .promote-admin-btn:hover {
            background-color: #0b7dda;
        }

        .demote-admin-btn {
            background-color: #ff9800;
        }

        .demote-admin-btn:hover {
            background-color: #e68900;
        }

        .promote-superadmin-btn {
            background-color: #9c27b0;
        }

        .promote-superadmin-btn:hover {
            background-color: #7b1fa2;
        }

        /* Form Styles */
        .form-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f2f2f2;
            border-radius: 8px;
        }

        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container select {
            width: 100%;
            padding: 8px 12px;
            margin: 8px 0 16px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container input[type="submit"] {
            background-color: #2a593d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .form-container input[type="submit"]:hover {
            background-color: #1e462a;
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
                padding: 6px 10px;
                font-size: 12px;
                margin-bottom: 5px;
            }

            h1 {
                font-size: 28px;
            }

            h2 {
                font-size: 20px;
            }

            th, td {
                padding: 10px;
                font-size: 14px;
            }

            .form-container input[type="submit"] {
                width: 100%;
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
                <span style="color:white;">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Gestion des Utilisateurs</h1>

        <!-- Display Success or Error Messages -->
        <?php if ($success_message): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Users Management Section -->
        <h2>Liste des Utilisateurs</h2>
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

                                <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                    <?php if ($user['role'] === 'user'): ?>
                                        <!-- Promote to Admin -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="promote_admin">
                                            <button type="submit" class="action-btn promote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir promouvoir cet utilisateur en admin ?');">Promouvoir en Admin</button>
                                        </form>
                                        
                                        <!-- Promote to Super Admin -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="promote_super_admin">
                                            <button type="submit" class="action-btn promote-superadmin-btn" onclick="return confirm('Êtes-vous sûr de vouloir promouvoir cet utilisateur en super admin ?');">Promouvoir en Super Admin</button>
                                        </form>
                                    <?php elseif ($user['role'] === 'admin'): ?>
                                        <!-- Demote to User -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="demote_admin">
                                            <button type="submit" class="action-btn demote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir rétrograder cet admin en utilisateur ?');">Rétrograder en Utilisateur</button>
                                        </form>

                                        <!-- Promote to Super Admin -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="promote_super_admin">
                                            <button type="submit" class="action-btn promote-superadmin-btn" onclick="return confirm('Êtes-vous sûr de vouloir promouvoir cet admin en super admin ?');">Promouvoir en Super Admin</button>
                                        </form>
                                    <?php elseif ($user['role'] === 'super_admin'): ?>
                                        <!-- Optionally, allow demoting super_admin to admin -->
                                        <!-- Ensure at least one super_admin remains -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="demote_admin">
                                            <button type="submit" class="action-btn demote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir rétrograder ce super admin en admin ?');">Rétrograder en Admin</button>
                                        </form>
                                    <?php endif; ?>
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

        <!-- Locations Management Section -->
        <h2>Gestion des Emplacements</h2>

        <!-- Add New Location Form -->
        <div class="form-container">
            <h3>Ajouter un Nouvel Emplacement</h3>
            <form method="POST">
                <input type="hidden" name="location_action" value="add_location">
                
                <label for="type">Type d'Emplacement:</label>
                <select name="type" id="type" required>
                    <option value="">--Sélectionnez--</option>
                    <option value="tente">Tente</option>
                    <option value="caravane">Caravane</option>
                    <option value="chalet">Chalet</option>
                </select>

                <label for="price_per_night">Prix par Nuit (€):</label>
                <input type="number" step="0.01" name="price_per_night" id="price_per_night" required>

                <label for="capacity">Capacité:</label>
                <input type="number" name="capacity" id="capacity" required>

                <input type="submit" value="Ajouter Emplacement">
            </form>
        </div>

        <!-- Locations Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Prix par Nuit (€)</th>
                    <th>Capacité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($locations): ?>
                    <?php foreach ($locations as $location): ?>
                        <tr>
                            <td><?= htmlspecialchars($location['id']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($location['type'])) ?></td>
                            <td><?= htmlspecialchars(number_format($location['price_per_night'], 2)) ?></td>
                            <td><?= htmlspecialchars($location['capacity']) ?></td>
                            <td>
                                <!-- Edit Location Button -->
                                <button onclick="showEditForm(<?= $location['id'] ?>, '<?= htmlspecialchars($location['type']) ?>', <?= htmlspecialchars($location['price_per_night']) ?>, <?= htmlspecialchars($location['capacity']) ?>);" class="action-btn promote-admin-btn">Modifier</button>

                                <!-- Delete Location Form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="location_action" value="delete_location">
                                    <input type="hidden" name="id" value="<?= $location['id'] ?>">
                                    <button type="submit" class="action-btn delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet emplacement ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Aucun emplacement trouvé.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Edit Location Modal -->
        <div id="editModal" class="form-container" style="display:none;">
            <h3>Modifier Emplacement</h3>
            <form method="POST">
                <input type="hidden" name="location_action" value="edit_location">
                <input type="hidden" name="id" id="edit_id">

                <label for="edit_type">Type d'Emplacement:</label>
                <select name="type" id="edit_type" required>
                    <option value="">--Sélectionnez--</option>
                    <option value="tente">Tente</option>
                    <option value="caravane">Caravane</option>
                    <option value="chalet">Chalet</option>
                </select>

                <label for="edit_price_per_night">Prix par Nuit (€):</label>
                <input type="number" step="0.01" name="price_per_night" id="edit_price_per_night" required>

                <label for="edit_capacity">Capacité:</label>
                <input type="number" name="capacity" id="edit_capacity" required>

                <input type="submit" value="Enregistrer les Modifications">
                <button type="button" onclick="hideEditForm();" class="action-btn deactivate-btn" style="background-color: #f44336;">Annuler</button>
            </form>
        </div>
    </div>

    <script>
        // Function to show the edit form with pre-filled data
        function showEditForm(id, type, price, capacity) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_type').value = type;
            document.getElementById('edit_price_per_night').value = price;
            document.getElementById('edit_capacity').value = capacity;
        }

        // Function to hide the edit form
        function hideEditForm() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>