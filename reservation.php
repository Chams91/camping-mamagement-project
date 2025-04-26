<?php
require 'includes/config.php';
require 'includes/auth.php';
redirectIfNotLoggedIn();

// Initialize variables
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    $success = "Réservation effectuée avec succès et en attente de confirmation.";
                } else {
                    $error = "Échec de la réservation. Veuillez réessayer.";
                }
            }
        }
    }
}

// Handle reservation cancellation (user can delete their own reservations)
if (isset($_GET['cancel'])) {
    $reservation_id = intval($_GET['cancel']);
    // Verify that the reservation belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
    $stmt->execute([$reservation_id, $_SESSION['user_id']]);
    $reservation = $stmt->fetch();

    if ($reservation) {
        // Only allow cancellation if reservation is not already confirmed or cancelled
        if ($reservation['status'] === 'pending') {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            if ($stmt->execute([$reservation_id])) {
                $success = "Réservation annulée avec succès.";
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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Réservations</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #495057;
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

        /* Your existing reservation styles */
        .main-content {
            display: flex;
            justify-content: center;
            padding: 20px;
            min-height: calc(100vh - 70px);
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 700px;
            width: 100%;
            margin: 20px 0;
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

        .reservation-box {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section {
            display: flex;
            flex-direction: column;
        }

        .section label {
            font-weight: 600;
            margin: 8px 0 5px;
        }

        input[type="date"], select {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .logement {
            display: flex;
            gap: 15px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .logement input[type="radio"] {
            display: none;
        }

        .logement label {
            flex: 1 1 30%;
            border: 2px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            text-align: center;
        }

        .logement label:hover {
            border-color: #00a344;
            background: #f0fdf4;
        }

        .logement input[type="radio"]:checked + label {
            border-color: #00a344;
            background: #f0fdf4;
            font-weight: bold;
        }

        .btn {
            background: #00a344;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
        }

        .btn:hover {
            background: #008f3d;
        }

        .error-message { color: red; }
        .success-message { color: green; }
        
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
                        <a href="admin.php">Espace Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Déconnexion</a>
                    <span style="color:white;">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
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
                <div class="section">
                    <h2>📋 Type d'Item</h2>
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
                    <!-- This will be populated via JavaScript based on item_type -->
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
            <?php if (count($user_reservations) === 0): ?>
                <p>Vous n'avez aucune réservation.</p>
            <?php else: ?>
                <table border="1">
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
                        <?php foreach ($user_reservations as $res): ?>
                            <tr>
                                <td><?= htmlspecialchars($res['id']) ?></td>
                                <td><?= htmlspecialchars($res['item_type']) ?></td>
                                <td>
                                    <?php 
                                        if ($res['item_type'] === 'location') {
                                            echo htmlspecialchars($res['location_type']);
                                        } else {
                                            echo htmlspecialchars($res['hebergement_name']);
                                        }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($res['start_date']) ?> au <?= htmlspecialchars($res['end_date']) ?></td>
                                <td><?= htmlspecialchars($res['num_people']) ?></td>
                                <td><?= number_format($res['total_price'], 2) ?></td>
                                <td><?= htmlspecialchars($res['payment_method']) ?></td>
                                <td><?= htmlspecialchars($res['status']) ?></td>
                                <td>
                                    <?php if ($res['status'] === 'pending'): ?>
                                        <a href="reservation.php?cancel=<?= htmlspecialchars($res['id']) ?>" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?')">Annuler</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
                                <option value="<?= htmlspecialchars($loc['id']) ?>"><?= htmlspecialchars(ucfirst($loc['type'])) ?> - <?= htmlspecialchars($loc['price_per_night']) ?>€/nuit</option>
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
                                <option value="<?= htmlspecialchars($heb['id']) ?>"><?= htmlspecialchars($heb['name']) ?> - <?= htmlspecialchars($heb['price']) ?>€/nuit</option>
                            <?php endforeach; ?>
                        </select>
                    `;
                    itemSelection.innerHTML = html;
                    itemSelection.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>