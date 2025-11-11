<?php
// Start a new session
session_start();

// Include your database connection file
include 'connect.php';

// Check if the form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data safely
    $username = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash the password for secure storage
   # $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Use a parameterized query to check if the email already exists
    $check_query = "SELECT u_id FROM users WHERE email = $1";
    $result = pg_query_params($conn, $check_query, array($email));

    if (pg_num_rows($result) > 0) {
        // If the email exists, show an error message
        echo "Email already registered! Please login.";
    } else {
        // Use a parameterized query to insert the new user securely
        $insert_query = "INSERT INTO users (username, email, password) VALUES ($1, $2, $3)";
        $insert = pg_query_params($conn, $insert_query, array($username, $email, $password));

        if ($insert) {
            // Registration successful, redirect to the login page
            header("Location: login_register.html");
            exit();
        } else {
            // Show a database error message
            echo "Error in registration: " . pg_last_error($conn);
        }
    }
}

// Close the database connection
pg_close($conn);
?>