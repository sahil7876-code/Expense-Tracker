<?php
session_start();
include 'connect.php'; 

if (!isset($_SESSION['u_id'])) {
    die("Error: user not logged in.");
}

$u_id = $_SESSION['u_id'];
$message = "";

// ===== HANDLE DELETE =====
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql_delete = "DELETE FROM budgets WHERE b_id = $1 AND u_id = $2";
    $result_delete = pg_query_params($conn, $sql_delete, array($delete_id, $u_id));

    if ($result_delete) {
        $_SESSION['message'] = "<p class='success-message'>Budget deleted successfully!</p>";
    } else {
        $_SESSION['message'] = "<p class='error-message'>Error deleting budget: " . pg_last_error($conn) . "</p>";
    }
    header("Location: budget.php");
    exit();
}

// ===== HANDLE POST =====
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = trim($_POST['amount'] ?? '');
    $category_name = trim($_POST['category'] ?? ''); 
    $month = trim($_POST['month'] ?? '');
    $note = trim($_POST['note'] ?? '');

    // Validation
    if (empty($category_name)) {
        $_SESSION['message'] = "<p class='error-message'>Please select a category.</p>";
        header("Location: budget.php");
        exit();
    }

    if (!is_numeric($amount) || $amount <= 0) {
        $_SESSION['message'] = "<p class='error-message'>Invalid amount. Must be a positive number.</p>";
        header("Location: budget.php");
        exit();
    }
    $amount = floatval($amount);

    if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
        $_SESSION['message'] = "<p class='error-message'>Invalid month format. Use YYYY-MM.</p>";
        header("Location: budget.php");
        exit();
    }

    if (strlen($note) > 255) {  // Assuming DB column limit; adjust if needed
        $note = substr($note, 0, 255);
    }

    // Get category ID
    $sql_category = "SELECT c_id FROM categories WHERE name = $1";
    $cat_result = pg_query_params($conn, $sql_category, array($category_name));

    if ($cat_result && pg_num_rows($cat_result) > 0) {
        $cat_row = pg_fetch_assoc($cat_result);
        $c_id = intval($cat_row['c_id']);

        // Prevent duplicates: check if budget already exists for user/category/month
        $sql_check = "SELECT b_id FROM budgets WHERE u_id = $1 AND c_id = $2 AND month = $3";
        $check_result = pg_query_params($conn, $sql_check, array($u_id, $c_id, $month));

        if (pg_num_rows($check_result) > 0) {
            $_SESSION['message'] = "<p class='error-message'>Budget for '$category_name' for this month already exists!</p>";
        } else {
            $sql = "INSERT INTO budgets (amount, c_id, u_id, month, note) VALUES ($1, $2, $3, $4, $5)";
            $result = pg_query_params($conn, $sql, array($amount, $c_id, $u_id, $month, $note));

            if ($result) {
                $_SESSION['message'] = "<p class='success-message'>Budget for '$category_name' added successfully!</p>";
            } else {
                $_SESSION['message'] = "<p class='error-message'>Error in adding budget: " . pg_last_error($conn) . "</p>";
            }
        }
    } else {
        $_SESSION['message'] = "<p class='error-message'>Selected category not found.</p>";
    }

    header("Location: budget.php");
    exit();
}

// Display and clear message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch categories for dropdown
$sql_categories = "SELECT name, c_id FROM categories WHERE type = 'expense' ORDER BY name ASC";
$categories_result = pg_query($conn, $sql_categories);
if (!$categories_result) {
    error_log("Categories query failed: " . pg_last_error($conn));
    $categories = [];
    $message .= "<p class='error-message'>Error loading categories. Please try again.</p>";
} else {
    $categories = pg_fetch_all($categories_result) ?: [];
    if (empty($categories)) {
        $message .= "<p class='error-message'>No expense categories available. Add some first.</p>";
    }
}

// Fetch all budgets for current user
$sql_budgets = "SELECT b.b_id, b.amount, b.month, b.note, c.name as category_name, c.c_id
                FROM budgets b
                JOIN categories c ON b.c_id = c.c_id
                WHERE b.u_id = $1
                ORDER BY b.month DESC, b.b_id DESC";
$budgets_result = pg_query_params($conn, $sql_budgets, array($u_id));
if (!$budgets_result) {
    error_log("Budgets query failed: " . pg_last_error($conn));
    $message .= "<p class='error-message'>Error loading budgets: " . pg_last_error($conn) . "</p>";
    $budgets = [];
} else {
    $budgets = pg_fetch_all($budgets_result) ?: [];
}

