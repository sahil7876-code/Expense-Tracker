<?php
// Start a session to access the user's ID
session_start();

// Include the database connection file. This gives us the $conn variable.
require_once 'connect.php';

// Check if the user is logged in. If not, redirect them to the login page.
if (!isset($_SESSION['u_id'])) {
    header("Location: index.html");
    exit();
}

// Get the user ID from the session and sanitize it for the query.
// Note: The variable name in your login.php is 'u_id', so we'll use that.
$user_id = pg_escape_string($conn, $_SESSION['u_id']);

// Query the database to get the total income for the logged-in user.
// We now query the 'income' table directly.
$sql_income = "SELECT SUM(amount) AS total_income FROM income WHERE u_id = '$user_id'";
$result_income = pg_query($conn, $sql_income);
$row_income = pg_fetch_assoc($result_income);
$total_income = $row_income['total_income'] ?? 0;

// Query the database to get the total expenses for the logged-in user.
// We now query the 'expenses' table directly.
$sql_expense = "SELECT SUM(amount) AS total_expense FROM expenses WHERE u_id = '$user_id'";
$result_expense = pg_query($conn, $sql_expense);
$row_expense = pg_fetch_assoc($result_expense);
$total_expense = $row_expense['total_expense'] ?? 0;

// Calculate the current balance.
$current_balance = $total_income - $total_expense;

// Close the database connection after all queries are done.
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ExpenseTracker</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="./images/favicon.ico" />
</head>

<body>
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
                <a href="analysis.php" class="nav-links">
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
    <div class="page-container">
        <div class="page-content">
            <div class="page-header animate-fade-in">
                <h1 class="page-title">Welcome to Your Dashboard</h1>
                <p class="page-subtitle">Manage your finances with ease. Track expenses, set goals, and stay on top of your budget.</p>
            </div>

            <div class="dashboard-grid animate-fade-in">
                <div class="dashboard-card">
                    <div class="dashboard-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>Track Expenses</h3>
                    <p>Add and monitor your daily expenses and income to stay on budget.</p>
                    <a href="expense.html" class="modern-btn btn-primary-modern">
                        <i class="fas fa-plus"></i>
                        Add Expense
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Budget Overview</h3>
                    <p>Get insights into your spending patterns and budget allocation.</p>
                    <a href="budget.php" class="modern-btn btn-success">
                        <i class="fas fa-chart-line"></i>
                        View Budget
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Financial Goals</h3>
                    <p>Set and track your savings goals to achieve financial milestones.</p>
                    <a href="goal.php" class="modern-btn btn-primary-modern">
                        <i class="fas fa-bullseye"></i>
                        Set Goals
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="dashboard-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Reports</h3>
                    <p>Generate detailed reports and analyze your financial progress.</p>
                    <a href="analysis.php" class="modern-btn btn-success">
                        <i class="fas fa-download"></i>
                        View Reports
                    </a>
                </div>
            </div>

            <div class="modern-card mt-30 animate-fade-in">
                <h3 class="text-center mb-30">Quick Overview</h3>
                <div class="expense-summary">
                    <div class="summary-card">
                        <div class="summary-amount income">₹<?php echo number_format($total_income, 2); ?></div>
                        <div class="summary-label">Total Income</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-amount expense">₹<?php echo number_format($total_expense, 2); ?></div>
                        <div class="summary-label">Total Expenses</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-amount balance">₹<?php echo number_format($current_balance, 2); ?></div>
                        <div class="summary-label">Current Balance</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-fade-in');
            });
        });
    </script>
</body>

</html>