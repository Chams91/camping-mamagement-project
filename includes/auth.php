<?php
require 'config.php';

function registerUser($username, $email, $password, $role = 'user') {
    global $pdo;
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $hashedPassword, $role]);
    } catch (PDOException $e) {
        // Check if error is duplicate entry
        if ($e->errorInfo[1] == 1062) {
            return false;
        }
        throw $e;
    }
}

function loginUser($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: home.php");
        exit();
    }
}

function processReservation($user_id, $arrival_date, $departure_date, $num_people, $accommodation_type) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO reservations 
                              (user_id, arrival_date, departure_date, num_people, accommodation_type) 
                              VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $arrival_date, $departure_date, $num_people, $accommodation_type]);
    } catch (PDOException $e) {
        error_log("Reservation failed: " . $e->getMessage());
        return false;
    }
}

?>