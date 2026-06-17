<?php
// XAMPP default local server credentials
$host = "localhost";
$dbname = "nhc_reservation";
$username = "root";      // XAMPP's default MySQL username is 'root'
$password = "";          // XAMPP's default MySQL password is empty

try {
    // Establish a connection to the database using PDO (PHP Data Objects)
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set the PDO error mode to exception so errors throw correctly
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // If the connection fails, stop the script and print the error
    die("Database Connection failed: " . $e->getMessage());
}
?>