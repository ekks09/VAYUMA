<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "vayuma_db";


try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_errno) {
        throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
    }
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $exception) {
    error_log('VAYUMA database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('VAYUMA is temporarily unavailable. Please try again later.');
}

?>