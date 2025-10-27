<?php
session_start();
require_once 'connect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if(isset($_GET['id']) && isset($_GET['status'])) {
    $task_id = $_GET['id'];
    $status = $_GET['status'];
    $user_id = $_SESSION['user_id'];
    
    // Update task status (only if task belongs to current user)
    $stmt = $con->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $status, $task_id, $user_id);
    
    if($stmt->execute()) {
        header("Location: user.php?message=Task updated successfully");
    } else {
        header("Location: user.php?error=Error updating task");
    }
    exit();
} else {
    header("Location: user.php");
    exit();
}
?>