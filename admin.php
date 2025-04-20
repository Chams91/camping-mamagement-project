<?php
session_start();

// Only allow admins and super_admins
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        /* Navigation Bar Styles */
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

        /* Page Content Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
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
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Camping Nature</div>
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
        <h1>Admin Panel</h1>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <!-- Empty table body -->
            </tbody>
        </table>
    </div>
</body>
</html>