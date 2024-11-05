<?php
session_start();
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
    <title>SoundBro</title>
</head>

<body class="bg-light">

<!-- display navigation bar -->
<?php include 'includes/navbar.php' ?>

<!-- welcome message + image -->
<div class='h2 p-2 m-5 text-center'>Welcome to  <b class="logo fw-bolder fs-2 ">SoundBro</b>! Follow the links above to get started.</div>
<img src="logo/full.png" class="biglogo w-100">

<!-- display footer -->
<?php include 'includes/footer.html'?>

</body>
</html>