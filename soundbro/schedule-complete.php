<?php
session_start();
// if logged out redirect to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit();
}
// if not an admin, redirect to dashboard
if ($_SESSION['user_type']!='admin') {
    header("Location: dashboard.php"); 
    exit();
}

// connect to database 
include 'includes/db-connect.php';

//get alert and error
if (isset($_SESSION['alert']) and isset($_SESSION['error'])){
    $alert=$_SESSION['alert'];
    $error=$_SESSION['error'];
} else {
    // confirm schedule has been made
    $sql="SELECT * FROM Schedule";
    $scheduleExists=$conn->query($sql);
    if ($scheduleExists -> num_rows == 0){
        // schedule not made, redirect to admin dashboard
        header("Location: dashboard-admin.php"); 
        exit();
}
}

// get start date of exam
$basicinfoSQL="SELECT * FROM BasicInfo";
$basicinfoResult=$conn->query($basicinfoSQL);
$basicinfo=$basicinfoResult->fetch_assoc();
$startDate=$basicinfo['StartDate'];
// format start date appropriately
$timestamp = strtotime($startDate);
$formattedDate = date('jS M Y', $timestamp);

// is schedule published?
$IsPublished=$basicinfo['IsPublished'];

// schedule again button
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

//publish schedule button
if(isset($_POST['publish'])){
    // set IsPublished to 1
    $sql="UPDATE basicinfo SET IsPublished=1";
    $conn->query($sql);
    //redirect to admin dashboard
    header("Location: dashboard-admin.php?published=1");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href='styles.css' rel='stylesheet'>
    <link rel="icon" href="logo/favicon3.png">
    <title>Schedule Complete -SoundBro</title>
</head>

<body class="bg-light">

<!-- link to navigation bar -->
<?php include 'includes/navbar.php' ?>
<h1 class="text-center m-4 text-primary">Scheduling Results</h1>

<!-- display if alert is found -->
<?php if (isset($alert) and $alert!="") { ?>
    <div class="alert alert-info m-3">
        <?php echo $alert; ?>
    </div>
<?php } ?>


<!-- display if error is found -->
<?php if (isset($error) and $error!="") { ?>
    <div class="alert alert-danger m-3">
        <?php 
        echo "ERROR: <br>".$error; 
        ?>
    </div>
<?php } ?>

<!-- display exam schedule -->
<div class="container mt-5">
    <h2>Exam Schedule | <?php echo $formattedDate ; ?></h2>
<table class="table table-bordered">
    <thead>
        <!-- table headings -->
        <tr class='table-primary'>
            <th>Student Name</th>
            <th>Instrument</th>
            <th>Grade</th>
            <th>Accompanist Name</th>
            <th>Start Time</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // get info from DB to create correct schedule format
        $scheduleInfoSQL = "SELECT FirstName,LastName,examinees.Instrument,examinees.Grade,StartTime,Duration,AccompID
        FROM students
        JOIN examinees ON students.StudentID = examinees.ExamineeID
        JOIN schedule ON examinees.ExamineeID = schedule.ExamineeID
        JOIN families ON examinees.Instrument = families.Instrument 
        JOIN timings ON timings.Grade=examinees.Grade AND families.InstrumentFamily=timings.InstrumentFamily;";
        $scheduleInfo = $conn->query($scheduleInfoSQL);

        if ($scheduleInfo->num_rows > 0) {

            $time= new DateTime('09:00');
            while ($row = $scheduleInfo->fetch_assoc()) {

                //get start time in correct form
                $StartTime=substr($row['StartTime'],0,5);
                $duration=$row['Duration'];

                // if times dont match, add an empty row
                if ($time->format('H:i') != $StartTime){
                    // empty row to signal a break
                    echo "<tr><td colspan='5'><br></td></tr>";
                    // reset time 
                    $StartTime_object = DateTime::createFromFormat('H:i', $StartTime);
                    $time->setTime($StartTime_object->format('H'), $StartTime_object->format('i')); 
                }

                // display info on table
                echo "<tr>";
                echo "<td>{$row['FirstName']} {$row['LastName']}</td>";
                $instrument=ucwords($row['Instrument']);
                echo "<td>{$instrument}</td>";
                echo "<td>{$row['Grade']}</td>";

                $AccompID=$row['AccompID'];
                if ($AccompID){
                    // if examinee has accompanist, display their name
                    $accompInfoSQL="SELECT FirstName,LastName FROM Supervisors
                    JOIN ExamAccomps ON Supervisors.SupervisorID = ExamAccomps.AccompID
                    WHERE AccompID = '$AccompID'";
                    $accompInfo=$conn->query($accompInfoSQL);
                    $accompRow=$accompInfo->fetch_assoc();
                    echo "<td>{$accompRow['FirstName']} {$accompRow['LastName']}</td>";
                } else {
                    // empty cell to maintain format
                    echo "<td> </td>";
                }

                //display start time
                echo "<td><strong>{$StartTime}</strong></td>";
                echo "</tr>";

                //increment time
                $time->modify("+{$duration} minutes");            
            }
        } else {
            // no records found so display message
            echo "<tr><td colspan='5'>No records found</td></tr>";
        }
        ?>
    </tbody>
</table>
</div>

<!-- display buttons -->
<form method="post" action="" class='text-center'>
    <div class="d-grid ">
    <div class="form-group text-center">
        <!-- if nothing has been published -->
        <?php if ($IsPublished==0) { ?>
            <!-- schedule again button -->
            <button type="submit" name="new" class=" m-2 p-2 fs-4 w-25 btn btn-danger rounded-pill">
                <i class="bi bi-pencil-square"></i> Schedule Again
            </button>
            <!-- publish schedule button-->
            <button type="submit" name="publish" class="fs-4 m-2 p-2 btn w-25 btn-primary border  rounded-pill">
                <i class="bi bi-send"></i> Publish Schedule
            </button>
        <?php } ?>
    </div> 
    </div>
</form>

<!-- display footer -->
<?php include 'includes/footer.html'?>
</body>
</html>