<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['u_id'])) {
    die("Error: user not logged in.");
}

$u_id = $_SESSION['u_id'];

// ===== DELETE RECORD =====
if (isset($_GET['delete_id'], $_GET['type'])) {
    $delete_id = $_GET['delete_id'];
    $type = $_GET['type'];

    $table = ($type === 'income') ? 'income' : 'expenses';
    $id_col = ($type === 'income') ? 'i_id' : 'e_id';

    $sql_delete = "DELETE FROM $table WHERE $id_col = $1 AND u_id = $2";
    $res_delete = pg_query_params($conn, $sql_delete, array($delete_id, $u_id));

    header("Location: view.php?filter=all");
    exit;
}

// ===== ADD RECORD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'], $_POST['c_id'], $_POST['amount'], $_POST['date'])) {
    $type = $_POST['type'];
    $c_id = $_POST['c_id'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    $table = ($type === 'income') ? 'income' : 'expenses';
    $sql_insert = "INSERT INTO $table (u_id, c_id, amount, date) VALUES ($1, $2, $3, $4)";
    $res_insert = pg_query_params($conn, $sql_insert, array($u_id, $c_id, $amount, $date));

    if ($res_insert) {
        header("Location: view.php?filter=all");
        exit;
    } else {
        echo "<p style='color:red'>Error inserting record: " . pg_last_error($conn) . "</p>";
    }
} else {
    header("Location: expense.html");
    exit;
}
