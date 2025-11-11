<?php
session_start();
include 'connect.php'; 

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Error: user not logged in.");
}

$u_id = $_SESSION['u_id'];
$message = "";

// ===== ADD GOAL =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_goal') {
    
    // Get form data safely
    $goal_name = $_POST['goal_name'];
    $goal_amount = $_POST['goal_amount'];
    $current_amount = $_POST['current_amount'];
    $goal_date = $_POST['goal_date'];

    // Use a parameterized query to insert the new goal
    $sql = "INSERT INTO goals (target, current, descirption, u_id, goal_date) VALUES ($1, $2, $3, $4, $5)";
    $result = pg_query_params($conn, $sql, array($goal_amount, $current_amount, $goal_name, $u_id, $goal_date));

    if ($result) {
        $message = "<p class='success-message'>Goal '$goal_name' added successfully!</p>";
    } else {
        $message = "<p class='error-message'>Error in adding goal: " . pg_last_error($conn) . "</p>";
    }
}

// ===== UPDATE CURRENT AMOUNT =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_goal') {
    $goal_id = $_POST['g_id'];
    $add_amount = $_POST['add_amount'];
    
    $sql_update = "UPDATE goals SET current = current + $1 WHERE g_id = $2 AND u_id = $3";
    $result_update = pg_query_params($conn, $sql_update, array($add_amount, $goal_id, $u_id));
    
    if ($result_update) {
        $message = "<p class='success-message'>Current amount updated successfully!</p>";
    } else {
        $message = "<p class='error-message'>Error updating amount: " . pg_last_error($conn) . "</p>";
    }
}

// ===== DELETE GOAL =====
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql_delete = "DELETE FROM goals WHERE g_id = $1 AND u_id = $2";
    $result_delete = pg_query_params($conn, $sql_delete, array($delete_id, $u_id));

    if ($result_delete) {
        $message = "<p class='success-message'>Goal deleted successfully!</p>";
    } else {
        $message = "<p class='error-message'>Error deleting goal: " . pg_last_error($conn) . "</p>";
    }
}

// Fetch all goals for the current user to display on the page
$sql_goals = "SELECT g_id, target, current, descirption, goal_date FROM goals WHERE u_id = $1 ORDER BY g_id DESC";
$goals_result = pg_query_params($conn, $sql_goals, array($u_id));
$goals = pg_fetch_all($goals_result);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Goals - Expense Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="icon" href="./images/favicon.ico" />
    <link rel="stylesheet" href="css/goal.css" />
    <style>
        .success-message { color: green; text-align: center; margin-top: 20px; }
        .error-message { color: red; text-align: center; margin-top: 20px; }
        .goal-list { margin-top: 30px; }
        .goal-item { border-bottom: 1px solid #ccc; padding: 10px 0; }
        .goal-actions { text-align: right; margin-top: 10px; }
        .goal-actions a { color: #d32f2f; text-decoration: none; margin-left: 15px; }
        .update-form { display: flex; gap: 5px; align-items: center; justify-content: flex-end; margin-top: 10px; }
        .update-form input[type="number"] { width: 100px; padding: 5px; }
        .update-form button { padding: 5px 10px; }
    </style>
</head>
<body class="goal-page">

<div class="navbar">
    <div class="nav-container">
        <a href="home.php" class="app-nav-logo">
            <i class="fas fa-wallet"></i>
                <span>ExpenseTracker</span>
        </a>
    <div class="nav-links">
        <a href="home.php" class="nav-links active">
            <i class="fas fa-home"></i>
                Dashboard
        </a>
        <a href="expense.html" class="nav-links">
            <i class="fas fa-receipt"></i>
                Expenses
        </a>
        <a href="goal.php" class="nav-links">
            <i class="fas fa-bullseye"></i>
                Goals
                </a>
                <a href="view.php" class="nav-links">
                    <i class="fas fa-eye"></i>
                    Records
                </a>
                <a href="budget.php" class="nav-links">
                    <i class="fas fa-chart-line"></i>
                    Budget
                </a>
                <a href="analysis.php" class="nav-links active">
                    <i class="fas fa-chart-pie"></i>
                    Analysis
                </a>
                <a href="index.html" class="nav-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
      </div>

    <div class="dark-container">
        <div class="dark-panel">
            <h2 class="dark-panel-title">Set Your Financial Goals</h2>
            <?php echo $message; ?>
            <form action="goal.php" method="post" class="dark-form">
                <input type="hidden" name="action" value="add_goal">
                <div class="form-group">
                    <label for="goal_name">Goal Name:</label>
                    <input type="text" id="goal_name" name="goal_name" placeholder="e.g. New Phone" required />
                </div>

                <div class="form-group">
                    <label for="goal_amount">Target Amount:</label>
                    <input type="number" id="goal_amount" name="goal_amount" placeholder="e.g. 60000" required />
                </div>

                <div class="form-group">
                    <label for="current_amount">Current Amount:</label>
                    <input type="number" id="current_amount" name="current_amount" placeholder="e.g. 10000" required />
                </div>

                <div class="form-group">
                    <label for="goal_date">Target Date:</label>
                    <input type="date" id="goal_date" name="goal_date" required />
                </div>

                <button type="submit" class="dark-btn">Add Goal</button>
            </form>
        </div>

        <div class="dark-panel goals-progress-panel">
            <h2 class="dark-panel-title">Your Progress</h2>
            <div class="goal-list">
                <?php if (!empty($goals)): ?>
                    <?php foreach ($goals as $goal): ?>
                        <?php
                            $progress_percentage = ($goal['current'] / $goal['target']) * 100;
                            $progress_percentage = min(100, $progress_percentage);
                            
                            // Calculate days remaining
                            $days_remaining = "N/A";
                            if (!empty($goal['goal_date'])) {
                                $goal_date = new DateTime($goal['goal_date']);
                                $now = new DateTime();
                                if ($goal_date > $now) {
                                    $interval = $now->diff($goal_date);
                                    $days_remaining = $interval->days . " days left";
                                } else {
                                    $days_remaining = "Overdue";
                                }
                            }
                        ?>
                        <div class="goal-item">
                            <div class="goal-item-header">
                                <h3><?php echo htmlspecialchars($goal['descirption']); ?></h3>
                                <p class="goal-amount">₹<?php echo number_format($goal['current']); ?> / ₹<?php echo number_format($goal['target']); ?></p>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%;"></div>
                            </div>
                            <p class="progress-text"><?php echo round($progress_percentage); ?>% Complete | Days Remaining: <?php echo $days_remaining; ?></p>
                            <div class="goal-actions">
                                <form action="goal.php" method="post" class="update-form">
                                    <input type="hidden" name="action" value="update_goal">
                                    <input type="hidden" name="g_id" value="<?php echo $goal['g_id']; ?>">
                                    <input type="number" name="add_amount" placeholder="Add amount" required>
                                    <button type="submit" class="update-btn">Update</button>
                                </form>
                                <a href="goal.php?delete_id=<?php echo $goal['g_id']; ?>" onclick="return confirm('Are you sure you want to delete this goal?')" class="delete-link">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center;">No goals set yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>