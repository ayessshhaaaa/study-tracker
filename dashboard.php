<?php
require_once __DIR__ . '/db_connect.php';

// Ensure user is logged in. Adjust the key names if your session uses different ones.
if (!isset($_SESSION['username'])) {
    if (isset($_POST['action'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'not_logged_in']);
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'User';

// Simple Tasks API for AJAX POSTs to this same file.
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // NOTE: Adjust table/column names below to match your schema.
    // Expected `tasks` table columns used here:
    // id (INT AI PK), user_id (INT), name (VARCHAR), description (TEXT),
    // priority (VARCHAR), deadline (DATETIME NULL), status (VARCHAR), created_at (TIMESTAMP default CURRENT_TIMESTAMP)
    try {
        if ($action === 'list') {
            $stmt = $conn->prepare("SELECT id, name, description, priority, deadline, status FROM tasks WHERE user_id = ? ORDER BY COALESCE(deadline, '9999-12-31') ASC, id DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $tasks = [];
            while ($row = $res->fetch_assoc()) {
                $tasks[] = [
                    'id' => (int)$row['id'],
                    'userId' => (int)$user_id,
                    'name' => $row['name'] ?? '',
                    'description' => $row['description'] ?? '',
                    'priority' => $row['priority'] ?? 'Medium',
                    'deadline' => $row['deadline'] ?? '',
                    'status' => $row['status'] ?? 'Pending',
                ];
            }
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            exit;
        } elseif ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = trim($_POST['priority'] ?? 'Medium');
            $deadline = trim($_POST['deadline'] ?? '');
            $status = trim($_POST['status'] ?? 'Pending');

            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Task name is required']);
                exit;
            }

            $deadlineParam = ($deadline !== '') ? $deadline : null;

            $stmt = $conn->prepare("INSERT INTO tasks (user_id, name, description, priority, deadline, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $user_id, $name, $description, $priority, $deadlineParam, $status);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => 'DB insert failed']);
                exit;
            }
            $new_id = $stmt->insert_id;
            echo json_encode(['success' => true, 'task' => [
                'id' => (int)$new_id,
                'userId' => (int)$user_id,
                'name' => $name,
                'description' => $description,
                'priority' => $priority,
                'deadline' => $deadline,
                'status' => $status,
            ]]);
            exit;
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = trim($_POST['priority'] ?? 'Medium');
            $deadline = trim($_POST['deadline'] ?? '');
            $status = trim($_POST['status'] ?? 'Pending');

            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid id']);
                exit;
            }
            $deadlineParam = ($deadline !== '') ? $deadline : null;

            $stmt = $conn->prepare("UPDATE tasks SET name = ?, description = ?, priority = ?, deadline = ?, status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssssiii", $name, $description, $priority, $deadlineParam, $status, $id, $user_id);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => 'DB update failed']);
                exit;
            }
            echo json_encode(['success' => true]);
            exit;
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid id']);
                exit;
            }
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => 'DB delete failed']);
                exit;
            }
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Productivity Tracker - Enhanced Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --accent: #f72585;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            padding-top: 56px;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar {
            background-color: white;
            height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            width: 250px;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s;
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        .sidebar .nav-link {
            color: #495057;
            border-radius: 0;
            padding: 12px 20px;
            transition: all 0.2s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #e9ecef;
            color: var(--primary);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .main-content.expanded {
            margin-left: 70px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .stats-card h3 {
            font-size: 1.8rem;
            margin: 0;
            color: var(--dark);
        }
        
        .stats-card p {
            color: #6c757d;
            margin: 0;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .task-item {
            border-left: 4px solid var(--primary);
            margin-bottom: 10px;
            padding: 10px 15px;
            background-color: white;
            border-radius: 5px;
        }
        
        .task-item.high {
            border-left-color: #dc3545;
        }
        
        .task-item.medium {
            border-left-color: #ffc107;
        }
        
        .task-item.low {
            border-left-color: #28a745;
        }
        
        .pomodoro-timer {
            text-align: center;
            padding: 20px;
        }
        
        .timer-display {
            font-size: 3rem;
            font-weight: bold;
            margin: 20px 0;
            color: var(--primary);
        }
        
        .quote-container {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .calendar-container {
            padding: 15px;
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }
        
        /* Calendar Modal Styles */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-top: 15px;
        }
        
        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
        }
        
        .calendar-day {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .calendar-day:hover {
            background-color: #e9ecef;
        }
        
        .calendar-day.current {
            background-color: var(--primary);
            color: white;
        }
        
        .calendar-day.has-event {
            position: relative;
        }
        
        .calendar-day.has-event::after {
            content: '';
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 6px;
            height: 6px;
            background-color: var(--accent);
            border-radius: 50%;
        }
        
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .sidebar-text {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>
                College Productivity Tracker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <span id="username-display"><?php echo htmlspecialchars($username); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="d-flex flex-column p-3">
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#taskManagerModal">
                        <i class="fas fa-tasks"></i>
                        <span class="sidebar-text">Task Manager</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#calendarModal">
                        <i class="fas fa-calendar-day"></i>
                        <span class="sidebar-text">Smart Calendar</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#pomodoroModal">
                        <i class="fas fa-hourglass-half"></i>
                        <span class="sidebar-text">Pomodoro Timer</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span class="sidebar-text">Progress Tracker</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#quotesModal">
                        <i class="fas fa-quote-right"></i>
                        <span class="sidebar-text">Motivational Quotes</span>
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle">
                    <img src="https://via.placeholder.com/32" alt="User" width="32" height="32" class="rounded-circle me-2">
                    <strong class="sidebar-text" id="sidebar-username"><?php echo htmlspecialchars($username); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li><a class="dropdown-item" href="#">Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Welcome Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Dashboard</h2>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                <i class="fas fa-plus me-1"></i> Add Task
                            </button>
                        </div>
                    </div>
                    <p class="text-muted" id="welcome-message">Welcome back! Here's your productivity overview.</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <i class="fas fa-tasks"></i>
                        <h3 id="pending-tasks">0</h3>
                        <p>Pending Tasks</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <i class="fas fa-check-circle"></i>
                        <h3 id="completed-tasks">0</h3>
                        <p>Completed Tasks</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <i class="fas fa-fire"></i>
                        <h3 id="productivity-score">0%</h3>
                        <p>Productivity Score</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <i class="fas fa-clock"></i>
                        <h3 id="pomodoro-sessions">0</h3>
                        <p>Pomodoro Sessions</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Calendar View -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Smart Calendar</span>
                            <button class="btn btn-sm btn-outline-primary" id="refresh-calendar">Refresh</button>
                        </div>
                        <div class="card-body calendar-container">
                            <div id="calendar">
                                <!-- Calendar will be populated by JavaScript -->
                                <div class="text-center p-4">
                                    <h4>Upcoming Events</h4>
                                    <div class="upcoming-events" id="upcoming-events-container">
                                        <!-- Events will be loaded dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Overview -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Recent Tasks</span>
                            <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#taskManagerModal">View All</a>
                        </div>
                        <div class="card-body" id="recent-tasks-container">
                            <!-- Tasks will be loaded here via JavaScript -->
                        </div>
                    </div>

                    <!-- Progress Chart -->
                    <div class="card">
                        <div class="card-header">
                            Weekly Productivity
                        </div>
                        <div class="card-body">
                            <canvas id="productivityChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Pomodoro Timer -->
                    <div class="card">
                        <div class="card-header">
                            Pomodoro Timer
                        </div>
                        <div class="card-body pomodoro-timer">
                            <div class="btn-group mb-3" role="group">
                                <button type="button" class="btn btn-outline-primary active" data-minutes="25">25:00</button>
                                <button type="button" class="btn btn-outline-primary" data-minutes="5">5:00</button>
                                <button type="button" class="btn btn-outline-primary" data-minutes="15">15:00</button>
                            </div>
                            <div class="timer-display">25:00</div>
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-primary btn-lg me-2" id="startTimer">
                                    <i class="fas fa-play"></i> Start
                                </button>
                                <button class="btn btn-secondary btn-lg" id="resetTimer">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Motivational Quote -->
                    <div class="quote-container">
                        <div class="d-flex justify-content-center mb-3">
                            <i class="fas fa-quote-left me-2"></i>
                            <blockquote class="blockquote mb-0 text-center">
                                <p id="quote-text">The future belongs to those who believe in the beauty of their dreams.</p>
                                <footer class="blockquote-footer text-white" id="quote-author">Eleanor Roosevelt</footer>
                            </blockquote>
                            <i class="fas fa-quote-right ms-2"></i>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-light btn-sm" id="new-quote-btn">
                                <i class="fas fa-sync me-1"></i> New Quote
                            </button>
                        </div>
                    </div>

                    <!-- Upcoming Deadlines -->
                    <div class="card">
                        <div class="card-header">
                            Upcoming Deadlines
                        </div>
                        <div class="card-body" id="upcoming-deadlines">
                            <!-- Deadlines will be loaded here via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTaskModalLabel">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTaskForm">
                        <input type="hidden" id="taskId" name="task_id" value="">
                        <div class="mb-3">
                            <label for="taskName" class="form-label">Task Name</label>
                            <input type="text" class="form-control" id="taskName" name="task_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="priority" id="priorityLow" value="Low">
                                    <label class="form-check-label" for="priorityLow">Low</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="priority" id="priorityMedium" value="Medium" checked>
                                    <label class="form-check-label" for="priorityMedium">Medium</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="priority" id="priorityHigh" value="High">
                                    <label class="form-check-label" for="priorityHigh">High</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="taskDeadline" class="form-label">Deadline</label>
                            <input type="datetime-local" class="form-control" id="taskDeadline" name="deadline" required>
                        </div>
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTaskBtn">Save Task</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Manager Modal -->
    <div class="modal fade" id="taskManagerModal" tabindex="-1" aria-labelledby="taskManagerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskManagerModalLabel">Task Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Priority</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="taskManagerTable">
                                <!-- Tasks will be loaded here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pomodoro Timer Modal -->
    <div class="modal fade" id="pomodoroModal" tabindex="-1" aria-labelledby="pomodoroModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pomodoroModalLabel">Pomodoro Timer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="pomodoro-timer">
                        <div class="btn-group mb-3 w-100" role="group">
                            <button type="button" class="btn btn-outline-primary active" data-minutes="25">25:00</button>
                            <button type="button" class="btn btn-outline-primary" data-minutes="5">5:00</button>
                            <button type="button" class="btn btn-outline-primary" data-minutes="15">15:00</button>
                        </div>
                        <div class="timer-display">25:00</div>
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-primary btn-lg me-2" id="modalStartTimer">
                                <i class="fas fa-play"></i> Start
                            </button>
                            <button class="btn btn-secondary btn-lg" id="modalResetTimer">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Calendar Modal -->
    <div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calendarModalLabel">Smart Calendar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="calendar-nav">
                        <button class="btn btn-sm btn-outline-primary" id="prev-month">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h4 id="current-month-year">October 2023</h4>
                        <button class="btn btn-sm btn-outline-primary" id="next-month">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <div class="calendar-header">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>
                    
                    <div class="calendar-grid" id="calendar-days">
                        <!-- Calendar days will be populated by JavaScript -->
                    </div>
                    
                    <div class="mt-4">
                        <h5>Upcoming Events</h5>
                        <div id="calendar-events">
                            <!-- Events will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="add-calendar-event">Add Event</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Backend API helper + initial sync
            async function api(action, data = {}) {
                const params = new URLSearchParams({ action, ...data });
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });
                return res.json();
            }
            // On first load, pull tasks from backend into localStorage for fast UI rendering
            (async () => {
                try {
                    const resp = await api('list');
                    if (resp.success) {
                        localStorage.setItem('user_tasks', JSON.stringify(resp.tasks || []));
                    }
                } catch (e) { /* ignore */ }
            })();

            // Initialize variables
            let currentUser = { id: <?php echo json_encode($user_id); ?>, name: <?php echo json_encode($username); ?> };

            // Task management functions
            function getTasks() {
// Read from localStorage (synced from backend on load)
                const tasks = JSON.parse(localStorage.getItem('user_tasks')) || [];
                return tasks.filter(task => task.userId === currentUser.id);
            }

            function saveTasks(tasks) {
                // In a real app, this would be an API call to your backend
                const allTasks = JSON.parse(localStorage.getItem('user_tasks')) || [];
                
                // Remove existing tasks for this user
                const otherTasks = allTasks.filter(task => task.userId !== currentUser.id);
                
                // Add updated tasks for this user
                const updatedTasks = [...otherTasks, ...tasks];
                localStorage.setItem('user_tasks', JSON.stringify(updatedTasks));
            }

            function addTask(task) {
// Create on backend first, then mirror to localStorage
                return (async () => {
                    try {
                        const resp = await api('add', {
                            name: task.name,
                            description: task.description || '',
                            priority: task.priority || 'Medium',
                            deadline: task.deadline || '',
                            status: task.status || 'Pending'
                        });
                        if (resp.success && resp.task) {
                            const tasks = JSON.parse(localStorage.getItem('user_tasks')) || [];
                            tasks.unshift(resp.task);
                            localStorage.setItem('user_tasks', JSON.stringify(tasks));
                            return resp.task;
                        }
                    } catch (e) { /* ignore */ }
                    // Fallback to local-only if backend fails
                    const tasks = JSON.parse(localStorage.getItem('user_tasks')) || [];
                    const newTask = {
                        id: Date.now(),
                        userId: currentUser.id,
                        name: task.name,
                        description: task.description || '',
                        priority: task.priority || 'Medium',
                        deadline: task.deadline || '',
                        status: task.status || 'Pending'
                    };
                    tasks.unshift(newTask);
                    localStorage.setItem('user_tasks', JSON.stringify(tasks));
                    return newTask;
                })();
            }

            function updateTask(taskId, updatedData) {
// Update backend, then localStorage
                (async () => {
                    try {
                        await api('update', {
                            id: taskId,
                            name: updatedData.name || '',
                            description: updatedData.description || '',
                            priority: updatedData.priority || 'Medium',
                            deadline: updatedData.deadline || '',
                            status: updatedData.status || 'Pending'
                        });
                    } catch (e) { /* ignore */ }
                })();
                const tasks = JSON.parse(localStorage.getItem('user_tasks')) || [];
                const updatedTasks = tasks.map(t => t.id === taskId ? { ...t, ...updatedData } : t);
                localStorage.setItem('user_tasks', JSON.stringify(updatedTasks));
                return updatedTasks;
            }

            function deleteTask(taskId) {
// Delete on backend, then localStorage
                (async () => {
                    try {
                        await api('delete', { id: taskId });
                    } catch (e) { /* ignore */ }
                })();
                const tasks = JSON.parse(localStorage.getItem('user_tasks')) || [];
                const updatedTasks = tasks.filter(t => t.id !== taskId);
                localStorage.setItem('user_tasks', JSON.stringify(updatedTasks));
                return updatedTasks;
            }

            function renderTasks() {
                const tasks = getTasks();
                const pendingTasks = tasks.filter(task => task.status === 'Pending');
                const completedTasks = tasks.filter(task => task.status === 'Completed');
                
                // Update stats
                document.getElementById('pending-tasks').textContent = pendingTasks.length;
                document.getElementById('completed-tasks').textContent = completedTasks.length;
                
                // Calculate productivity score (simple version)
                const totalTasks = tasks.length;
                const productivityScore = totalTasks > 0 
                    ? Math.round((completedTasks.length / totalTasks) * 100) 
                    : 0;
                document.getElementById('productivity-score').textContent = `${productivityScore}%`;
                
                // Render recent tasks (only pending)
                const recentTasksContainer = document.getElementById('recent-tasks-container');
                recentTasksContainer.innerHTML = '';
                
                if (pendingTasks.length === 0) {
                    recentTasksContainer.innerHTML = '<p class="text-muted text-center">No tasks yet. Add a task to get started!</p>';
                } else {
                    // Show only the 3 most recent pending tasks
                    const recentTasks = pendingTasks
                        .sort((a, b) => new Date(a.deadline) - new Date(b.deadline))
                        .slice(0, 3);
                    
                    recentTasks.forEach(task => {
                        const taskElement = createTaskElement(task);
                        recentTasksContainer.appendChild(taskElement);
                    });
                }
                
                // Render task manager table
                const taskManagerTable = document.getElementById('taskManagerTable');
                taskManagerTable.innerHTML = '';
                
                tasks.forEach(task => {
                    const row = document.createElement('tr');
                    
                    // Format deadline for display
                    const deadlineDate = new Date(task.deadline);
                    const formattedDeadline = deadlineDate.toLocaleString();
                    
                    row.innerHTML = `
                        <td>${task.name}</td>
                        <td><span class="badge ${getPriorityBadgeClass(task.priority)}">${task.priority}</span></td>
                        <td>${formattedDeadline}</td>
                        <td><span class="badge ${task.status === 'Completed' ? 'bg-success' : 'bg-warning'}">${task.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1 edit-task" data-task-id="${task.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-task" data-task-id="${task.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                            ${task.status === 'Pending' ? 
                                `<button class="btn btn-sm btn-outline-success complete-task" data-task-id="${task.id}">
                                    <i class="fas fa-check"></i>
                                </button>` : ''
                            }
                        </td>
                    `;
                    
                    taskManagerTable.appendChild(row);
                });
                
                // Add event listeners to action buttons
                document.querySelectorAll('.edit-task').forEach(button => {
                    button.addEventListener('click', function() {
                        const taskId = parseInt(this.getAttribute('data-task-id'));
                        editTask(taskId);
                    });
                });
                
                document.querySelectorAll('.delete-task').forEach(button => {
                    button.addEventListener('click', function() {
                        const taskId = parseInt(this.getAttribute('data-task-id'));
                        deleteTaskHandler(taskId);
                    });
                });
                
                document.querySelectorAll('.complete-task').forEach(button => {
                    button.addEventListener('click', function() {
                        const taskId = parseInt(this.getAttribute('data-task-id'));
                        completeTask(taskId);
                    });
                });
                
                // Render upcoming deadlines
                renderUpcomingDeadlines();
                
                // Render calendar events
                renderCalendarEvents();
            }
            
            function getPriorityBadgeClass(priority) {
                switch(priority) {
                    case 'High': return 'bg-danger';
                    case 'Medium': return 'bg-warning';
                    case 'Low': return 'bg-success';
                    default: return 'bg-secondary';
                }
            }
            
            function createTaskElement(task) {
                const taskElement = document.createElement('div');
                taskElement.classList.add('task-item', task.priority.toLowerCase());
                
                // Format deadline for display
                const deadlineDate = new Date(task.deadline);
                const now = new Date();
                const timeDiff = deadlineDate - now;
                const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
                
                let dueText = '';
                if (daysDiff === 0) {
                    dueText = 'Due: Today, ' + deadlineDate.toLocaleTimeString();
                } else if (daysDiff === 1) {
                    dueText = 'Due: Tomorrow, ' + deadlineDate.toLocaleTimeString();
                } else if (daysDiff > 1) {
                    dueText = 'Due: ' + deadlineDate.toLocaleDateString() + ', ' + deadlineDate.toLocaleTimeString();
                } else {
                    dueText = 'Overdue: ' + deadlineDate.toLocaleDateString();
                }
                
                taskElement.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-1">${task.name}</h5>
                        <small class="text-${task.priority === 'High' ? 'danger' : task.priority === 'Medium' ? 'warning' : 'success'}">${task.priority} Priority</small>
                    </div>
                    <p class="mb-1">${task.description || 'No description'}</p>
                    <small class="text-muted">${dueText}</small>
                `;
                
                return taskElement;
            }
            
            function renderUpcomingDeadlines() {
                const tasks = getTasks();
                const pendingTasks = tasks.filter(task => task.status === 'Pending');
                const upcomingDeadlinesContainer = document.getElementById('upcoming-deadlines');
                
                upcomingDeadlinesContainer.innerHTML = '';
                
                if (pendingTasks.length === 0) {
                    upcomingDeadlinesContainer.innerHTML = '<p class="text-muted text-center">No upcoming deadlines</p>';
                    return;
                }
                
                // Sort by deadline and take top 3
                const upcomingTasks = pendingTasks
                    .sort((a, b) => new Date(a.deadline) - new Date(b.deadline))
                    .slice(0, 3);
                
                const listGroup = document.createElement('ul');
                listGroup.classList.add('list-group', 'list-group-flush');
                
                upcomingTasks.forEach(task => {
                    const deadlineDate = new Date(task.deadline);
                    const now = new Date();
                    const timeDiff = deadlineDate - now;
                    const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
                    
                    let badgeText = '';
                    if (daysDiff === 0) {
                        badgeText = 'Today';
                    } else if (daysDiff === 1) {
                        badgeText = '1 day';
                    } else {
                        badgeText = `${daysDiff} days`;
                    }
                    
                    const listItem = document.createElement('li');
                    listItem.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');
                    
                    listItem.innerHTML = `
                        <div>
                            <h6 class="mb-0">${task.name}</h6>
                            <small class="text-muted">${task.description || 'No description'}</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">${badgeText}</span>
                    `;
                    
                    listGroup.appendChild(listItem);
                });
                
                upcomingDeadlinesContainer.appendChild(listGroup);
            }
            
            function renderCalendarEvents() {
                const tasks = getTasks();
                const upcomingEventsContainer = document.getElementById('upcoming-events-container');
                const calendarEventsContainer = document.getElementById('calendar-events');
                
                upcomingEventsContainer.innerHTML = '';
                calendarEventsContainer.innerHTML = '';
                
                if (tasks.length === 0) {
                    upcomingEventsContainer.innerHTML = '<p class="text-muted">No upcoming events</p>';
                    calendarEventsContainer.innerHTML = '<p class="text-muted">No upcoming events</p>';
                    return;
                }
                
                // Sort by deadline and take top 3
                const upcomingTasks = tasks
                    .sort((a, b) => new Date(a.deadline) - new Date(b.deadline))
                    .slice(0, 3);
                
                upcomingTasks.forEach(task => {
                    const deadlineDate = new Date(task.deadline);
                    const formattedDate = deadlineDate.toLocaleDateString() + ' ' + deadlineDate.toLocaleTimeString();
                    
                    const eventItem = document.createElement('div');
                    eventItem.classList.add('event-item', 'p-2', 'border-bottom');
                    
                    eventItem.innerHTML = `
                        <div class="d-flex justify-content-between">
                            <span>${task.name}</span>
                            <small class="text-muted">${formattedDate}</small>
                        </div>
                    `;
                    
                    upcomingEventsContainer.appendChild(eventItem.cloneNode(true));
                    calendarEventsContainer.appendChild(eventItem);
                });
            }
            
            function editTask(taskId) {
                const tasks = getTasks();
                const task = tasks.find(t => t.id === taskId);
                
                if (task) {
                    // Populate the form
                    document.getElementById('taskId').value = task.id;
                    document.getElementById('taskName').value = task.name;
                    document.getElementById('taskDeadline').value = task.deadline.replace(' ', 'T').substring(0, 16);
                    document.getElementById('taskDescription').value = task.description || '';
                    
                    // Set priority
                    document.querySelectorAll('input[name="priority"]').forEach(radio => {
                        radio.checked = (radio.value === task.priority);
                    });
                    
                    // Open the modal
                    const addTaskModal = new bootstrap.Modal(document.getElementById('addTaskModal'));
                    addTaskModal.show();
                }
            }
            
            function deleteTaskHandler(taskId) {
                if (confirm('Are you sure you want to delete this task?')) {
                    deleteTask(taskId);
                    renderTasks();
                }
            }
            
            function completeTask(taskId) {
                updateTask(taskId, { status: 'Completed' });
                renderTasks();
            }
            
            // Event Listeners
            document.getElementById('saveTaskBtn').addEventListener('click', function() {
                const taskId = document.getElementById('taskId').value;
                const taskName = document.getElementById('taskName').value;
                const priority = document.querySelector('input[name="priority"]:checked').value;
                const deadline = document.getElementById('taskDeadline').value;
                const description = document.getElementById('taskDescription').value;
                
                if (taskName && deadline) {
                    if (taskId) {
                        // Update existing task
                        updateTask(parseInt(taskId), {
                            name: taskName,
                            priority: priority,
                            deadline: deadline,
                            description: description
                        });
                    } else {
                        // Add new task
                        addTask({
                            name: taskName,
                            priority: priority,
                            deadline: deadline,
                            description: description
                        });
                    }
                    
                    // Close modal and reset form
                    const addTaskModal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
                    addTaskModal.hide();
                    document.getElementById('addTaskForm').reset();
                    document.getElementById('taskId').value = '';
                    
                    // Refresh tasks display
                    renderTasks();
                } else {
                    alert('Please fill in all required fields');
                }
            });
            
            // Initialize the dashboard
            renderTasks();
            
            // Initialize productivity chart
            const ctx = document.getElementById('productivityChart').getContext('2d');
            const productivityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Tasks Completed',
                        data: [3, 5, 2, 6, 4, 3, 7],
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 10
                        }
                    }
                }
            });
            
            // Pomodoro Timer functionality
            let timerInterval;
            let timerMinutes = 25;
            let timerSeconds = 0;
            let isTimerRunning = false;
            
            function updateTimerDisplay() {
                const timerDisplay = document.querySelector('.timer-display');
                if (timerDisplay) {
                    timerDisplay.textContent = `${timerMinutes.toString().padStart(2, '0')}:${timerSeconds.toString().padStart(2, '0')}`;
                }
                
                const modalTimerDisplay = document.querySelector('#pomodoroModal .timer-display');
                if (modalTimerDisplay) {
                    modalTimerDisplay.textContent = `${timerMinutes.toString().padStart(2, '0')}:${timerSeconds.toString().padStart(2, '0')}`;
                }
            }
            
            function startTimer() {
                if (!isTimerRunning) {
                    isTimerRunning = true;
                    timerInterval = setInterval(function() {
                        if (timerSeconds === 0) {
                            if (timerMinutes === 0) {
                                clearInterval(timerInterval);
                                isTimerRunning = false;
                                alert('Timer completed!');
                                return;
                            }
                            timerMinutes--;
                            timerSeconds = 59;
                        } else {
                            timerSeconds--;
                        }
                        updateTimerDisplay();
                    }, 1000);
                }
            }
            
            function resetTimer() {
                clearInterval(timerInterval);
                isTimerRunning = false;
                
                // Reset to the selected time
                const selectedTimeBtn = document.querySelector('.btn-group .btn.active');
                if (selectedTimeBtn) {
                    timerMinutes = parseInt(selectedTimeBtn.getAttribute('data-minutes'));
                } else {
                    timerMinutes = 25;
                }
                timerSeconds = 0;
                updateTimerDisplay();
            }
            
            // Timer event listeners
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    resetTimer();
                });
            });
            
            document.getElementById('startTimer').addEventListener('click', startTimer);
            document.getElementById('resetTimer').addEventListener('click', resetTimer);
            document.getElementById('modalStartTimer').addEventListener('click', startTimer);
            document.getElementById('modalResetTimer').addEventListener('click', resetTimer);
            
            // Initialize timer display
            resetTimer();
            
            // Motivational quotes functionality
            const quotes = [
                {
                    text: "The future belongs to those who believe in the beauty of their dreams.",
                    author: "Eleanor Roosevelt"
                },
                {
                    text: "Don't watch the clock; do what it does. Keep going.",
                    author: "Sam Levenson"
                },
                {
                    text: "Believe you can and you're halfway there.",
                    author: "Theodore Roosevelt"
                },
                {
                    text: "Everything you've ever wanted is on the other side of fear.",
                    author: "George Addair"
                },
                {
                    text: "Success is not final, failure is not fatal: it is the courage to continue that counts.",
                    author: "Winston Churchill"
                }
            ];
            
            function showRandomQuote() {
                const randomIndex = Math.floor(Math.random() * quotes.length);
                const quote = quotes[randomIndex];
                document.getElementById('quote-text').textContent = quote.text;
                document.getElementById('quote-author').textContent = quote.author;
            }
            
            document.getElementById('new-quote-btn').addEventListener('click', showRandomQuote);
            
            // Initialize with a random quote
            showRandomQuote();
            
            // Calendar functionality
            const currentDate = new Date();
            let currentMonth = currentDate.getMonth();
            let currentYear = currentDate.getFullYear();
            
            function renderCalendar() {
                const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];
                
                document.getElementById('current-month-year').textContent = `${monthNames[currentMonth]} ${currentYear}`;
                
                const firstDay = new Date(currentYear, currentMonth, 1).getDay();
                const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                
                const calendarDays = document.getElementById('calendar-days');
                calendarDays.innerHTML = '';
                
                // Add empty cells for days before the first day of the month
                for (let i = 0; i < firstDay; i++) {
                    const emptyDay = document.createElement('div');
                    emptyDay.classList.add('calendar-day');
                    calendarDays.appendChild(emptyDay);
                }
                
                // Add cells for each day of the month
                for (let day = 1; day <= daysInMonth; day++) {
                    const dayElement = document.createElement('div');
                    dayElement.classList.add('calendar-day');
                    dayElement.textContent = day;
                    
                    // Check if this day is today
                    if (currentDate.getDate() === day && 
                        currentDate.getMonth() === currentMonth && 
                        currentDate.getFullYear() === currentYear) {
                        dayElement.classList.add('current');
                    }
                    
                    // Randomly mark some days as having events (for demo)
                    if (Math.random() > 0.7) {
                        dayElement.classList.add('has-event');
                    }
                    
                    calendarDays.appendChild(dayElement);
                }
            }
            
            document.getElementById('prev-month').addEventListener('click', function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar();
            });
            
            document.getElementById('next-month').addEventListener('click', function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar();
            });
            
            // Initialize calendar
            renderCalendar();
            
            // Refresh calendar button
            document.getElementById('refresh-calendar').addEventListener('click', function() {
                renderCalendar();
                renderCalendarEvents();
            });
        });
    </script>
</body>
</html>