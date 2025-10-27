<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: user.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - To-Do List</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h1>Create Account</h1>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Registration successful! <a href="index.php">Login here</a>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    if($_GET['error'] == 'username_exists') echo "Username already exists!";
                    if($_GET['error'] == 'email_exists') echo "Email already exists!";
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register_process.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="register">Register</button>
            </form>
            
            <p style="text-align: center; margin-top: 15px;">
                Already have an account? <a href="index.php" style="color: #800000;">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>