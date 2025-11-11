<?php
session_start();
include 'connect.php'; // your DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // ✅ Secure parameterized query (email only)
    $sql = "SELECT u_id, email, password FROM users WHERE email = $1";
    $result = pg_query_params($conn, $sql, array($email));

    if ($row = pg_fetch_assoc($result)) {
        // ⚠️ INSECURE: Comparing plain-text passwords directly
        if ($row['password'] === $password) {  
            // ⚠️ For a real app, you MUST use password_verify() with a hashed password!

            // ✅ Store both user id & email in session
            $_SESSION['u_id'] = $row['u_id'];
            $_SESSION['email'] = $row['email'];

            header("Location:home.php");
            exit();
            
        } else {
            // Invalid password, show a generic error message
            echo "Invalid email or password!";
        }
    } else {
        // User not found, show a generic error message
        echo "Invalid email or password!";
    }
}
?>