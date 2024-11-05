<?php
session_start();
// if logged out redirect to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//connect to database
include 'includes/db-connect.php';

// get user's first name
$ID=$_SESSION['user_id'];
if ($_SESSION['user_type']=='student'){
    $result=$conn->query("SELECT FirstName FROM Students WHERE StudentID = '$ID'");
} else {
    $result=$conn->query("SELECT FirstName FROM Supervisors WHERE SupervisorID = '$ID'");
}
$row = $result->fetch_assoc();
$firstName=$row['FirstName'];
$message="";

// display the exam schedule
function displaySchedule($conn,$result,$teacher){
    while ($row = $result->fetch_assoc()) {

        //get start time in correct form
        $StartTime=substr($row['StartTime'],0,5);

        // display info on table
        echo "<tr>";
        echo "<td>{$row['student_fname']} {$row['student_lname']}</td>";
        $instrument=ucwords($row['Instrument']);
        echo "<td>{$instrument}</td>";
        echo "<td>{$row['Grade']}</td>";
        if ($teacher){
            echo "<td><strong>{$row['teacher_fname']} {$row['teacher_lname']}</strong></td>";
        }
        $AccompID=$row['AccompID'];
        if ($AccompID){
            // if examinee has an accompanist, display their name
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
    }
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
    <title>Dashboard - SoundBro</title>
</head>

<body class="bg-light">
<!-- display navigation bar -->
<?php include "includes/navbar.php" ?>

<!-- Display welcome message -->
<h1 class ="p-2 mt-4 text-center">Dashboard</h1>
<h3 class ="mb-4 text-center text-secondary">Welcome, <?php echo $firstName ; ?>! </h3>
<hr class="border-dark border-2">

<!-- display additional message for Ms Pearcey (admin) -->
<?php if ($_SESSION['user_type']=='admin') { ?>
    <div class="container p-3 my-5 border">
    <p class="fs-5">
        Welcome! If you are here for admin purposes, click <a href="dashboard-admin.php">here.</a> <br>
        If not, stay on this page to see music teacher / accompanist info.
    </p>
    </div>
    <hr>
<?php }?>

<?php

// get basic info + start date
$publishSQL="SELECT IsPublished,StartDate FROM BasicInfo";
$publishResult=$conn->query($publishSQL);
if ($publishResult and $publishResult->num_rows > 0){
    $publishInfo=$publishResult->fetch_assoc();
    $IsPublished=$publishInfo['IsPublished'];
    $startDate=$publishInfo['StartDate'];
    $timestamp = strtotime($startDate);
    $formattedDate = date('jS M Y', $timestamp);
} else {
    // schedule not created yet, so set the variable $IsPublished to 0
    $IsPublished = 0;
}

// if user is a student
if ($_SESSION['user_type']=='student'){
    $results_message="If you have recently taken an exam, results will be displayed on the 
    music notice board, or you will be contacted by your music teacher.";
    //is schedule published yet
    if ($IsPublished==1){ ?>
        <!-- display STUDENT exam schedule -->
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
                $examineeSQL="SELECT students.FirstName AS student_fname,students.LastName AS student_lname,Instrument,Grade,StartTime,examinees.AccompID
                FROM schedule 
                JOIN examinees ON schedule.ExamineeID=examinees.ExamineeID 
                JOIN students ON students.StudentID=examinees.ExamineeID 
                WHERE examinees.ExamineeID='$ID';";
                $examineeResult = $conn->query($examineeSQL);
                // if result is not empty
                if ($examineeResult and $examineeResult->num_rows > 0) {
                    // display schedule
                    displaySchedule($conn,$examineeResult,false);
                } else {
                    // no records found, display message
                    echo "<tr><td colspan='5'>
                    You aren't scheduled to play in the upcoming exams.<br>$results_message</td></tr>";
                }
                ?>
            </tbody>
        </table>
        </div><?php
    } else { 
        // not published yet
        $message.="Nothing has been scheduled yet. Check again later.<br>$results_message";
    }

// user must be a supervisor / admin
} else {
    // is schedule published?
    if ($IsPublished==1){ ?>
    
        <!-- display TEACHER exam schedule -->
        <div class="container my-5">
            <h2>Exam Schedule | <?php echo $formattedDate ; ?> | Teacher Info</h2>
        <table class="table table-bordered">
            <thead>
                <!-- table headings -->
                <tr class='table-primary'>
                    <th>Student Name</th>
                    <th>Instrument</th>
                    <th>Grade</th>
                    <th>Teacher Name</th>
                    <th>Accompanist Name</th>
                    <th>Start Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // get info from DB to create correct schedule format
                $teacherSQL="SELECT students.FirstName AS student_fname,students.LastName AS student_lname,Instrument,Grade,StartTime,examinees.AccompID,ScheduleID,supervisors.FirstName AS teacher_fname,supervisors.LastName AS teacher_lname
                FROM schedule 
                JOIN examinees ON schedule.ExamineeID=examinees.ExamineeID 
                JOIN supervisors ON supervisors.SupervisorID=examinees.SupervisorID 
                JOIN students ON students.StudentID=examinees.ExamineeID 
                WHERE supervisors.SupervisorID='$ID'
                ORDER BY StartTime ASC";
                $teacherResult = $conn->query($teacherSQL);
                // if result is not empty
                if ($teacherResult and $teacherResult->num_rows > 0) {
                    // display schedule
                    displaySchedule($conn,$teacherResult,true);
                } else {
                    // no records found, display message
                    echo "<tr><td colspan='6'>
                    You aren't teaching any upcoming examinees.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        </div>
     
        <!-- display ACCOMPANIST exam schedule -->
        <div class="container my-5">
            <h2>Exam Schedule | <?php echo $formattedDate ; ?> | Accompanist Info</h2>
        <table class="table table-bordered">
            <thead>
                <tr class='table-primary'>
                    <!-- table headings -->
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
                $accompSQL="SELECT students.FirstName AS student_fname,students.LastName 
                AS student_lname,Instrument,Grade,StartTime,examinees.AccompID,examinees.ExamineeID,ScheduleID
                FROM schedule
                JOIN examinees ON schedule.ExamineeID=examinees.ExamineeID
                JOIN examaccomps ON examinees.AccompID=examaccomps.AccompID
                JOIN supervisors ON examaccomps.AccompID=supervisors.SupervisorID
                JOIN students ON students.StudentID=examinees.ExamineeID
                WHERE supervisors.SupervisorID='$ID'
                ORDER BY StartTime ASC";
                $accompResult = $conn->query($accompSQL);
                // if result is not empty
                if ($accompResult and $accompResult->num_rows > 0) {
                    // display schedule
                    displaySchedule($conn,$accompResult,false);
                } else {
                    // no records found, display message
                    echo "<tr><td colspan='5'>
                    You aren't accompanying any upcoming exams.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        </div><?php
    } else {
        // nothing published yet
        $message.="Nothing has been scheduled yet. Check again later.";
    }
}
?>

<!-- display messages -->
<div class="container p-3 ">
    <p class="text-center">
        <?php echo $message."<br>";

        // add a horizontal line to break up the text
        if ($message != ""){ ?>
            <hr>
        <?php } ?>

        <p class="fs-5 text-center">
            Email Ms. Pearcey at <strong>admin@email.com</strong> if you have any concerns.
        </p>
    </p>
</div>
<!-- display footer -->
<?php include 'includes/footer.html'?>
</body>

</html>