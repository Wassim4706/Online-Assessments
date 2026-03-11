<?php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "login_register";
    try {
        $dbh = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    } catch (PDOException $e) {
        die("error: " . $e->getMessage());
    }
?>