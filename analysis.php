<?php
session_start();
include 'connect.php'; 

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    die("Error: user not logged in.");
}

$u_id = $_SESSION['u_id'];

// Fetch all goals for the current user to display on the page
$sql_goals = "SELECT g_id, target, current, descirption FROM goals WHERE u_id = $1 ORDER BY g_id DESC";
$goals_result = pg_query_params($conn, $sql_goals, array($u_id));

// Check if the query was successful before fetching
if ($goals_result) {
    $goals = pg_fetch_all($goals_result);
} else {
    $goals = [];
}

// Fetch categories and their total expenses for the current user
$sql_cat_data = "SELECT c.name, SUM(e.amount) AS total 
                 FROM expenses e 
                 JOIN categories c ON e.c_id = c.c_id 
                 WHERE e.u_id = $1 
                 GROUP BY c.name";
$cat_data_result = pg_query_params($conn, $sql_cat_data, array($u_id));

$categories = [];
$amounts = [];
if ($cat_data_result) {
    $cat_data = pg_fetch_all($cat_data_result);
    if ($cat_data) {
        foreach ($cat_data as $row) {
            $categories[] = $row['name'];
            $amounts[] = (float)$row['total'];
        }
    }
}

// Fetch monthly income for the current user
$sql_income_data = "SELECT TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total 
                     FROM income 
                     WHERE u_id = $1 
                     GROUP BY month 
                     ORDER BY month";
$income_data_result = pg_query_params($conn, $sql_income_data, array($u_id));

// Fetch monthly expenses for the current user
$sql_expense_data = "SELECT TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total 
                      FROM expenses 
                      WHERE u_id = $1 
                      GROUP BY month 
                      ORDER BY month";
$expense_data_result = pg_query_params($conn, $sql_expense_data, array($u_id));

$months = [];
$income_values = [];
$expense_values = [];

$income_data = $income_data_result ? pg_fetch_all($income_data_result) : [];
$expense_data = $expense_data_result ? pg_fetch_all($expense_data_result) : [];

$all_months = array_unique(array_merge(
    array_column($income_data, 'month'),
    array_column($expense_data, 'month')
));
sort($all_months);

foreach ($all_months as $month) {
    $months[] = date('M Y', strtotime($month));
    $income_values[] = 0;
    $expense_values[] = 0;
}

foreach ($income_data as $row) {
    $key = array_search(date('M Y', strtotime($row['month'])), $months);
    if ($key !== false) {
        $income_values[$key] = (float)$row['total'];
    }
}

foreach ($expense_data as $row) {
    $key = array_search(date('M Y', strtotime($row['month'])), $months);
    if ($key !== false) {
        $expense_values[$key] = (float)$row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analysis - Expense Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/analysis.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="light-theme">
    <div class="navbar">
        <div class="nav-container">
            <a href="home.php" class="app-nav-logo">
                <i class="fas fa-wallet"></i>
                <span>ExpenseTracker</span>
            </a>
            <div class="nav-links">
                <a href="home.php" class="nav-links">
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
    <div class="analysis-container">
        <h2 class="analysis-title">Financial Analysis</h2>
        <p class="analysis-subtitle">View your spending habits and progress towards your goals.</p>

        <div class="card chart-card">
            <h3>Spending Breakdown by Category</h3>
            <canvas id="category-chart" style="max-height:300px;"></canvas>
        </div>

        <div class="card chart-card">
            <h3>Monthly Income vs. Expense</h3>
            <canvas id="income-expense-chart" style="max-height:300px;"></canvas>
        </div>

        <div class="card goals-progress-card">
            <h2 class="card-title">Your Progress</h2>
            <?php
            if (!empty($goals)) {
                foreach ($goals as $g) {
                    $percent = ($g['current'] / $g['target']) * 100;
                    $percent = min(100, $percent);
            ?>
                    <div class="goal-item">
                        <div class="goal-header">
                            <span class="goal-title"><?php echo htmlspecialchars($g['descirption']); ?></span>
                            <span class="goal-amount">₹<?php echo number_format($g['current'], 2); ?> / ₹<?php echo number_format($g['target'], 2); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo round($percent); ?>%;"></div>
                        </div>
                        <span class="progress-text"><?php echo round($percent); ?>% Complete</span>
                    </div>
            <?php
                }
            } else {
                echo "<p class='no-goals-message'>No goals found. Set a goal to track your progress!</p>";
            }
            ?>
        </div>
    </div>

    <script>
        // Data for category chart
        const catLabels = <?php echo json_encode($categories); ?>;
        const catAmounts = <?php echo json_encode($amounts); ?>;

        // Data for income vs expense chart
        const incomeLabels = <?php echo json_encode($months); ?>;
        const incomeAmounts = <?php echo json_encode($income_values); ?>;
        const expenseAmounts = <?php echo json_encode($expense_values); ?>;

        // Category Pie Chart
        const ctxCat = document.getElementById('category-chart').getContext('2d');
        new Chart(ctxCat, {
            type: 'pie',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Expenses by Category',
                    data: catAmounts,
                    backgroundColor: ['#3498db','#2ecc71','#f1c40f','#e67e22','#9b59b6','#1abc9c','#e84393']
                }]
            },
            options: { responsive: true, plugins:{legend:{position:'bottom'}} }
        });

        // Income vs Expense Bar Chart
        const ctxIE = document.getElementById('income-expense-chart').getContext('2d');
        new Chart(ctxIE, {
            type: 'bar', // Changed from 'line' to 'bar'
            data: {
                labels: incomeLabels,
                datasets: [
                    { label:'Income', data: incomeAmounts, backgroundColor:'#2ecc71', borderColor:'#2ecc71', borderWidth: 1 },
                    { label:'Expense', data: expenseAmounts, backgroundColor:'#e74c3c', borderColor:'#e74c3c', borderWidth: 1 }
                ]
            },
            options:{
                responsive:true,
                plugins:{legend:{position:'bottom'}},
                scales:{
                    y:{beginAtZero:true},
                    x:{grid:{display:false}}
                }
            }
        });
    </script>
</body>
</html>