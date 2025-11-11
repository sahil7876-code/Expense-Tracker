<?php
$host = "host=127.0.0.1";
$port = "port=5432";
$dbname = "dbname=ETdatabase";
$credentials = "user=postgres password=wandre";

// Attempt to connect
$conn = pg_connect("$host $port $dbname $credentials");

// Check connection
if (!$conn) {
    echo "Error: Unable to open database\n";
    // Optional: show PostgreSQL error
    echo pg_last_error();
} else {
    echo " ";
}
?>
