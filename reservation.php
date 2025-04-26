<?php
require 'includes/config.php';
require 'includes/auth.php';
redirectIfNotLoggedIn();

// Initialize variables
$error = '';
$success = '';

// Handle reservation creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    // Sanitize and validate input
    $item_type = $_POST['item_type'];
    $start_date = $_POST['arrival_date'];
    $end_date = $_POST['departure_date'];
    $num_people = intval($_POST['num_people']);
    $payment_method = $_POST['payment_method'];

    // Validate dates
    if ($start_date > $end_date) {
        $error = "La date de départ doit être après la date d'arrivée.";
    } else {
        // Determine item_type and corresponding ID
        if ($item_type === 'location') {
            $location_id = intval($_POST['location_id']);
            $hebergement_id = null;
            // Fetch price_per_night from locations table
            $stmt = $pdo->prepare("SELECT price_per_night FROM locations WHERE id = ?");
            $stmt->execute([$location_id]);
            $location = $stmt->fetch();
            if (!$location) {
                $error = "Type de location sélectionné invalide.";
            } else {
                $price_per_night = floatval($location['price_per_night']);
            }
        } elseif ($item_type === 'hebergement') {
            $hebergement_id = intval($_POST['hebergement_id']);
            $location_id = null;
            // Fetch price_per_night from hebergements table
            $stmt = $pdo->prepare("SELECT price FROM hebergements WHERE id = ?");
            $stmt->execute([$hebergement_id]);
            $hebergement = $stmt->fetch();
            if (!$hebergement) {
                $error = "Type d'hébergement sélectionné invalide.";
            } else {
                $price_per_night = floatval($hebergement['price']);
            }
        } else {
            $error = "Type d'item invalide.";
        }

        if (empty($error)) {
            // Calculate number of nights
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $num_nights = $interval->days;

            if ($num_nights <= 0) {
                $error = "La durée du séjour doit être d'au moins une nuit.";
            } else {
                // Calculate total_price
                $total_price = $price_per_night * $num_nights;

                // Insert into reservations table
                $stmt = $pdo->prepare("INSERT INTO reservations 
                    (user_id, item_type, location_id, hebergement_id, start_date, end_date, num_people, total_price, payment_method, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $result = $stmt->execute([
                    $_SESSION['user_id'],
                    $item_type,
                    $location_id,
                    $hebergement_id,
                    $start_date,
                    $end_date,
                    $num_people,
                    $total_price,
                    $payment_method
                ]);

                if ($result) {
                    // Redirect to avoid form resubmission
                    header("Location: reservation.php?success=1");
                    exit();
                } else {
                    $error = "Échec de la réservation. Veuillez réessayer.";
                }
            }
        }
    }
}

// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $reservation_id = intval($_POST['reservation_id']);
    // Verify that the reservation belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
    $stmt->execute([$reservation_id, $_SESSION['user_id']]);
    $reservation = $stmt->fetch();

    if ($reservation) {
        // Only allow cancellation if reservation is pending
        if ($reservation['status'] === 'pending') {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            if ($stmt->execute([$reservation_id])) {
                // Redirect with success message
                header("Location: reservation.php?cancel=1");
                exit();
            } else {
                $error = "Échec de l'annulation de la réservation.";
            }
        } else {
            $error = "Vous ne pouvez pas annuler une réservation confirmée ou déjà annulée.";
        }
    } else {
        $error = "Réservation invalide ou vous n'êtes pas autorisé à la modifier.";
    }
}

// Fetch locations and hebergements for selection in the form
$locations = [];
$hebergements = [];

// Fetch locations
$stmt = $pdo->query("SELECT id, type, price_per_night, capacity FROM locations");
$locations = $stmt->fetchAll();

// Fetch hebergements
$stmt = $pdo->query("SELECT id, name, price FROM hebergements");
$hebergements = $stmt->fetchAll();

