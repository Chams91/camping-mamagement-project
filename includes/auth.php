<?php
require 'config.php';

function registerUser($username, $email, $password, $role = 'user') {
    global $pdo;
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
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
        if (!$user['is_active']) {
            // User is deactivated
            return false;
        }

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

/**
 * Fetch all users from the database.
 *
 * @return array|false Returns an array of users or false on failure.
 */
function getAllUsers() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, role, is_active FROM users ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch users: " . $e->getMessage());
        return false;
    }
}

/**
 * Activate a user by setting is_active to 1.
 *
 * @param int $user_id The ID of the user to activate.
 * @return bool Returns true on success, false on failure.
 */
function activateUser($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Failed to activate user: " . $e->getMessage());
        return false;
    }
}

/**
 * Deactivate a user by setting is_active to 0.
 *
 * @param int $user_id The ID of the user to deactivate.
 * @return bool Returns true on success, false on failure.
 */
function deactivateUser($user_id) {
    global $pdo;
    try {
        // Prevent deactivating self
        if ($_SESSION['user_id'] == $user_id) {
            return false;
        }

        // If deactivating a super_admin, ensure at least one remains active
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $role = $stmt->fetchColumn();

        if ($role === 'super_admin') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND is_active = 1");
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count <= 1) {
                // Prevent deactivating the last active super_admin
                return false;
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Failed to deactivate user: " . $e->getMessage());
        return false;
    }
}
?>