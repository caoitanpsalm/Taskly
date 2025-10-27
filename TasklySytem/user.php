<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle task creation
if (isset($_POST['add_task'])) {
    $task_title = $_POST['task_title'];
    $task_description = $_POST['task_description'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    
    $stmt = $con->prepare("INSERT INTO tasks (user_id, task_title, task_description, priority, due_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $task_title, $task_description, $priority, $due_date);
    
    if ($stmt->execute()) {
        $message = "Task added successfully!";
    } else {
        $message = "Error adding task: " . $con->error;
    }
}

// Get stats
$total_tasks = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id")->fetch_assoc()['count'];
$completed_tasks = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['count'];
$high_priority = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND priority = 'high'")->fetch_assoc()['count'];
$today_tasks = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND due_date = CURDATE()")->fetch_assoc()['count'];

// Get user's tasks
$tasks = $con->query("SELECT * FROM tasks WHERE user_id = $user_id ORDER BY 
    CASE priority 
        WHEN 'high' THEN 1 
        WHEN 'medium' THEN 2 
        WHEN 'low' THEN 3 
    END, due_date ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Taskly - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="script.js" defer></script>
    <style>
        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* BASE BODY */
        body {
            font-family: Arial, sans-serif;
            background-color: #FFD700;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background: #800000;
            color: #FFD700;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
            transition: width 0.3s ease;
            overflow-x: hidden;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar.collapsed .logo h1,
        .sidebar.collapsed .sidebar-section h3,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .section-header,
        .sidebar.collapsed .user-details,
        .sidebar.collapsed .progress-card,
        .sidebar.collapsed .motivation {
            display: none;
        }

        .sidebar.collapsed .nav-menu a {
            justify-content: center;
            padding: 12px 0;
        }

        .sidebar.collapsed .nav-menu i {
            margin-right: 0;
            font-size: 1.2rem;
        }

        /* LOGO */
        .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid #FFD700;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 1.5rem;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: #FFD700;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
        }

        /* NAV MENU */
        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            padding: 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #FFD700;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-text {
            margin-left: 10px;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: #600000;
            border-left-color: #FFD700;
        }

        /* SIDEBAR SECTIONS */
        .sidebar-section {
            margin-bottom: 25px;
        }

        .sidebar-section h3 {
            color: #FFD700;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin: 0 20px 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #FFD700;
        }

        /* PROGRESS CARD */
        .sidebar-extras {
            padding: 0 20px;
            margin-bottom: 20px;
        }

        .progress-card {
            background: rgba(255, 215, 0, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .progress-text strong {
            color: #FFD700;
        }

        .progress-text span {
            color: #FFD700;
        }

        .progress-bar {
            background: rgba(255, 215, 0, 0.3);
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            background: #FFD700;
            height: 100%;
            transition: width 0.3s ease;
        }

        /* MOTIVATION */
        .motivation {
            text-align: center;
            color: #FFD700;
            font-style: italic;
            font-size: 0.9rem;
        }

        .motivation i {
            opacity: 0.5;
            margin-right: 5px;
        }

        /* USER SECTION */
        .sidebar-user {
            border-top: 1px solid #FFD700;
            padding-top: 20px;
            margin: 0 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #FFD700;
            color: #800000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .user-details strong {
            display: block;
            color: #FFD700;
            font-size: 0.9rem;
        }

        .user-details span {
            color: rgba(255, 215, 0, 0.7);
            font-size: 0.8rem;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 25px;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 60px;
        }

        /* HEADER */
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #800000;
            font-size: 1.8rem;
        }

        /* STATS */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #800000;
        }

        /* CONTENT BOX */
        .content-box {
            background: white;
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .box-header {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .box-header h2 {
            color: #800000;
            font-size: 1.2rem;
        }

        /* FORM */
        .task-form {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* TABLE */
        .table-container {
            overflow-x: auto;
            padding: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #800000;
            color: #FFD700;
            text-align: left;
        }

        /* BUTTONS */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #800000;
            color: #FFD700;
        }

        .btn-success {
            background: #008000;
            color: #fff;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }

        /* ✅ TASKS TABLE DESIGN */
        .task-table-container {
            padding: 20px;
            overflow-x: auto;
        }

        .task-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            font-size: 0.95rem;
        }

        .task-table thead {
            background-color: #800000;
            color: #FFD700;
        }

        .task-table th,
        .task-table td {
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid #ddd;
        }

        .task-table th {
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .task-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .task-table tr:hover {
            background-color: #fff3cd;
            transition: background 0.3s ease;
        }

        /* Priority Colors */
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #856404; font-weight: bold; }
        .priority-low { color: #0c5460; font-weight: bold; }

        /* Status Colors */
        .status-pending { color: #856404; font-weight: 600; }
        .status-in-progress { color: #004085; font-weight: 600; }
        .status-completed { color: #155724; font-weight: 600; }

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: #800000 !important;
            color: #FFD700 !important;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
            font-size: 14px;
            border: 2px solid #FFD700 !important;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: inline-block;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 15px !important;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .mobile-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 998;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }

        /* ✅ Fix overlay and mobile menu behavior */
            .mobile-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 998; /* one below sidebar */
            }

            .mobile-overlay.active {
                display: block;
            }

            @media (max-width: 768px) {
                 body {
                overflow-x: hidden;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 250px;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 999;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
                width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobile-overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <h1><i class="fas fa-tasks"></i> Taskly</h1>
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- 🗂 MAIN SECTIONS -->
        <div class="sidebar-section">
            <h3>Tasks</h3>
            <ul class="nav-menu">
                <li><a href="user.php" class="active">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Dashboard</span>
                </a></li>
                <li><a href="today.php">
                    <i class="fas fa-calendar-day"></i>
                    <span class="nav-text">Today</span>
                </a></li>
                <li><a href="upcoming.php">
                    <i class="fas fa-calendar-week"></i>
                    <span class="nav-text">Upcoming</span>
                </a></li>
                <li><a href="completed.php">
                    <i class="fas fa-check-circle"></i>
                    <span class="nav-text">Completed</span>
                </a></li>
            </ul>
        </div>

        <!-- 💡 EXTRAS -->
        <div class="sidebar-extras">
            <div class="progress-card">
                <div class="progress-text">
                    <strong>Today's Progress</strong>
                    <span><?php echo $today_tasks; ?> tasks</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $total_tasks > 0 ? ($completed_tasks/$total_tasks)*100 : 0; ?>%"></div>
                </div>
            </div>
            
            <div class="motivation">
                <i class="fas fa-quote-left"></i>
                <p>You got this! 💪</p>
            </div>
        </div>

        <!-- ⚙️ USER -->
        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <strong><?php echo $_SESSION['username']; ?></strong>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="profile.php">
                    <i class="fas fa-user-cog"></i>
                    <span class="nav-text">Profile</span>
                </a></li>
                
                <li>
                    <form method="POST" action="logout.php" style="display: inline;">
                        <button type="submit" style="background: none; border: none; color: #FFD700; cursor: pointer; width: 100%; text-align: left; padding: 12px 20px; font-size: 1rem;" onclick="return confirm('Are you sure you want to logout?')">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="nav-text">Logout</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" id="mobile-menu-btn">
            <i class="fas fa-bars"></i> Menu
        </button>
    
        <div class="header">
            <h1>Task Management</h1>
            <p>Welcome back, <?php echo $_SESSION['username']; ?>!</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3>Total Tasks</h3>
                <div class="stat-number"><?php echo $total_tasks; ?></div>
            </div>
            <div class="stat-card">
                <h3>Completed</h3>
                <div class="stat-number"><?php echo $completed_tasks; ?></div>
            </div>
            <div class="stat-card">
                <h3>High Priority</h3>
                <div class="stat-number"><?php echo $high_priority; ?></div>
            </div>
            <div class="stat-card">
                <h3>Due Today</h3>
                <div class="stat-number"><?php echo $today_tasks; ?></div>
            </div>
        </div>

        <!-- Add Task Form -->
        <div class="content-box">
            <div class="box-header">
                <h2>Add New Task</h2>
            </div>
            <div class="task-form">
                <?php if ($message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="task_title" class="form-control" placeholder="Task Title" required>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="task_description" class="form-control" placeholder="Task Description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <select name="priority" class="form-control">
                                <option value="low">Low Priority</option>
                                <option value="medium" selected>Medium Priority</option>
                                <option value="high">High Priority</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="add_task" class="btn btn-primary">Add Task</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Tasks Table -->
<div class="content-box">
    <div class="box-header">
        <h2>My Tasks</h2>
        <div class="box-actions">

            <span>Total: <?php echo $total_tasks; ?> tasks</span>
        </div>
    </div>

    <div class="table-container">
        <?php if ($tasks->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Task Title</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($task = $tasks->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($task['task_title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($task['task_description']); ?></td>
                            <td>
                                <span style="color: 
                                    <?php echo $task['priority'] == 'high' 
                                        ? '#e74c3c' 
                                        : ($task['priority'] == 'medium' 
                                            ? '#f39c12' 
                                            : '#27ae60'); ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </td>
                            <td><?php echo $task['due_date'] ? $task['due_date'] : 'No date'; ?></td>
                            <td>
                                <span style="color: #800000;">
                                    <?php echo ucfirst($task['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($task['status'] != 'completed'): ?>
                                        <a 
                                            href="update_task.php?id=<?php echo $task['id']; ?>&status=completed" 
                                            class="btn btn-success btn-sm"
                                        >
                                            Complete
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #155724; font-weight: bold; padding: 4px 8px;">✓ Done</span>
                                    <?php endif; ?>
                                    <a 
                                        href="edit_task.php?id=<?php echo $task['id']; ?>" 
                                        class="btn btn-primary btn-sm"
                                    >
                                        Edit
                                    </a>
                                    <a 
                                        href="delete_task.php?id=<?php echo $task['id']; ?>" 
                                        class="btn btn-primary btn-sm" 
                                        onclick="return confirm('Delete this task?')"
                                    >
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #800000;">
                <h3>No tasks yet!</h3>
                <p>Start by adding your first task above.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const body = document.body;

            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');
}

            
            // Update toggle button icon
            const toggleIcon = sidebar.querySelector('.toggle-btn i');
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.className = 'fas fa-bars';
            } else {
                toggleIcon.className = 'fas fa-bars';
            }
        

        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.createElement('div');
            overlay.className = 'mobile-overlay';
            document.body.appendChild(overlay);

    // Open mobile sidebar
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
             });

    // Close sidebar when overlay is clicked
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
    });

    // Close sidebar when clicking a menu link (on mobile)
            const menuItems = document.querySelectorAll('.nav-menu a');
            menuItems.forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    }
                });
            });
        });

    </script>

</body>
</html>
