<?php
session_start();
require_once 'connect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if(isset($_GET['id'])) {
    $task_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Delete task (only if it belongs to current user)
    $stmt = $con->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    
    if($stmt->execute()) {
        header("Location: user.php?message=Task deleted successfully");
    } else {
        header("Location: user.php?error=Error deleting task");
    }
    exit();
} else {
    header("Location: user.php");
    exit();
}
?>