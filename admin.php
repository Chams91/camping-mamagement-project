<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Handle user management requests: activate, deactivate, delete, role changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'])) {
    $user_action = $_POST['user_action'];
    $user_id = intval($_POST['user_id']);

    // Prevent actions on self
    if ($user_id === $_SESSION['user_id']) {
        if ($user_action === 'deactivate') {
            $error_message = "Vous ne pouvez pas désactiver votre propre compte.";
        } elseif ($user_action === 'delete') {
            $error_message = "Vous ne pouvez pas supprimer votre propre compte.";
        } elseif ($user_action === 'promote_admin' || $user_action === 'promote_super_admin' || $user_action === 'demote_admin') {
            $error_message = "Vous ne pouvez pas modifier le rôle de votre propre compte.";
        }
    } else {
        switch ($user_action) {
            case 'activate':
                if (activateUser($user_id)) {
                    $success_message = "Utilisateur activé avec succès.";
                } else {
                    $error_message = "Échec de l'activation de l'utilisateur.";
                }
                break;

            case 'deactivate':
                if (deactivateUser($user_id)) {
                    $success_message = "Utilisateur désactivé avec succès.";
                } else {
                    $error_message = "Échec de la désactivation de l'utilisateur ou vous ne pouvez pas désactiver le dernier super_admin.";
                }
                break;

            case 'delete':
                if (deleteUser($user_id)) {
                    $success_message = "Utilisateur supprimé avec succès.";
                } else {
                    $error_message = "Échec de la suppression de l'utilisateur.";
                }
                break;

            case 'promote_admin':
                // Only super_admins can promote to admin
                if ($_SESSION['role'] === 'super_admin') {
                    if (changeUserRole($user_id, 'admin')) {
                        $success_message = "Utilisateur promu en admin avec succès.";
                    } else {
                        $error_message = "Échec de la promotion de l'utilisateur en admin.";
                    }
                } else {
                    $error_message = "Vous n'avez pas les permissions nécessaires pour promouvoir cet utilisateur en admin.";
                }
                break;

            case 'demote_admin':
                // Only super_admins can demote to user
                if ($_SESSION['role'] === 'super_admin') {
                    if (changeUserRole($user_id, 'user')) {
                        $success_message = "Admin rétrogradé en utilisateur avec succès.";
                    } else {
                        $error_message = "Échec de la rétrogradation de l'admin en utilisateur.";
                    }
                } else {
                    $error_message = "Vous n'avez pas les permissions nécessaires pour rétrograder cet admin.";
                }
                break;

            case 'promote_super_admin':
                // Only super_admins can promote to super_admin
                if ($_SESSION['role'] === 'super_admin') {
                    if (changeUserRole($user_id, 'super_admin')) {
                        $success_message = "Utilisateur promu en super admin avec succès.";
                    } else {
                        $error_message = "Échec de la promotion de l'utilisateur en super admin.";
                    }
                } else {
                    $error_message = "Vous n'avez pas les permissions nécessaires pour promouvoir cet utilisateur en super admin.";
                }
                break;

            default:
                $error_message = "Action invalide.";
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------
    // Locations Management
    // -------------------
    if (isset($_POST['location_action'])) {
        $location_action = $_POST['location_action'];

        switch ($location_action) {
            case 'add_location':
                $type = trim($_POST['type']);
                $price_per_night = floatval($_POST['price_per_night']);
                $capacity = intval($_POST['capacity']);
                $equipments = isset($_POST['equipments']) ? $_POST['equipments'] : [];
                $availability_start_date = $_POST['availability_start_date'];
                $availability_end_date = $_POST['availability_end_date'];

                // Basic validation
                if ($type && $price_per_night > 0 && $capacity > 0 && $availability_start_date && $availability_end_date) {
                    if (addLocation($type, $price_per_night, $capacity, $equipments, $availability_start_date, $availability_end_date)) {
                        $_SESSION['success_message'] = "Emplacement ajouté avec succès.";
                    } else {
                        $_SESSION['error_message'] = "Échec de l'ajout de l'emplacement.";
                    }
                } else {
                    $_SESSION['error_message'] = "Veuillez remplir correctement tous les champs pour ajouter un emplacement.";
                }
                break;

            case 'edit_location':
                $id = intval($_POST['id']);
                $type = trim($_POST['type']);
                $price_per_night = floatval($_POST['price_per_night']);
                $capacity = intval($_POST['capacity']);
                $equipments = isset($_POST['equipments']) ? $_POST['equipments'] : [];
                $availability_start_date = $_POST['availability_start_date'];
                $availability_end_date = $_POST['availability_end_date'];

                // Basic validation
                if ($id > 0 && $type && $price_per_night > 0 && $capacity > 0 && $availability_start_date && $availability_end_date) {
                    if (updateLocation($id, $type, $price_per_night, $capacity, $equipments, $availability_start_date, $availability_end_date)) {
                        $_SESSION['success_message'] = "Emplacement mis à jour avec succès.";
                    } else {
                        $_SESSION['error_message'] = "Échec de la mise à jour de l'emplacement.";
                    }
                } else {
                    $_SESSION['error_message'] = "Veuillez remplir correctement tous les champs pour mettre à jour l'emplacement.";
                }
                break;

            case 'delete_location':
                $id = intval($_POST['id']);

                if ($id > 0) {
                    if (deleteLocation($id)) {
                        $_SESSION['success_message'] = "Emplacement supprimé avec succès.";
                    } else {
                        $_SESSION['error_message'] = "Échec de la suppression de l'emplacement.";
                    }
                } else {
                    $_SESSION['error_message'] = "ID d'emplacement invalide pour la suppression.";
                }
                break;

            default:
                $_SESSION['error_message'] = "Action d'emplacement invalide.";
        }

        // Redirect to prevent form resubmission
        header("Location: admin.php");
        exit();
    }

    // -------------------
    // Hébergements Management
    // -------------------
    if (isset($_POST['hebergement_action'])) {
        $hebergement_action = $_POST['hebergement_action'];

        switch ($hebergement_action) {
            case 'add_hebergement':
                $name = trim($_POST['name']);
                $arrival_date = $_POST['arrival_date'];
                $departure_date = $_POST['departure_date'];
                $price = floatval($_POST['price']);
                $equipments = isset($_POST['equipments']) ? $_POST['equipments'] : [];

                // Basic validation
                if ($name && $arrival_date && $departure_date && $price > 0) {
                    if (addHebergement($name, $arrival_date, $departure_date, $price, $equipments)) {
                        $_SESSION['success_message'] = "Hébergement ajouté avec succès.";
                    } else {
                        $_SESSION['error_message'] = "Échec de l'ajout de l'hébergement.";
                    }
                } else {
                    $_SESSION['error_message'] = "Veuillez remplir correctement tous les champs pour ajouter un hébergement.";
                }
                break;

            case 'edit_hebergement':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $arrival_date = $_POST['arrival_date'];
                $departure_date = $_POST['departure_date'];
                $price = floatval($_POST['price']);
                $equipments = isset($_POST['equipments']) ? $_POST['equipments'] : [];

                // Basic validation
                if ($id > 0 && $name && $arrival_date && $departure_date && $price > 0) {
                    if (updateHebergement($id, $name, $arrival_date, $departure_date, $price, $equipments)) {
                        $_SESSION['success_message'] = "Hébergement mis à jour avec succès.";
                    } else {
                        $_SESSION['error_message'] = "Échec de la mise à jour de l'hébergement.";
                    }
                } else {
                    $_SESSION['error_message'] = "Veuillez remplir correctement tous les champs pour mettre à jour l'hébergement.";
                }
                break;

            case 'delete_hebergement':
                $id = intval($_POST['id']);

                if ($id > 0) {
                    if (deleteHebergement($id)) {
                        $_SESSION['success_message'] = "Hébergement supprimé avec succès.";
                    } else {
                        $_SESSION['error_message'] = "Échec de la suppression de l'hébergement.";
                    }
                } else {
                    $_SESSION['error_message'] = "ID d'hébergement invalide pour la suppression.";
                }
                break;

            default:
                $_SESSION['error_message'] = "Action d'hébergement invalide.";
        }

        // Redirect to prevent form resubmission
        header("Location: admin.php");
        exit();
    }
}

// Fetch all users, locations, and hébergements
$users = getAllUsers();
$locations = getAllLocations();
$hebergements = getAllHebergements();

// Retrieve and clear messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
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

        .promote-superadmin-btn {
            background-color: #9c27b0;
        }

        .promote-superadmin-btn:hover {
            background-color: #7b1fa2;
        }

        .demote-admin-btn {
            background-color: #ff9800;
        }

        .demote-admin-btn:hover {
            background-color: #e68900;
        }

        /* Form Styles */
        .form-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f2f2f2;
            border-radius: 8px;
        }

        .form-container label {
            display: block;
            margin-bottom: 5px;
            color: #333333;
            font-weight: bold;
        }

        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container select,
        .form-container input[type="date"] {
            width: 100%;
            padding: 8px 12px;
            margin: 8px 0 16px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container input[type="submit"],
        .form-container button {
            background-color: #2a593d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .form-container input[type="submit"]:hover,
        .form-container button:hover {
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

        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            padding-top: 60px;
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
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

            .form-container input[type="submit"],
            .form-container button {
                width: 100%;
            }
        }
    </style>
    <script>
        // Function to show the edit location form with pre-filled data
        function showEditLocationForm(location) {
            document.getElementById('editLocationModal').style.display = 'block';
            document.getElementById('edit_location_id').value = location.id;
            document.getElementById('edit_type').value = location.type;
            document.getElementById('edit_price_per_night').value = location.price_per_night;
            document.getElementById('edit_capacity').value = location.capacity;

            // Reset all equipment checkboxes
            document.getElementById('edit_equipment_water').checked = false;
            document.getElementById('edit_equipment_electricity').checked = false;
            document.getElementById('edit_equipment_heating').checked = false;

            // Split the equipments string into an array
            var equipments = location.equipments.split(',');

            equipments.forEach(function(item) {
                if (item.trim() === 'water') {
                    document.getElementById('edit_equipment_water').checked = true;
                }
                if (item.trim() === 'electricity') {
                    document.getElementById('edit_equipment_electricity').checked = true;
                }
                if (item.trim() === 'heating') {
                    document.getElementById('edit_equipment_heating').checked = true;
                }
            });

            document.getElementById('edit_availability_start_date').value = location.availability_start_date;
            document.getElementById('edit_availability_end_date').value = location.availability_end_date;
        }

        // Function to hide the edit location form
        function hideEditLocationForm() {
            document.getElementById('editLocationModal').style.display = 'none';
        }

        // Function to show the edit hébergement form with pre-filled data
        function showEditHebergementForm(hebergement) {
            document.getElementById('editHebergementModal').style.display = 'block';
            document.getElementById('edit_hebergement_id').value = hebergement.id;
            document.getElementById('edit_hebergement_name').value = hebergement.name;
            document.getElementById('edit_hebergement_arrival_date').value = hebergement.arrival_date;
            document.getElementById('edit_hebergement_departure_date').value = hebergement.departure_date;
            document.getElementById('edit_hebergement_price').value = hebergement.price;

            // Reset all equipment checkboxes
            document.getElementById('edit_hebergement_equip_wifi').checked = false;
            document.getElementById('edit_hebergement_equip_climatisation').checked = false;

            // Split the equipments string into an array
            var equipments = hebergement.equipments.split(',');

            equipments.forEach(function(item) {
                if (item.trim() === 'wifi') {
                    document.getElementById('edit_hebergement_equip_wifi').checked = true;
                }
                if (item.trim() === 'climatisation') {
                    document.getElementById('edit_hebergement_equip_climatisation').checked = true;
                }
            });
        }

        // Function to hide the edit hébergement form
        function hideEditHebergementForm() {
            document.getElementById('editHebergementModal').style.display = 'none';
        }

        // Close the modals when clicking outside of them
        window.onclick = function(event) {
            var editLocationModal = document.getElementById('editLocationModal');
            var editHebergementModal = document.getElementById('editHebergementModal');
            if (event.target == editLocationModal) {
                editLocationModal.style.display = "none";
            }
            if (event.target == editHebergementModal) {
                editHebergementModal.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Camping Nature - Admin Panel</div>
            <div class="nav-links">
                <a href="home.php">Accueil</a>
                <a href="reservation.php">Réserver</a>    
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
                                <div class="action-buttons">
                                    <!-- Activate/Deactivate Buttons -->
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <?php if ($user['is_active']): ?>
                                            <input type="hidden" name="user_action" value="deactivate">
                                            <button type="submit" class="demote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur ?');">Désactiver</button>
                                        <?php else: ?>
                                            <input type="hidden" name="user_action" value="activate">
                                            <button type="submit" class="promote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir activer cet utilisateur ?');">Activer</button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Role Management Buttons (Visible to Super Admins Only) -->
                                    <?php if ($_SESSION['role'] === 'super_admin'): ?>
                                        <?php if ($user['role'] === 'user'): ?>
                                            <!-- Promote to Admin -->
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="user_action" value="promote_admin">
                                                <button type="submit" class="promote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir promouvoir cet utilisateur en admin ?');">Promouvoir en Admin</button>
                                            </form>

                                            <!-- Promote to Super Admin -->
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="user_action" value="promote_super_admin">
                                                <button type="submit" class="promote-superadmin-btn" onclick="return confirm('Êtes-vous sûr de vouloir promouvoir cet utilisateur en super admin ?');">Promouvoir en Super Admin</button>
                                            </form>
                                        <?php elseif ($user['role'] === 'admin'): ?>
                                            <!-- Demote to User -->
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="user_action" value="demote_admin">
                                                <button type="submit" class="demote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir rétrograder cet admin en utilisateur ?');">Rétrograder en Utilisateur</button>
                                            </form>

                                            <!-- Promote to Super Admin -->
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="user_action" value="promote_super_admin">
                                                <button type="submit" class="promote-superadmin-btn" onclick="return confirm('Êtes-vous sûr de vouloir promouvoir cet admin en super admin ?');">Promouvoir en Super Admin</button>
                                            </form>
                                        <?php elseif ($user['role'] === 'super_admin'): ?>
                                            <!-- Optionally, allow demoting super_admin to admin -->
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="user_action" value="demote_admin">
                                                <button type="submit" class="demote-admin-btn" onclick="return confirm('Êtes-vous sûr de vouloir rétrograder ce super admin en admin ?');">Rétrograder en Admin</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Delete Button -->
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="user_action" value="delete">
                                        <button type="submit" class="delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">Supprimer</button>
                                    </form>
                                </div>
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

        <!-- -----------------------
             Locations Management
             ------------------------ -->
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

                <label>Équipements Disponibles:</label>
                <input type="checkbox" name="equipments[]" value="water" id="equip_water"> <label for="equip_water">Eau</label><br>
                <input type="checkbox" name="equipments[]" value="electricity" id="equip_elec"> <label for="equip_elec">Électricité</label><br>
                <input type="checkbox" name="equipments[]" value="heating" id="equip_heat"> <label for="equip_heat">Chauffage</label><br><br>

                <label for="availability_start_date">Date de Début Disponibilité:</label>
                <input type="date" name="availability_start_date" id="availability_start_date" required>

                <label for="availability_end_date">Date de Fin Disponibilité:</label>
                <input type="date" name="availability_end_date" id="availability_end_date" required>

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
                    <th>Équipements</th>
                    <th>Disponibilité</th>
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
                                <?php
                                    // Convert equipments string to display-friendly format
                                    $equipments_display = '';
                                    if (!empty($location['equipments'])) {
                                        $equipments_array = explode(',', $location['equipments']);
                                        $formatted_equipments = array_map('ucfirst', $equipments_array);
                                        $equipments_display = htmlspecialchars(implode(', ', $formatted_equipments));
                                    }
                                    echo $equipments_display ?: 'Aucun';
                                ?>
                            </td>
                            <td><?= htmlspecialchars($location['availability_start_date']) ?> au <?= htmlspecialchars($location['availability_end_date']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Edit Location Button -->
                                    <button onclick='showEditLocationForm(<?= json_encode($location) ?>)' class="promote-admin-btn">Modifier</button>

                                    <!-- Delete Location Form -->
                                    <form method="POST">
                                        <input type="hidden" name="location_action" value="delete_location">
                                        <input type="hidden" name="id" value="<?= $location['id'] ?>">
                                        <button type="submit" class="delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet emplacement ?');">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Aucun emplacement trouvé.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Edit Location Modal -->
        <div id="editLocationModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="hideEditLocationForm()">&times;</span>
                <h3>Modifier Emplacement</h3>
                <form method="POST">
                    <input type="hidden" name="location_action" value="edit_location">
                    <input type="hidden" name="id" id="edit_location_id">

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

                    <label>Équipements Disponibles:</label>
                    <input type="checkbox" name="equipments[]" value="water" id="edit_equipment_water"> <label for="edit_equipment_water">Eau</label><br>
                    <input type="checkbox" name="equipments[]" value="electricity" id="edit_equipment_electricity"> <label for="edit_equipment_electricity">Électricité</label><br>
                    <input type="checkbox" name="equipments[]" value="heating" id="edit_equipment_heating"> <label for="edit_equipment_heating">Chauffage</label><br><br>

                    <label for="edit_availability_start_date">Date de Début Disponibilité:</label>
                    <input type="date" name="availability_start_date" id="edit_availability_start_date" required>

                    <label for="edit_availability_end_date">Date de Fin Disponibilité:</label>
                    <input type="date" name="availability_end_date" id="edit_availability_end_date" required>

                    <input type="submit" value="Enregistrer les Modifications">
                </form>
            </div>
        </div>

        <!-- -----------------------
             Hébergements Management
             ------------------------ -->
        <h2>Gestion des Hébergements</h2>

        <!-- Add New Hébergement Form -->
        <div class="form-container">
            <h3>Ajouter un Nouvel Hébergement</h3>
            <form method="POST">
                <input type="hidden" name="hebergement_action" value="add_hebergement">
                
                <label for="name">Nom de l'Hébergement:</label>
                <input type="text" name="name" id="name" required>
                
                <label for="arrival_date">Date d'Arrivée:</label>
                <input type="date" name="arrival_date" id="arrival_date" required>
                
                <label for="departure_date">Date de Départ:</label>
                <input type="date" name="departure_date" id="departure_date" required>
                
                <label for="price">Prix (€):</label>
                <input type="number" step="0.01" name="price" id="price" required>
                
                <label>Équipements Disponibles:</label>
                <input type="checkbox" name="equipments[]" value="wifi" id="equip_hebergement_wifi"> <label for="equip_hebergement_wifi">Wifi</label><br>
                <input type="checkbox" name="equipments[]" value="climatisation" id="equip_hebergement_climatisation"> <label for="equip_hebergement_climatisation">Climatisation</label><br><br>
                
                <input type="submit" value="Ajouter Hébergement">
            </form>
        </div>

        <!-- Hébergements Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Date d'Arrivée</th>
                    <th>Date de Départ</th>
                    <th>Prix (€)</th>
                    <th>Équipements</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hebergements): ?>
                    <?php foreach ($hebergements as $hebergement): ?>
                        <tr>
                            <td><?= htmlspecialchars($hebergement['id']) ?></td>
                            <td><?= htmlspecialchars($hebergement['name']) ?></td>
                            <td><?= htmlspecialchars($hebergement['arrival_date']) ?></td>
                            <td><?= htmlspecialchars($hebergement['departure_date']) ?></td>
                            <td><?= htmlspecialchars(number_format($hebergement['price'], 2)) ?></td>
                            <td>
                                <?php
                                    // Convert equipments string to display-friendly format
                                    $equipments_display = '';
                                    if (!empty($hebergement['equipments'])) {
                                        $equipments_array = explode(',', $hebergement['equipments']);
                                        $formatted_equipments = array_map('ucfirst', $equipments_array);
                                        $equipments_display = htmlspecialchars(implode(', ', $formatted_equipments));
                                    }
                                    echo $equipments_display ?: 'Aucun';
                                ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Edit Hébergement Button -->
                                    <button onclick='showEditHebergementForm(<?= json_encode($hebergement) ?>)' class="promote-admin-btn">Modifier</button>

                                    <!-- Delete Hébergement Form -->
                                    <form method="POST">
                                        <input type="hidden" name="hebergement_action" value="delete_hebergement">
                                        <input type="hidden" name="id" value="<?= $hebergement['id'] ?>">
                                        <button type="submit" class="delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet hébergement ?');">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Aucun hébergement trouvé.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Edit Hébergement Modal -->
        <div id="editHebergementModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="hideEditHebergementForm()">&times;</span>
                <h3>Modifier Hébergement</h3>
                <form method="POST">
                    <input type="hidden" name="hebergement_action" value="edit_hebergement">
                    <input type="hidden" name="id" id="edit_hebergement_id">

                    <label for="edit_hebergement_name">Nom de l'Hébergement:</label>
                    <input type="text" name="name" id="edit_hebergement_name" required>

                    <label for="edit_hebergement_arrival_date">Date d'Arrivée:</label>
                    <input type="date" name="arrival_date" id="edit_hebergement_arrival_date" required>

                    <label for="edit_hebergement_departure_date">Date de Départ:</label>
                    <input type="date" name="departure_date" id="edit_hebergement_departure_date" required>

                    <label for="edit_hebergement_price">Prix (€):</label>
                    <input type="number" step="0.01" name="price" id="edit_hebergement_price" required>

                    <label>Équipements Disponibles:</label>
                    <input type="checkbox" name="equipments[]" value="wifi" id="edit_hebergement_equip_wifi"> <label for="edit_hebergement_equip_wifi">Wifi</label><br>
                    <input type="checkbox" name="equipments[]" value="climatisation" id="edit_hebergement_equip_climatisation"> <label for="edit_hebergement_equip_climatisation">Climatisation</label><br><br>

                    <input type="submit" value="Enregistrer les Modifications">
                </form>
            </div>
        </div>
    </div>
</body>
</html>