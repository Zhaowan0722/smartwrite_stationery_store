<?php

/* Database configuration */
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "online_stationery_store";

/* Connect to database */
$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

/* Check connection */
if(!$conn){
    die("Database connection failed: " . mysqli_connect_error());
}

/* Set charset */
mysqli_set_charset($conn, "utf8mb4");

?>