// ===== Optimize spent calculation =====
// Fetch all expenses in one query
$spent_amounts = [];
if (!empty($budgets)) {
    $sql_expenses = "SELECT c_id, TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as spent
                     FROM expenses
                     WHERE u_id = $1
                     GROUP BY c_id, TO_CHAR(date, 'YYYY-MM')";
    $expenses_result = pg_query_params($conn, $sql_expenses, array($u_id));

    if (!$expenses_result) {
        error_log("Expenses query failed: " . pg_last_error($conn));
        $spent_amounts = [];  // Fallback to empty
    } else {
        while ($row = pg_fetch_assoc($expenses_result)) {
            $key = $row['c_id'] . '_' . $row['month'];
            $spent_amounts[$key] = (float) $row['spent'];
        }
    }

    foreach ($budgets as &$budget) {
        $key = $budget['c_id'] . '_' . $budget['month'];
        $budget['spent'] = $spent_amounts[$key] ?? 0.0;
    }
    unset($budget);  // Unset reference to avoid issues
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Set Budget - Expense Tracker</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/budget.css" />
<style>
.success-message { color: green; text-align: center; margin-top: 20px; }
.error-message { color: red; text-align: center; margin-top: 20px; }
</style>
</head>
<body class="light-theme">

<div class="navbar">
  <div class="nav-container">
      <a href="home.php" class="app-nav-logo"><i class="fas fa-wallet"></i> ExpenseTracker</a>
      <div class="nav-links">
          <a href="home.php"><i class="fas fa-home"></i> Dashboard</a>
          <a href="expense.php"><i class="fas fa-receipt"></i> Expenses</a>
          <a href="goal.php"><i class="fas fa-bullseye"></i> Goals</a>
          <a href="view.php"><i class="fas fa-eye"></i> Records</a>
          <a href="budget.php" class="active"><i class="fas fa-chart-line"></i> Budget</a>
          <a href="analysis.php"><i class="fas fa-chart-pie"></i> Analysis</a>
          <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
  </div>
</div>

<div class="dark-container">
  <div class="dark-panel">
    <h2 class="dark-panel-title">Set a Monthly Budget</h2>
    <?php echo $message; ?>
    <form action="budget.php" method="post" class="dark-form">
      <div class="form-group">
        <label for="category">Select Category:</label>
        <select id="category" name="category" required>
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $category): ?>
              <option value="<?php echo htmlspecialchars($category['name']); ?>">
                  <?php echo htmlspecialchars($category['name']); ?>
              </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="amount">Budget Amount:</label>
        <input type="number" id="amount" name="amount" placeholder="e.g., 5000" required min="1" step="0.01" />
      </div>
      <div class="form-group">
        <label for="month">Month:</label>
        <input type="month" id="month" name="month" required />
      </div>
      <div class="form-group">
        <label for="note">Note (Optional):</label>
        <textarea id="note" name="note" rows="3" placeholder="e.g., Budget for groceries" maxlength="255"></textarea>
      </div>
      <button type="submit" class="dark-btn">Set Budget</button>
    </form>
  </div>

  <div class="budget-display-panel">
    <h2 class="budget-display-panel-title">Your Budgets</h2>
    <div class="budget-list">
      <?php if (!empty($budgets)): ?>
          <?php foreach ($budgets as $budget): ?>
              <?php 
                  $spent_amount = (float) ($budget['spent'] ?? 0.0);
                  $budget_amount = (float) $budget['amount'];
                  $money_left = $budget_amount - $spent_amount;
                  $progress_percent = ($budget_amount > 0) ? ($spent_amount / $budget_amount) * 100 : 0;
                  $progress_percent = min(100, max(0, $progress_percent));
              ?>
              <div class="budget-item">
                  <div class="budget-item-header">
                      <h3><?php echo htmlspecialchars($budget['category_name']); ?> (<?php echo htmlspecialchars($budget['month']); ?>)</h3>
                      <a href="budget.php?delete_id=<?php echo htmlspecialchars($budget['b_id']); ?>" onclick="return confirm('Are you sure?')" class="delete-link">Delete</a>
                  </div>
                  <p class="budget-info">Budget: ₹<?php echo number_format($budget_amount, 2); ?></p>
                  <p class="budget-info">Spent: ₹<?php echo number_format($spent_amount, 2); ?></p>
                  <div class="progress-container">
                      <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                  </div>
                  <p class="budget-left">Money Left: ₹<?php echo number_format($money_left, 2); ?></p>
                  <?php if (!empty(trim($budget['note'] ?? ''))): ?>
                      <p class="budget-note">Note: <?php echo htmlspecialchars($budget['note']); ?></p>
                  <?php endif; ?>
              </div>
          <?php endforeach; ?>
      <?php else: ?>
          <p style="text-align:center;">No budgets set yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>