<?php
session_start();
require 'db.php';

// Signup Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $vet_number = $_POST['vet_number'];
    $farm_id = $_POST['farm_id'];

    // Validate password match
    if ($password !== $confirm_password) {
        header("Location: signup.php?error=Passwords do not match");
        exit();
    }

    try {
        // Check if username exists
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            header("Location: signup.php?error=Username already exists");
            exit();
        }

        // Verify VET number
        $query = "SELECT * FROM authorized_vets WHERE vet_number = ? AND is_used = FALSE";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $vet_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            header("Location: signup.php?error=Invalid or already used VET number");
            exit();
        }

        // Verify farm exists
        $query = "SELECT farm_id FROM farms WHERE farm_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            header("Location: signup.php?error=Invalid Farm ID");
            exit();
        }

        // Start transaction
        $conn->begin_transaction();

        // Insert new user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'Manager'; // Default role
        $query = "INSERT INTO users (username, password_hash, farm_id, role, vet_number) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssiss", $username, $password_hash, $farm_id, $role, $vet_number);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting user: " . $stmt->error);
        }

        // Mark VET number as used
        $query = "UPDATE authorized_vets SET is_used = TRUE WHERE vet_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $vet_number);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating VET number: " . $stmt->error);
        }

        $conn->commit();
        header("Location: login.php?success=Account created successfully");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: signup.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verify username and password
    $query = "SELECT user_id, username, password_hash, farm_id, role, vet_number 
              FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['farm_id'] = $user['farm_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['vet_number'] = $user['vet_number'];
        header("Location: dashboard.php");
        exit();
    } else {
        // Redirect back to login with error message
        header("Location: login.php?error=Invalid username or password");
        exit();
    }
}
?>
