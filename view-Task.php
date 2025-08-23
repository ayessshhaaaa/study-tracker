<?php
// --- Live & Dynamic PHP Backend (Final Fix) ---

// 1. ESTABLISH DATABASE CONNECTION & START SESSION
// Your 'db_connect.php' should handle both starting the session and connecting.
require_once __DIR__ . '/db_connect.php'; 

// 2. CHECK USER AUTHENTICATION
// The script will only reach this point if the database connection was successful.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 3. GET USER DATA FROM SESSION
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// THIS SCRIPT NOW HAS TWO MODES:
// A) AJAX request (fetches tasks for a specific date and returns only JSON data).
// B) Initial page load (renders the full HTML structure).

if (isset($_GET['fetch_tasks']) && isset($_GET['date'])) {
    header('Content-Type: application/json');
    
    $selectedDate = $_GET['date'];
    $tasks = [];

    // --- FIX APPLIED HERE: Corrected column names from 'title', 'description' to 'subject', 'task' ---
    $query = "SELECT subject, task, start_time, end_time FROM tasks WHERE user_id = ? AND task_date = ? ORDER BY start_time ASC";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("is", $userId, $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    $conn->close();
    
    // Return the tasks as a JSON object and stop the script.
    echo json_encode($tasks);
    exit();
}

// --- FOR INITIAL PAGE LOAD, FETCH TODAY'S TASKS ---
$initialTasks = [];
$todayDate = date('Y-m-d');
// --- FIX APPLIED HERE: Corrected column names from 'title', 'description' to 'subject', 'task' ---
$query = "SELECT subject, task, start_time, end_time FROM tasks WHERE user_id = ? AND task_date = ? ORDER BY start_time ASC";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("is", $userId, $todayDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $initialTasks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
// The connection will be closed automatically when the script finishes.

// Group initial tasks by hour for rendering.
$tasksByHour = [];
foreach ($initialTasks as $task) {
    $hour = (int)date('H', strtotime($task['start_time']));
    if (!isset($tasksByHour[$hour])) {
        $tasksByHour[$hour] = [];
    }
    $tasksByHour[$hour][] = $task;
}

// Define the palette of pastel colors for task cards.
$taskColors = ['#FFDAB9', '#E0BBE4', '#BDE0FE', '#C1E1C1', '#FFC3A0', '#D8BFD8', '#FADADD', '#FFFACD'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Tasks</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* --- Global Styles & Variables --- */
        :root {
            --bg-color: #FEFBF3;
            --text-dark: #2C3E50;
            --text-light: #95A5A6;
            --header-green: #16A085;
            --white: #FFFFFF;
            --border-color: #F0EBE3;
            --accent-blue: #3498DB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .planner-container {
            width: 100%;
            max-width: 480px;
            background-color: var(--white);
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }

        /* --- Header --- */
        .planner-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        .header-title h1 { font-size: 2.5rem; font-weight: 700; line-height: 1.1; }
        .header-title p { font-size: 1rem; color: var(--text-light); margin-top: 0.25rem; }
        .add-task-btn {
            background-color: var(--header-green);
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            white-space: nowrap;
        }
        .add-task-btn:hover { background-color: #1ABC9C; }

        /* --- Interactive Calendar --- */
        .calendar-widget { margin-bottom: 2rem; }
        .month-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .month-display { font-size: 1.25rem; font-weight: 600; }
        .nav-arrow {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .nav-arrow:hover { color: var(--text-dark); }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            text-align: center;
        }
        .day-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-light);
            padding-bottom: 0.5rem;
        }
        .day-number {
            font-size: 1rem;
            font-weight: 600;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .day-number.other-month { color: #ccc; cursor: default; }
        .day-number:not(.other-month):hover { background-color: #f0f0f0; }
        .day-number.today { border-color: var(--header-green); }
        .day-number.selected {
            background-color: var(--text-dark);
            color: var(--white);
            border-color: var(--text-dark);
        }

        /* --- Timeline --- */
        .timeline { display: flex; flex-direction: column; gap: 1rem; }
        .timeslot { display: flex; gap: 1.5rem; min-height: 60px; }
        .time {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
            width: 50px;
            position: relative;
            top: -8px;
        }
        .tasks-for-hour {
            flex: 1;
            border-left: 2px dashed var(--border-color);
            padding-left: 1.5rem;
            padding-bottom: 1rem;
        }
        .tasks-for-hour:empty { min-height: 40px; }
        .task-card {
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 1rem;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .task-card h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.25rem; }
        .task-card p { font-size: 0.9rem; color: var(--text-dark); opacity: 0.7; }
        #no-tasks-message {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
            display: none; /* Hidden by default */
        }
    </style>
</head>
<body>

    <div class="planner-container">
        <!-- Header -->
        <header class="planner-header">
            <div class="header-title">
                <h1 id="header-day">Today</h1>
                <p>Productive Day, <?php echo htmlspecialchars($userName); ?>!</p>
            </div>
            <a href="add_task.php" class="add-task-btn">Add Task</a>
        </header>

        <!-- Interactive Calendar Widget -->
        <section class="calendar-widget">
            <div class="month-navigation">
                <button id="prev-month-btn" class="nav-arrow"><i class="fas fa-chevron-left"></i></button>
                <h2 id="month-display" class="month-display"></h2>
                <button id="next-month-btn" class="nav-arrow"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="calendar-grid" id="calendar-grid-header">
                <div class="day-name">Sun</div><div class="day-name">Mon</div><div class="day-name">Tue</div><div class="day-name">Wed</div><div class="day-name">Thu</div><div class="day-name">Fri</div><div class="day-name">Sat</div>
            </div>
            <div class="calendar-grid" id="calendar-grid-body">
                <!-- Calendar days will be generated by JavaScript -->
            </div>
        </section>

        <!-- Timeline -->
        <main class="timeline" id="timeline">
            <!-- Initial tasks for today are rendered here by PHP -->
            <?php
            if (empty($initialTasks)) {
                echo '<div id="no-tasks-message" style="display:block;">No tasks for today.</div>';
            } else {
                for ($hour = 0; $hour < 24; $hour++):
                    $timeLabel = date("g A", strtotime("$hour:00"));
                ?>
                <div class="timeslot">
                    <span class="time"><?php echo $timeLabel; ?></span>
                    <div class="tasks-for-hour">
                        <?php
                        if (isset($tasksByHour[$hour])):
                            $colorIndex = $hour;
                            foreach ($tasksByHour[$hour] as $task):
                                $cardColor = $taskColors[$colorIndex % count($taskColors)];
                                $colorIndex++;
                        ?>
                        <div class="task-card" style="background-color: <?php echo $cardColor; ?>;">
                            <!-- FIX APPLIED HERE: Use 'subject' and 'task' keys -->
                            <h3><?php echo htmlspecialchars($task['subject']); ?></h3>
                            <p><?php echo htmlspecialchars($task['task']); ?></p>
                        </div>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                <?php endfor;
            }
            ?>
        </main>
        <div id="no-tasks-message" style="display:none;">No tasks for this day.</div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthDisplay = document.getElementById('month-display');
    const calendarGridBody = document.getElementById('calendar-grid-body');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');
    const timeline = document.getElementById('timeline');
    const noTasksMessage = document.getElementById('no-tasks-message');
    const headerDay = document.getElementById('header-day');

    let currentDate = new Date();

    function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(date);
        calendarGridBody.innerHTML = '';

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstDayOfMonth; i++) {
            calendarGridBody.innerHTML += `<div class="day-number other-month"></div>`;
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayEl = document.createElement('div');
            dayEl.className = 'day-number';
            dayEl.textContent = i;
            const thisDate = new Date(year, month, i);
            dayEl.dataset.date = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

            if (thisDate.getTime() === today.getTime()) {
                dayEl.classList.add('today');
                if (!calendarGridBody.querySelector('.selected')) {
                    dayEl.classList.add('selected');
                }
            }
            
            dayEl.addEventListener('click', () => {
                const selected = calendarGridBody.querySelector('.selected');
                if (selected) selected.classList.remove('selected');
                dayEl.classList.add('selected');
                fetchTasksForDate(dayEl.dataset.date);
            });

            calendarGridBody.appendChild(dayEl);
        }
    }

    async function fetchTasksForDate(dateString) {
        const selectedDate = new Date(dateString + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if(selectedDate.getTime() === today.getTime()) {
            headerDay.textContent = 'Today';
        } else {
            headerDay.textContent = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(selectedDate);
        }

        try {
            // Show a loading indicator if you want
            timeline.innerHTML = '<p style="text-align:center;">Loading tasks...</p>';
            noTasksMessage.style.display = 'none';

            const response = await fetch(`view-Task.php?fetch_tasks=true&date=${dateString}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const tasks = await response.json();
            
            if(tasks.error) {
                throw new Error(tasks.error);
            }

            renderTasks(tasks);
        } catch (error) {
            console.error('Failed to fetch tasks:', error);
            timeline.innerHTML = `<p style="text-align:center; color:red;">Could not load tasks. ${error.message}</p>`;
        }
    }

    function renderTasks(tasks) {
        timeline.innerHTML = ''; // Clear current timeline
        noTasksMessage.style.display = tasks.length === 0 ? 'block' : 'none';
        if (tasks.length === 0) return;

        const tasksByHour = {};
        tasks.forEach(task => {
            const hour = parseInt(task.start_time.substring(0, 2), 10);
            if (!tasksByHour[hour]) tasksByHour[hour] = [];
            tasksByHour[hour].push(task);
        });
        
        const taskColors = ['#FFDAB9', '#E0BBE4', '#BDE0FE', '#C1E1C1', '#FFC3A0', '#D8BFD8', '#FADADD', '#FFFACD'];

        for (let hour = 0; hour < 24; hour++) {
            const timeLabel = new Date(0, 0, 0, hour).toLocaleTimeString('en-US', { hour: 'numeric', hour12: true });
            const timeslotHTML = `
                <div class="timeslot">
                    <span class="time">${timeLabel}</span>
                    <div class="tasks-for-hour">
                        ${(tasksByHour[hour] || []).map((task, index) => {
                            const color = taskColors[(hour + index) % taskColors.length];
                            return `
                                <div class="task-card" style="background-color: ${color};">
                                    <!-- FIX APPLIED HERE: Use 'subject' and 'task' keys -->
                                    <h3>${escapeHTML(task.subject)}</h3>
                                    <p>${escapeHTML(task.task)}</p>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
            timeline.innerHTML += timeslotHTML;
        }
    }
    
    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, match => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[match]));
    }

    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    });

    // Initial render
    renderCalendar(currentDate);
});
</script>

</body>
</html>
