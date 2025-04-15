<?php
require 'includes/auth.php'; // Still load auth functions
$logged_in = isLoggedIn(); // Check login status without redirect
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camping Nature</title>
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
            background-image: url('images/background.jpg');
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
        
        .content {
            text-align: left;
            max-width: 600px;
            padding: 40px;
        }

        
        h1 {
            color: #2a593d;
            font-size: 36px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        p {
            color: white;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        h2 {
            color: #2a593d;
            font-size: 24px;
            font-weight: bold;
            margin-top: 30px;
        }
        input{
             background-color: #2a593d;
             color: white;
            font-size: 18px;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
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
                <?php if ($logged_in): ?>
                    <a href="logout.php">Déconnexion</a>
                    <span style="color:white;">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <?php else: ?>
                    <a href="login.php">Connexion</a>
                    <a href="register.php">Créer un compte</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="content">
        <h1>Bienvenue au Camping Nature</h1>
        <p>Découvrez nos emplacements exceptionnels en pleine nature.</p>
        
        <?php if ($logged_in): ?>
            <h2><a href="reservation.php"><input type="submit" value="Réserver maintenant"></a></h2>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div style="background: rgba(255,255,255,0.8); padding: 20px; margin-top: 30px;">
                    <h2 style="color:#2a593d;">Espace Admin</h2>
                    <p style="color:#495057;">Gestion des réservations | Gestion des utilisateurs</p>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div style="margin-top: 30px;">
                <h2><a href="login.php"><input type="submit" value="Se connecter pour réserver"></a></h2>
                <p style="margin-top: 15px;">Pas encore de compte? <a href="register.php" style="color:#2a593d;">Inscrivez-vous</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>