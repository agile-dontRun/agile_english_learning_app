<?php

$servername = "localhost";
$username = "";
$password = "";
$dbname = "english_learning_app";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connect error: " . $conn->connect_error);
}


$conn->set_charset("utf8");

?>