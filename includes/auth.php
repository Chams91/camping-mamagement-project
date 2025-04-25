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

/**
 * Delete a user from the database.
 *
 * @param int $user_id The ID of the user to delete.
 * @return bool Returns true on success, false on failure.
 */
function deleteUser($user_id) {
    global $pdo;
    try {
        // Prevent deleting self
        if ($_SESSION['user_id'] == $user_id) {
            return false;
        }

        // If deleting a super_admin, ensure at least one remains
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $role = $stmt->fetchColumn();

        if ($role === 'super_admin') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND id != ? AND is_active = 1");
            $stmt->execute([$user_id]);
            $count = $stmt->fetchColumn();

            if ($count < 1) {
                // Prevent deleting the last active super_admin
                return false;
            }
        }

        // Proceed to delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Failed to delete user: " . $e->getMessage());
        return false;
    }
}

/**
 * Change the role of a user.
 *
 * @param int $user_id The ID of the user whose role is to be changed.
 * @param string $new_role The new role to assign ('user', 'admin', 'super_admin').
 * @return bool Returns true on success, false on failure.
 */
function changeUserRole($user_id, $new_role) {
    global $pdo;
    try {
        // Prevent changing own role
        if ($_SESSION['user_id'] == $user_id) {
            return false;
        }

        // Validate new role
        $valid_roles = ['user', 'admin', 'super_admin'];
        if (!in_array($new_role, $valid_roles)) {
            return false;
        }

        // Get current role of the user
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_role = $stmt->fetchColumn();

        // If demoting from super_admin, ensure at least one remains
        if ($current_role === 'super_admin' && $new_role !== 'super_admin') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND is_active = 1");
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count <= 1) {
                // Prevent demoting the last active super_admin
                return false;
            }
        }

        // If promoting to super_admin, ensure the current user is a super_admin
        if ($new_role === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
            return false;
        }

        // Proceed to change the role
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$new_role, $user_id]);
    } catch (PDOException $e) {
        error_log("Failed to change user role: " . $e->getMessage());
        return false;
    }
}

// locations management

/**
 * Fetch all locations from the database.
 *
 * @return array|false Returns an array of locations or false on failure.
 */
function getAllLocations() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM locations ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch locations: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a new location to the database.
 *
 * @param string $type Type of accommodation ('tente', 'caravane', 'chalet').
 * @param float $price_per_night Price per night.
 * @param int $capacity Capacity of the location.
 * @param array $equipments Array of available equipment.
 * @param string $availability_start_date Start date of availability.
 * @param string $availability_end_date End date of availability.
 * @return bool Returns true on success, false on failure.
 */
function addLocation($type, $price_per_night, $capacity, $equipments, $availability_start_date, $availability_end_date) {
    global $pdo;
    try {
        // Convert the equipments array to a comma-separated string
        $equipments_str = implode(',', $equipments);
        
        $stmt = $pdo->prepare("INSERT INTO locations 
            (type, price_per_night, capacity, equipments, availability_start_date, availability_end_date) 
            VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $type, 
            $price_per_night, 
            $capacity, 
            $equipments_str, 
            $availability_start_date, 
            $availability_end_date
        ]);
    } catch (PDOException $e) {
        error_log("Failed to add location: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing location in the database.
 *
 * @param int $id ID of the location to update.
 * @param string $type New type of accommodation.
 * @param float $price_per_night New price per night.
 * @param int $capacity New capacity.
 * @param array $equipments Array of available equipment.
 * @param string $availability_start_date New start date of availability.
 * @param string $availability_end_date New end date of availability.
 * @return bool Returns true on success, false on failure.
 */
function updateLocation($id, $type, $price_per_night, $capacity, $equipments, $availability_start_date, $availability_end_date) {
    global $pdo;
    try {
        // Convert the equipments array to a comma-separated string
        $equipments_str = implode(',', $equipments);
        
        $stmt = $pdo->prepare("UPDATE locations SET 
            type = ?, 
            price_per_night = ?, 
            capacity = ?, 
            equipments = ?, 
            availability_start_date = ?, 
            availability_end_date = ?
            WHERE id = ?");
        return $stmt->execute([
            $type, 
            $price_per_night, 
            $capacity, 
            $equipments_str, 
            $availability_start_date, 
            $availability_end_date,
            $id
        ]);
    } catch (PDOException $e) {
        error_log("Failed to update location: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a location from the database.
 *
 * @param int $id ID of the location to delete.
 * @return bool Returns true on success, false on failure.
 */
function deleteLocation($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Failed to delete location: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a new hébergement to the database.
 *
 * @param string $name Name of the hébergement.
 * @param string $arrival_date Arrival date.
 * @param string $departure_date Departure date.
 * @param float $price Price of the hébergement.
 * @param array $equipments Array of available equipment.
 * @return bool Returns true on success, false on failure.
 */
function addHebergement($name, $arrival_date, $departure_date, $price, $equipments) {
    global $pdo;
    try {
        // Convert the equipments array to a comma-separated string
        $equipments_str = implode(',', $equipments);
        
        $stmt = $pdo->prepare("INSERT INTO hebergements 
            (name, arrival_date, departure_date, price, equipments) 
            VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $name, 
            $arrival_date, 
            $departure_date, 
            $price, 
            $equipments_str
        ]);
    } catch (PDOException $e) {
        error_log("Failed to add hébergement: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing hébergement in the database.
 *
 * @param int $id ID of the hébergement to update.
 * @param string $name New name of the hébergement.
 * @param string $arrival_date New arrival date.
 * @param string $departure_date New departure date.
 * @param float $price New price.
 * @param array $equipments Array of available equipment.
 * @return bool Returns true on success, false on failure.
 */
function updateHebergement($id, $name, $arrival_date, $departure_date, $price, $equipments) {
    global $pdo;
    try {
        // Convert the equipments array to a comma-separated string
        $equipments_str = implode(',', $equipments);
        
        $stmt = $pdo->prepare("UPDATE hebergements SET 
            name = ?, 
            arrival_date = ?, 
            departure_date = ?, 
            price = ?, 
            equipments = ?
            WHERE id = ?");
        return $stmt->execute([
            $name, 
            $arrival_date, 
            $departure_date, 
            $price, 
            $equipments_str,
            $id
        ]);
    } catch (PDOException $e) {
        error_log("Failed to update hébergement: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a hébergement from the database.
 *
 * @param int $id ID of the hébergement to delete.
 * @return bool Returns true on success, false on failure.
 */
function deleteHebergement($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM hebergements WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Failed to delete hébergement: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch all hébergements from the database.
 *
 * @return array|false Returns an array of hébergements or false on failure.
 */
function getAllHebergements() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM hebergements ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch hébergements: " . $e->getMessage());
        return false;
    }
}

?>