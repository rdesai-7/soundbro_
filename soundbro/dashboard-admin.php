<?php
session_start();
// if logged out redirect to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}

// if not an admin, redirect to dashboard
if ($_SESSION['user_type']!='admin') {
    //redirect to admin page
    header("Location: dashboard.php"); 
    exit();
}

include 'includes/db-connect.php';

//get first name for welcome message
$ID=$_SESSION['user_id'];
$result=$conn->query("SELECT * FROM Supervisors WHERE SupervisorID = '$ID'");
$row = $result->fetch_assoc();
$firstName=$row['FirstName'];

$scheduleExists=False;
// check status of scheduling process
$sql="SELECT * FROM BasicInfo";
$scheduleResult=$conn->query($sql);

// has schedule been made?
if ($scheduleResult -> num_rows > 0){
    $scheduleExists=True;
}

// view existing schedule button
if(isset($_POST['viewExisting'])) {
    // redirect to schedule-complete.php
    header("Location: schedule-complete.php"); 
    exit();

}

// start new schedule button
if (isset($_POST['new'])) {
    //empty basic info and schedule
    $clearBasicInfo="TRUNCATE TABLE BasicInfo";
    $conn->query($clearBasicInfo);
    $clearSchedule="TRUNCATE TABLE Schedule";
    $conn->query($clearSchedule);
    // redirect to schedule page
    header("Location: schedule.php"); 
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
    <title>Admin Dashboard - SoundBro</title>
</head>

<body class="bg-light">
<!-- display navigation bar -->
<?php include "includes/navbar.php" ?>

<!-- display if user has just published -->
<?php if (isset($_GET['published']) and $_GET['published']==1) { ?>
    <div class="alert alert-success m-2 ">
        The schedule has been published!
    </div>
<?php } ?>

<!-- welcome message -->
<div class='p-2 m-5 text-center'>
    <h2>Welcome, <?php echo $firstName ; ?>! </h2>
    <!-- link back to dashboard -->
    <p> This is the admin page. Click <a href="dashboard.php">here</a> to go to the music teacher / accompanist login. </p>
</div>

<div class="container my-5"> 
    <div class="row justify-content-center">
        <div class="col-md-7 bg-white p-5 rounded-4 shadow">
            <h2 class="text-center mb-4 pb-4 text-primary border-3 border-bottom ">Schedule Music Exams</h2>
            <form method="post" action="" class='text-center'>
       
                <!-- view existing schedule -->
                <?php if ($scheduleExists) { ?> 
                <div class="m-2 p-2 form-group text-center">
                    <button type="submit" name="viewExisting" class=" fs-5 btn p-2 btn-primary w-75 btn-block"><i class="bi bi-eye"></i> View Existing Schedule</button>
                </div>
                <?php } ?>
            
                <!-- start new schedule --> 
                <div class="m-2 p-2 form-group text-center">
                    <button type="submit" name="new" class=" fs-5 btn p-2 btn-danger w-75 btn-block"><i class="bi bi-pencil-square"></i> Start New Schedule</button>
                </div> 
                <?php if($scheduleExists){ ?>
                    <!-- warning message -->
                    <p><strong>Warning:</strong> Starting a new schedule will delete the existing one.</p>
                <?php } ?>
            </form>
        </div>
    </div>
</div>


<!-- display footer -->
<?php include 'includes/footer.html'?>
</body>
</html>