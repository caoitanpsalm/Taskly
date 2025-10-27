<?php
session_start();
require 'connect.php';
if(!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];
$tasks = $con->query("SELECT * FROM tasks WHERE user_id = $user_id AND status = 'completed'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Tasks - Taskly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="script.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #FFD700;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
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
        
        .nav-menu a:hover, .nav-menu a.active {
            background: #600000;
            border-left-color: #FFD700;
        }

        /* Sidebar Sections */
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

        /* User Section */
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

        .user-details {
            flex: 1;
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
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 60px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #800000;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
        }
        
        /* Content Box */
        .content-box {
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .box-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .box-header h2 {
            color: #800000;
        }
        
        /* Task List */
        .task-list {
            padding: 20px;
        }

        .task-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            opacity: 0.9;
        }

        .task-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            opacity: 1;
        }

        .task-item h3 {
            color: #800000;
            margin-bottom: 10px;
            font-size: 1.2rem;
            text-decoration: line-through;
        }

        .task-item p {
            color: #666;
            text-decoration: line-through;
        }

        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .meta-tag {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .meta-priority-high {
            background: #ffe6e6;
            color: #dc3545;
        }

        .meta-priority-medium {
            background: #fff3cd;
            color: #856404;
        }

        .meta-priority-low {
            background: #d1ecf1;
            color: #0c5460;
        }

        .meta-status-completed {
            background: #d4edda;
            color: #155724;
        }

        .meta-due-date {
            background: #e2e3e5;
            color: #383d41;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #800000;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: #800000;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #800000;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: #800000;
            color: #FFD700;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
            font-size: 14px;
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
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <h1><i class="fas fa-tasks"></i> Taskly</h1>
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Main Sections -->
        <div class="sidebar-section">
            <h3>Tasks</h3>
            <ul class="nav-menu">
                <li><a href="user.php">
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
                <li><a href="completed.php" class="active">
                    <i class="fas fa-check-circle"></i>
                    <span class="nav-text">Completed</span>
                </a></li>
            </ul>
        </div>

        <!-- User Section -->
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
        <button class="mobile-menu-btn">
            <i class="fas fa-bars"></i> Menu
        </button>
    
        <div class="header">
            <h1>✅ Completed Tasks</h1>
            <p>Your accomplished tasks and achievements</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $tasks->num_rows; ?></div>
                <div class="stat-label">Total Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo date('M Y'); ?></div>
                <div class="stat-label">Current Month</div>
            </div>
        </div>

        <div class="content-box">
            <div class="box-header">
                <h2>Completed Tasks</h2>
                <span><?php echo $tasks->num_rows; ?> tasks</span>
            </div>
            <div class="task-list">
                <?php if($tasks->num_rows > 0): ?>
                    <?php while($t = $tasks->fetch_assoc()): ?>
                        <div class="task-item">
                            <h3><?php echo htmlspecialchars($t['task_title']); ?></h3>
                            <?php if(!empty($t['task_description'])): ?>
                                <p><?php echo htmlspecialchars($t['task_description']); ?></p>
                            <?php endif; ?>
                            <div class="task-meta">
                                <span class="meta-tag meta-priority-<?php echo strtolower($t['priority']); ?>">
                                    <?php echo ucfirst($t['priority']); ?> Priority
                                </span>
                                <span class="meta-tag meta-status-completed">
                                    Completed
                                </span>
                                <?php if(!empty($t['due_date'])): ?>
                                    <span class="meta-tag meta-due-date">
                                        Due: <?php echo date('M j, Y', strtotime($t['due_date'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No completed tasks yet!</h3>
                        <p>Complete some tasks to see them here. You can do it! 💪</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const body = document.body;
            
            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');
            
            const toggleIcon = sidebar.querySelector('.toggle-btn i');
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.className = 'fas fa-bars';
            } else {
                toggleIcon.className = 'fas fa-bars';
            }
        }

        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.createElement('div');
            
            overlay.className = 'mobile-overlay';
            document.body.appendChild(overlay);

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('mobile-open');
                    overlay.classList.toggle('active');
                });
            }

            overlay.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            });

            // Close sidebar when clicking on menu items on mobile
            if (window.innerWidth <= 768) {
                const menuItems = document.querySelectorAll('.nav-menu a');
                menuItems.forEach(item => {
                    item.addEventListener('click', () => {
                        sidebar.classList.remove('mobile-open');
                        overlay.classList.remove('active');
                    });
                });
            }
        });
    </script>
</body>
</html>

