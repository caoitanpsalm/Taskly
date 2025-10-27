<?php
session_start();
require_once 'connect.php';

if(isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Check if username already exists
    $check_username = $con->prepare("SELECT id FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result = $check_username->get_result();
    
    if($result->num_rows > 0) {
        header("Location: register.php?error=username_exists");
        exit();
    }
    
    // Check if email already exists
    $check_email = $con->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if($result->num_rows > 0) {
        header("Location: register.php?error=email_exists");
        exit();
    }
    
    // Insert new user
    $stmt = $con->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if($stmt->execute()) {
        header("Location: register.php?success=1");
    } else {
        echo "Error: " . $con->error;
    }
}
?>