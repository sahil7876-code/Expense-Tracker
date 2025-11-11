<?php
session_start();
include 'connect.php'; // $conn should be defined here

if (!isset($_SESSION['u_id'])) {
    die("Error: user not logged in.");
}

$u_id = $_SESSION['u_id'];

// ===== DELETE RECORD =====
if (isset($_GET['delete_id']) && isset($_GET['type'])) {
    $delete_id = $_GET['delete_id'];
    $type = $_GET['type'];

    if ($type === 'income') {
        $sql_delete = "DELETE FROM income WHERE i_id = $1 AND u_id = $2";
    } else {
        $sql_delete = "DELETE FROM expenses WHERE e_id = $1 AND u_id = $2";
    }

    $result_delete = pg_query_params($conn, $sql_delete, array($delete_id, $u_id));

    if ($result_delete) {
        echo "<p class='success'>Record deleted successfully!</p>";
    } else {
        echo "<p class='error'>Error deleting: " . pg_last_error($conn) . "</p>";
    }
}

// ===== FILTER =====
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($filter === 'income') {
    $sql_view = "SELECT i.i_id AS id, c.name, i.amount, i.date, 'income' AS type
                 FROM income i
                 JOIN categories c ON i.c_id = c.c_id
                 WHERE i.u_id = $1
                 ORDER BY i.date DESC";
    $result = pg_query_params($conn, $sql_view, array($u_id));

} elseif ($filter === 'expense') {
    $sql_view = "SELECT e.e_id AS id, c.name, e.amount, e.date, 'expense' AS type
                 FROM expenses e
                 JOIN categories c ON e.c_id = c.c_id
                 WHERE e.u_id = $1
                 ORDER BY e.date DESC";
    $result = pg_query_params($conn, $sql_view, array($u_id));

} else {
    $sql_view = "SELECT e.e_id AS id, c.name, e.amount, e.date, 'expense' AS type
                 FROM expenses e
                 JOIN categories c ON e.c_id = c.c_id
                 WHERE e.u_id = $1
                 UNION ALL
                 SELECT i.i_id AS id, c.name, i.amount, i.date, 'income' AS type
                 FROM income i
                 JOIN categories c ON i.c_id = c.c_id
                 WHERE i.u_id = $1
                 ORDER BY date DESC";
    $result = pg_query_params($conn, $sql_view, array($u_id));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/view.css">
</head>
<body class="view-page-body">

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

    <div class="view-container">
        <h2 class="view-title">Your Expenses / Income</h2>
        
        <div class="filter-buttons">
            <a href="view.php?filter=all" class="filter-btn">All</a>
            <a href="view.php?filter=income" class="filter-btn">Income</a>
            <a href="view.php?filter=expense" class="filter-btn">Expense</a>
        </div>

        <div class="records-card">
            <table class="records-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && pg_num_rows($result) > 0) {
                        while ($row = pg_fetch_assoc($result)) {
                            $typeClass = ($row['type'] === 'income') ? 'badge income-badge' : 'badge expense-badge';
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['name']}</td>
                                    <td>₹" . number_format($row['amount'], 2) . "</td>
                                    <td>{$row['date']}</td>
                                    <td><span class='$typeClass'>{$row['type']}</span></td>
                                    <td><a class='delete-link' href='view.php?delete_id={$row['id']}&type={$row['type']}' onclick='return confirm(\"Are you sure?\")'>Delete</a></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='no-records'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>