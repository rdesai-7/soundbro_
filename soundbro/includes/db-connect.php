<?php
// connect to database using credentials
$serverName = "localhost";
$serverUsername = "root";
$serverPassword = "";    

// this should be the exact name of the database you are connecting to
$dbname = "soundbro_empty_db";

// set up connection to database
$conn = new mysqli($serverName, $serverUsername, $serverPassword, $dbname);

// unexpected fatal error 
if ($conn->connect_error) {
    // display error message
    die("Connection failed: " . $conn->connect_error);
}
?>