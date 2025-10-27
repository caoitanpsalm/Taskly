<?php
session_start();
require_once 'connect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Get task details for editing
if(isset($_GET['id'])) {
    $task_id = $_GET['id'];
    $stmt = $con->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    
    if(!$task) {
        header("Location: user.php?error=Task not found");
        exit();
    }
}

// Handle task update
if(isset($_POST['update_task'])) {
    $task_id = $_POST['task_id'];
    $task_title = $_POST['task_title'];
    $task_description = $_POST['task_description'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    
    $stmt = $con->prepare("UPDATE tasks SET task_title = ?, task_description = ?, priority = ?, due_date = ?, status = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssssii", $task_title, $task_description, $priority, $due_date, $status, $task_id, $user_id);
    
    if($stmt->execute()) {
        $message = "Task updated successfully!";
    } else {
        $message = "Error updating task: " . $con->error;
    }
    
    // Refresh task data
    $stmt = $con->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Task - To-Do List</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="header">
                <h1>Edit Task</h1>
                <p>Update your task details</p>
            </div>
            
            <div class="nav-menu">
                <a href="user.php" class="btn">Back to Tasks</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if(isset($task)): ?>
            <form method="POST" action="" class="task-form">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                
                <div class="form-group">
                    <label for="task_title">Task Title:*</label>
                    <input type="text" id="task_title" name="task_title" value="<?php echo htmlspecialchars($task['task_title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="task_description">Description:</label>
                    <textarea id="task_description" name="task_description" rows="3"><?php echo htmlspecialchars($task['task_description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="priority">Priority:</label>
                        <select id="priority" name="priority">
                            <option value="low" <?php echo $task['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $task['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $task['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in progress" <?php echo $task['status'] == 'in progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="due_date">Due Date:</label>
                        <input type="date" id="due_date" name="due_date" value="<?php echo $task['due_date']; ?>">
                    </div>
                </div>
                
                <button type="submit" name="update_task" class="btn">Update Task</button>
                <a href="user.php" class="btn">Cancel</a>
            </form>
            <?php else: ?>
                <div class="alert alert-error">Task not found!</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>