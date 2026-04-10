<?php
// Database connection configuration
$host = "localhost";
$username = "Zll";
$password = "Xz83MjpM4fm8mEd5";
$dbname = "english_learning_app";

$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);