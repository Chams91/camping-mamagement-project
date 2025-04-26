<?php
require 'includes/auth.php';
redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arrival_date = $_POST['arrival_date'];
    $departure_date = $_POST['departure_date'];
    $num_people = $_POST['num_people'];
    $accommodation_type = $_POST['accommodation_type'];
    
    //$reservation_success = processReservation($_SESSION['user_id'], $arrival_date, $departure_date, $num_people, $accommodation_type);
    
    if ($reservation_success) {
        header("Location: confirmation.php");
        exit();
    } else {
        $error = "Désolé, la réservation n'a pas pu être complétée. Veuillez réessayer.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver votre séjour</title>
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

        .error-message {
            color: #d32f2f;
            text-align: center;
            margin-bottom: 20px;
        }
        .paiement input[type="radio"] {
    display: none;
}

.paiement label {
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

.paiement label:hover {
    border-color: #007bff;
    background: #e9f5ff;
}

.paiement input[type="radio"]:checked + label {
    border-color: #007bff;
    background: #e9f5ff;
    font-weight: bold;
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

            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="reservation-box">
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

                <div class="section">
                    <h2>🏕️ Type d'hébergement</h2>
                    <div class="logement">
                        <input type="radio" id="tent" name="accommodation_type" value="tent" checked>
                        <label for="tent">Tente<br><small>À partir de 30DT/nuit</small></label>

                        <input type="radio" id="caravan" name="accommodation_type" value="caravan">
                        <label for="caravan">Emplacement Caravane<br><small>À partir de 40DT/nuit</small></label>

                        <input type="radio" id="mobile_home" name="accommodation_type" value="mobile_home">
                        <label for="mobile_home">Mobil-home<br><small>À partir de 100DT/nuit</small></label>
                    </div>
                </div>
                <div class="section">
                <div class="section">
    <h2>🧾 Services supplémentaires</h2>
    <label><input type="checkbox" name="extras[]" value="velo"> Location de vélo</label>
    <label><input type="checkbox" name="extras[]" value="barbecue"> Barbecue</label>
    <label><input type="checkbox" name="extras[]" value="randonnée"> Randonnée accompagnée</label>
    <label><input type="checkbox" name="extras[]" value="sport"> Activités sportives</label>
    <label><input type="checkbox" name="extras[]" value="repas"> Repas inclus</label>
</div>
    <h2>💳 Mode de paiement</h2>
    <div class="logement">
        <input type="radio" id="credit_card" name="payment_method" value="carte" checked>
        <label for="credit_card">Carte bancaire</label>

        <input type="radio" id="cash" name="payment_method" value="cash">
        <label for="cash">Paiement à l'arrivée</label>
    </div>
</div>

                <button type="submit" class="btn">Vérifier la disponibilité</button>
                <button type="reset" class="btn">annuler</button>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('arrival_date').addEventListener('change', function() {
            const arrivalDate = new Date(this.value);
            const nextDay = new Date(arrivalDate);
            nextDay.setDate(arrivalDate.getDate() + 1);
            
            const departureInput = document.getElementById('departure_date');
            departureInput.min = nextDay.toISOString().split('T')[0];
            
            if (new Date(departureInput.value) < nextDay) {
                departureInput.value = '';
            }
        });
    </script>
</body>
</html>