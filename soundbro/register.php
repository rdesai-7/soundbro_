<?php
session_start();
// if logged in redirect to dashboard.php
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- connect to stylesheets -->
    <link href='https://fonts.googleapis.com/css?family=League Spartan' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href='styles.css' rel='stylesheet'>
    <link rel="icon" href="logo/favicon3.png">
    <title>Registration - SoundBro</title>
</head>

<body class="bg-light">
<!-- display navigation bar -->
<?php include "includes/navbar.php" ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 bg-white p-5 rounded-4 shadow">
            <!-- direct users to correct registration page -->
            <h2 class="text-center mb-4 text-primary">Register</h2>
            <h5 class="text-center mb-4">To register a student, click <a href='register-student.php'>here</a></h4>
            <h5 class="text-center mb-4">To register an accompanist / music teacher, click <a href='register-supervisor.php'>here</a></h4>
            <p class="h6 mt-3 text-center">
                <!-- link to login page -->
                If you have an account, login <a href="login.php">here</a>
            </p>
        </div>
    </div>
</div>

<!-- display footer -->
<?php include 'includes/footer.html'?>
</body>

</html>