<?php
session_start();
// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: user.php");
    exit();
}

$con = new mysqli("localhost", "root", "", "todo_db");
$error = '';

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Check if fields are empty
    if(empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
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
                $error = "Invalid password";
            }
        } else {
            $error = "User not found";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Taskly</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1>Login to Taskly</h1>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 15px;">
                Don't have an account? <a href="register.php" style="color: #800000;">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>