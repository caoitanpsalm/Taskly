<?php
session_start();
require_once 'connect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Get user data - using only existing columns
$user_stmt = $con->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Get user stats
$total_tasks = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id")->fetch_assoc()['count'];
$completed_tasks = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['count'];
$pending_tasks = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND status = 'pending'")->fetch_assoc()['count'];
$today_tasks = $con->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND due_date = CURDATE()")->fetch_assoc()['count'];

// Calculate productivity rate
$productivity_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Handle profile update - only update existing columns
if(isset($_POST['update_profile'])) {
    $email = $_POST['email'];
    
    // Check if email already exists (excluding current user)
    $check_email = $con->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    $email_result = $check_email->get_result();
    
    if($email_result->num_rows > 0) {
        $message = "Error: Email already exists!";
    } else {
        // Update user profile - only email for now
        $update_stmt = $con->prepare("UPDATE users SET email = ? WHERE id = ?");
        $update_stmt->bind_param("si", $email, $user_id);
        
        if($update_stmt->execute()) {
            $message = "Profile updated successfully!";
        } else {
            $message = "Error updating profile: " . $con->error;
        }
    }
}

// Get recent activity
$activity_stmt = $con->prepare("
    SELECT 'completed' as type, task_title, updated_at as activity_date 
    FROM tasks 
    WHERE user_id = ? AND status = 'completed'
    UNION ALL
    SELECT 'created' as type, task_title, created_at as activity_date 
    FROM tasks 
    WHERE user_id = ? 
    ORDER BY activity_date DESC 
    LIMIT 5
");
$activity_stmt->bind_param("ii", $user_id, $user_id);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();

// Calculate current streak (simplified - in real app you'd track login dates)
$current_streak = 1; // Placeholder

// Get username for display
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile | Taskly</title>
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
            transition: margin-left 0.3s ease;
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

        /* Progress Card */
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

        /* Motivation */
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
        }
        
        /* Stats Cards */
        .stats-cards {
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
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #800000;
        }
        
        /* Content Box */
        .content-box {
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        /* Profile Content */
        .box-body {
            padding: 20px;
        }

        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #800000;
            color: #FFD700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 15px;
            border: 4px solid #FFD700;
        }

        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #800000;
        }

        .profile-info p {
            color: #666;
        }

        /* Forms */
        .task-form {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #800000;
            border-radius: 5px;
            font-size: 16px;
            background: white;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #600000;
            box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.1);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }

        /* Buttons */
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary {
            background-color: #800000;
            color: #FFD700;
        }

        .btn-primary:hover {
            background-color: #600000;
            transform: translateY(-1px);
        }

        /* Activity List */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #800000;
            color: #FFD700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #800000;
        }

        .activity-time {
            font-size: 14px;
            color: #666;
        }

        /* Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        .mobile-menu-btn:active {
            transform: scale(0.95);
        }

        .sidebar {
            transition: transform 0.3s ease-in-out;
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
                <li><a href="profile.php" class="active">
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
            <h1>My Profile</h1>
            <p>Manage your account information and view your activity</p>
        </div>

        <!-- Profile Information Section -->
        <div class="content-box">
            <div class="box-header">
                <h2>Profile Information</h2>
            </div>
            <div class="box-body">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php 
                        $initials = strtoupper(substr($username, 0, 2));
                        echo $initials;
                        ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($username); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>

                <!-- User Stats -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <h3>Tasks Completed</h3>
                        <div class="stat-number"><?php echo $completed_tasks; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Tasks Pending</h3>
                        <div class="stat-number"><?php echo $pending_tasks; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Productivity Rate</h3>
                        <div class="stat-number"><?php echo $productivity_rate; ?>%</div>
                    </div>
                    <div class="stat-card">
                        <h3>Current Streak</h3>
                        <div class="stat-number"><?php echo $current_streak; ?></div>
                    </div>
                </div>

                <?php if($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
                        <small style="color: #666;">Username cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="content-box">
            <div class="box-header">
                <h2>Recent Activity</h2>
            </div>
            <div class="box-body">
                <ul class="activity-list">
                    <?php if($activity_result->num_rows > 0): ?>
                        <?php while($activity = $activity_result->fetch_assoc()): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <?php echo $activity['type'] == 'completed' ? '✓' : '+'; ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?php 
                                    if($activity['type'] == 'completed') {
                                        echo 'Completed task "' . htmlspecialchars($activity['task_title']) . '"';
                                    } else {
                                        echo 'Added new task "' . htmlspecialchars($activity['task_title']) . '"';
                                    }
                                    ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('F j, Y g:i A', strtotime($activity['activity_date'])); ?>
                                </div>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-content">
                                <div class="activity-title">No recent activity</div>
                                <div class="activity-time">Complete some tasks to see your activity here</div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
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