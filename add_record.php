<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['u_id'])) {
    die("Error: user not logged in.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u_id = $_SESSION['u_id'];
    $type = $_POST['type'];
    $c_id = $_POST['c_id'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    if ($type === 'expense') {
        $sql = "INSERT INTO expenses (u_id, c_id, amount, date) VALUES ($1, $2, $3, $4)";
    } elseif ($type === 'income') {
        $sql = "INSERT INTO income (u_id, c_id, amount, date) VALUES ($1, $2, $3, $4)";
    } else {
        die("Invalid type selected.");
    }

    $result = pg_query_params($conn, $sql, array($u_id, $c_id, $amount, $date));

    if ($result) {
        header("Location: expense.php?filter=all"); // redirect to view records
        exit;
    } else {
        echo "Error inserting record: " . pg_last_error($conn);
    }
}
?>
