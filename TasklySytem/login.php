<?php
session_start();

// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: user.php");
    exit();
}

$con = new mysqli("localhost", "root", "", "todo_db");

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Check if fields are empty
    if(empty($username) || empty($password)) {
        header("Location: index.php?error=empty");
        exit();
    }
    
    // Check if user exists in database
    $stmt = $con->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to user dashboard
            header("Location: user.php");
            exit();
        } else {
            header("Location: index.php?error=invalid");
            exit();
        }
    } else {
        header("Location: index.php?error=not_found");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>