// Fetch user's reservations
$stmt = $pdo->prepare("SELECT r.*, 
    l.type AS location_type, l.price_per_night, 
    h.name AS hebergement_name, h.price AS hebergement_price 
    FROM reservations r 
    LEFT JOIN locations l ON r.location_id = l.id 
    LEFT JOIN hebergements h ON r.hebergement_id = h.id 
    WHERE r.user_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$user_reservations = $stmt->fetchAll();

// Handle success messages from redirects
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Réservation effectuée avec succès et en attente de confirmation.";
}

if (isset($_GET['cancel']) && $_GET['cancel'] == 1) {
    $success = "Réservation annulée avec succès.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Réservations - Camping Nature</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            margin: 0;
            padding: 0;
            color: #495057;
        }

        /* Navigation Bar Styles (Consistent with home.php) */
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
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
        }

        h1 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 10px;
            color: #2a593d;
        }

        .subtitle {
            text-align: center;
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }

        /* Feedback Message Styles */
        .error-message {
            color: #d32f2f;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Reservation Form Styles */
        .reservation-box {
            display: flex;
            flex-direction: column;
            gap: 20px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .section {
            display: flex;
            flex-direction: column;
        }

        .section h2 {
            margin-bottom: 10px;
            color: #2a593d;
        }

        .section label {
            font-weight: 600;
            margin: 8px 0 5px;
        }

        input[type="date"], select, input[type="radio"] + label {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        /* Payment Method Styling */
        .paiement {
            display: flex;
            flex-direction: row;
            gap: 15px;
        }

        .paiement label {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .paiement input[type="radio"] {
            display: none;
        }

        .paiement input[type="radio"]:checked + label {
            border-color: #007bff;
            background: #e9f5ff;
            font-weight: bold;
        }

        .paiement label:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }

        /* Submit Button */
        input[type="submit"] {
            padding: 12px 20px;
            background: #2a593d;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            align-self: flex-start;
        }

        input[type="submit"]:hover {
            background: #1e4628;
        }

        /* Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

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

        .confirm-btn { background-color: #4CAF50; }
        .confirm-btn:hover { background-color: #45a049; }

        .cancel-btn { background-color: #f44336; }
        .cancel-btn:hover { background-color: #da190b; }

        .delete-btn { background-color: #555555; }
        .delete-btn:hover { background-color: #333333; }

        /* Form Container */
        .form-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f2f2f2;
            border-radius: 8px;
        }

        .form-container h3 {
            margin-bottom: 15px;
            color: #2a593d;
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

        /* Responsive Styles */
        @media (max-width: 768px) {
            th, td {
                padding: 8px;
                font-size: 14px;
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

            .form-container input[type="submit"],
            .form-container button {
                font-size: 14px;
                padding: 10px 16px;
            }
        }

        /* Flex Container for Layout */
        .main-content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            min-height: calc(100vh - 70px); /* Adjust based on header height */
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Camping Nature</div>
            <div class="nav-links">
                <a href="home.php">Accueil</a>
                <a href="reservation.php">Réserver</a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin')): ?>
                        <a href="admin.php#manage_users">Espace Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Déconnexion</a>
                    <span>Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <?php else: ?>
                    <a href="login.php">Connexion</a>
                    <a href="register.php">Créer un compte</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="main-content">
        <div class="container">
            <h1>Réserver votre séjour</h1>
            <p class="subtitle">Choisissez vos dates et votre type d'hébergement</p>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" class="reservation-box">
                <input type="hidden" name="create_reservation" value="1">
                <div class="section">
                    <h2>📋 Type de réservation</h2>
                    <label>
                        <input type="radio" name="item_type" value="location" required> Location
                    </label>
                    <label>
                        <input type="radio" name="item_type" value="hebergement" required> Hébergement
                    </label>
                </div>

                <div class="section">
                    <h2>📅 Dates de séjour</h2>
                    <label for="arrival_date">Date d'arrivée</label>
                    <input type="date" id="arrival_date" name="arrival_date" required min="<?= date('Y-m-d') ?>">

                    <label for="departure_date">Date de départ</label>
                    <input type="date" id="departure_date" name="departure_date" required>
                </div>

                <div class="section">
                    <h2>🧑‍🤝‍🧑 Voyageurs</h2>
                    <label for="num_people">Nombre de personnes</label>
                    <select id="num_people" name="num_people" required>
                        <option value="1">1 personne</option>
                        <option value="2">2 personnes</option>
                        <option value="3">3 personnes</option>
                        <option value="4">4 personnes</option>
                        <option value="5">5 personnes</option>
                        <option value="6">6 personnes</option>
                    </select>
                </div>

                <div class="section" id="item_selection" style="display:none;">
                    <!-- Populated via JavaScript based on item_type -->
                </div>

                <div class="section">
                    <h2>💳 Mode de paiement</h2>
                    <div class="paiement">
                        <input type="radio" id="credit_card" name="payment_method" value="credit_card" checked>
                        <label for="credit_card">Carte bancaire</label>

                        <input type="radio" id="cash" name="payment_method" value="cash">
                        <label for="cash">Paiement à l'arrivée</label>
                    </div>
                </div>

                <input type="submit" value="Réserver">
            </form>

            <h2>Vos Réservations</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type d'Item</th>
                            <th>Item</th>
                            <th>Dates</th>
                            <th>Nombre de personnes</th>
                            <th>Prix Total (€)</th>
                            <th>Méthode de paiement</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($user_reservations) === 0): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;">Vous n'avez aucune réservation.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($user_reservations as $res): ?>
                                <tr>
                                    <td><?= htmlspecialchars($res['id']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($res['item_type'])) ?></td>
                                    <td>
                                        <?php 
                                            if ($res['item_type'] === 'location') {
                                                echo htmlspecialchars(ucfirst($res['location_type']));
                                            } else {
                                                echo htmlspecialchars($res['hebergement_name']);
                                            }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($res['start_date']) ?> au <?= htmlspecialchars($res['end_date']) ?></td>
                                    <td><?= htmlspecialchars($res['num_people']) ?></td>
                                    <td><?= number_format($res['total_price'], 2) ?></td>
                                    <td>
                                        <?php 
                                            if ($res['payment_method'] === 'credit_card') {
                                                echo "Carte Bancaire";
                                            } else {
                                                echo "Espèces";
                                            }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars(ucfirst($res['status'])) ?></td>
                                    <td>
                                        <?php if ($res['status'] === 'pending'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="cancel_reservation" value="1">
                                                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($res['id']) ?>">
                                                <input type="submit" value="Annuler" class="action-btn cancel-btn" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?');">
                                            </form>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // JavaScript to dynamically show location or hebergement selection based on item_type
        document.querySelectorAll('input[name="item_type"]').forEach((elem) => {
            elem.addEventListener('change', function(event) {
                var itemType = event.target.value;
                var itemSelection = document.getElementById('item_selection');
                if (itemType === 'location') {
                    var html = `
                        <h2>📍 Sélection de la Location</h2>
                        <label for="location_id">Type de Location:</label>
                        <select name="location_id" id="location_id" required>
                            <option value="">--Sélectionnez--</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc['id']) ?>"><?= htmlspecialchars(ucfirst($loc['type'])) ?> - <?= htmlspecialchars(number_format($loc['price_per_night'], 2)) ?>€/nuit</option>
                            <?php endforeach; ?>
                        </select>
                    `;
                    itemSelection.innerHTML = html;
                    itemSelection.style.display = 'block';
                } else if (itemType === 'hebergement') {
                    var html = `
                        <h2>🏠 Sélection de l'Hébergement</h2>
                        <label for="hebergement_id">Type d'Hébergement:</label>
                        <select name="hebergement_id" id="hebergement_id" required>
                            <option value="">--Sélectionnez--</option>
                            <?php foreach ($hebergements as $heb): ?>
                                <option value="<?= htmlspecialchars($heb['id']) ?>"><?= htmlspecialchars($heb['name']) ?> - <?= htmlspecialchars(number_format($heb['price'], 2)) ?>€/nuit</option>
                            <?php endforeach; ?>
                        </select>
                    `;
                    itemSelection.innerHTML = html;
                    itemSelection.style.display = 'block';
                }
            });
        });

        // Trigger the change event on page load if an item_type is already selected
        window.addEventListener('load', function() {
            var selected = document.querySelector('input[name="item_type"]:checked');
            if (selected) {
                selected.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>