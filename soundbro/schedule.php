<?php
// Start the session to store user data
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

// return ideal time of next break
function nextBreak($numCompletedBreaks){
    $error = "";
    // validate input
    if ($numCompletedBreaks >= 0 and $numCompletedBreaks <= 3) {
        // set next break time based on number of breaks completed
        switch($numCompletedBreaks){
            case 0:
                $breaktime = new DateTime('10:45');
                break;
            case 1:
                $breaktime = new DateTime('12:30');
                break;
            case 2:
                $breaktime = new DateTime('15:15');
                break;
            case 3:
                $breaktime = new DateTime('17:00');
                break;
        }
    } else {
        // create error message
        $error = "Invalid number of completed breaks.<br> ";
        // set breaktime to something to prevent errors
        $breaktime = new DateTime('23:00');
    }
    return [$breaktime, $error];
}

// decide if schedule is meeting TCL regulations or not
function meetingRegulations($time,$dayExamTime,$sessionTime,$numCompletedBreaks){
    $latestTime = new DateTime('17:00');
    $latestLunchTime = new DateTime('12:45');

    // decide if time is valid
    $timeValid = $time->format('H:i') <= $latestTime->format('H:i');
    // decide if a lunch break must be scheduled or not
    $lunchValid = ($numCompletedBreaks != 1) || ($time->format('H:i') <= $latestLunchTime->format('H:i'));
    // decide if the current session is too long
    $sessionValid = $sessionTime <= 120;
    // decide if too many exams have been scheduled
    $dayExamValid = $dayExamTime <= 390;

    return $timeValid and $lunchValid and $sessionValid and $dayExamValid ;
}

// schedule breaks 
function addBreak($time,$numCompletedBreaks){
    $eod=False;
    $sessionTime=0; // scheduling a break, so current session ends
    $error="";
    // validate input
    if ($numCompletedBreaks >= 0 and $numCompletedBreaks <= 3) {
        switch($numCompletedBreaks){
            case 0:                
                // 15 min break
                $time->modify('+15 minutes');
                $numCompletedBreaks+=1;
                break;
            case 1:
                // 60 min lunch break
                $time->modify('+60 minutes');
                $numCompletedBreaks+=1;
                break;
            case 2:
                // 15 min break
                $time->modify('+15 minutes');
                $numCompletedBreaks+=1;
                break;
            case 3:
                // end of day
                $eod=True;
                break;
        }
    } else {
        // invalid numCompletedBreaks
        $error = "Invalid number of completed breaks. <br>";
    }
    return [$time,$sessionTime,$numCompletedBreaks,$eod,$error];
}

// get field in ExamAccomps corresponding to the time
function getAccompField($time){

    // round down minutes to nearest 30
    $minute = $time->format('i');
    $roundedMinute = floor($minute / 30) * 30;

    // create new object with rounded minutes
    $roundDownTime = clone $time;
    $roundDownTime->setTime(
        $time->format('H'),
        $roundedMinute,
        $time->format('s')
    );

    // format time as 'StartHHMM'
    $formattedTime = 'Start' . $roundDownTime->format('Hi');
    return [$roundDownTime,$formattedTime];
}

// get data about an examinee from DB
function getExamineeInfo($ExamineeID,$conn){

     // get Duration + AccompID of examinees exam
     $examineeinfo="SELECT Duration,Examinees.AccompID
     FROM timings 
     JOIN families ON families.InstrumentFamily = timings.InstrumentFamily 
     JOIN examinees ON examinees.Instrument = families.Instrument AND timings.Grade=examinees.Grade 
     WHERE ExamineeID='$ExamineeID'";
     $examineeResult=$conn->query($examineeinfo);
     $row=$examineeResult->fetch_assoc();
     // retrieve variables from SQL result
     $duration=$row['Duration']; 
     $accompID=$row['AccompID'];

     return [$duration,$accompID];
}

// determine if an examinee's accompanist is available at a given time
function accompAvailable($ExamineeID,$time,$conn){

    // get info about examinee 
    [$duration,$accompID]=getExamineeInfo($ExamineeID,$conn);

    // create object for midpoint time
    $midTime = clone $time;
    $halfDuration=$duration / 2;
    $midTime->modify("+{$halfDuration} minutes");

    // create object for end time
    $endTime = clone $time;
    $endTime->modify("+{$duration} minutes");

    //get field name for start/mid/end time 
    [$roundDownTime,$startField]=getAccompField($time); // $time is the start time of the exam
    [$roundDownTime,$midField]=getAccompField($midTime);
    [$roundDownTime,$endField]=getAccompField($endTime);
    //roundDownTime is ignored (it is returned to simplify the function recalculateOrder)

    // if exams run past 5pm
    if (($startField=='Start1700' || $midField=='Start1700' || $endField=='Start1700') 
        and ($endTime->format('H:i')!=='17:00')) {
        // this breaks regulations so return false
        return false;
    } else {
        if (!$accompID){
            // examinee has no accompanist, return true
            return true;
        } else {
            if ($endTime->format('H:i')=='17:00'){
                // if exam ends at 5pm, remove 'Start1700' 
                $endField=$midField;
            }
            // get availability from ExamAccomps
            $availabilitySQL="SELECT $startField,$midField,$endField
            FROM ExamAccomps
            WHERE AccompID = $accompID";
            $availabilityResult=$conn->query($availabilitySQL);
            $availabilityRow=$availabilityResult->fetch_assoc();

            // store 3 variables to indicate availability at each point
            $available_start = $availabilityRow[$startField];
            $available_mid = $availabilityRow[$midField];
            $available_end = $availabilityRow[$endField];

            if ($available_start==1 and $available_mid==1 and $available_end==1){
                // if accompanist is available the during the whole exam
                return true;
            } else {
                // if accompanist is not available during exam at any point
                return false;
            }
        }
    }
}

// sort accompanists appropriately
function freeTimeSort($rowA, $rowB) {
    $leniency_threshold=150;

    if ($rowA[3] > $leniency_threshold and $rowB[3] > $leniency_threshold){
        // free time is greater than threshold, return 0 to keep order unchanged
        return 0;
    } else {
        // free time is below threshold, sort by free time to maximise efficiency
        return $rowA[3] - $rowB[3];
    }
}

// sort all accompanists by free time at the start
function freeTimeSort_initial($rowA,$rowB){
    // sort ascending by free time
    return $rowA[3] - $rowB[3];
}

// sort examinees by family, instrument and then grade
function instrumentSort($a, $b) {
    // sort by instrument family
    $result = strcmp($a['InstrumentFamily'], $b['InstrumentFamily']);

    // if family is the same, sort by instrument
    if ($result === 0) {
        $result = strcmp($a['Instrument'], $b['Instrument']);
    }

    // if instrument is the same, sort ascending by grade
    if ($result === 0) {
        $result = $a['Grade'] - $b['Grade'];
    }

    return $result;
}

// determine order of priority for scheduling examinees
function recalculateOrder($examineeIDs,$time,$conn){
    $accompIDs=[];
    $error="";

    //calculate total exam time
    for ($i = 0; $i < count($examineeIDs); $i++) {
        $ExamineeID=$examineeIDs[$i]; 
        // get AccompID and Duration for examinee
        [$duration,$accompID]=getExamineeInfo($ExamineeID,$conn);

        // if accompID null, do not add to accompIDs
        if ($accompID){

            $accompIndex = null;
            // iterate through each accompanist
            foreach ($accompIDs as $rIndex => $row) {
                // Check if this row matches the accompID
                if ($row[0] === $accompID) {
                    // increment by duration
                    $accompIndex = $rIndex;
                    $accompIDs[$accompIndex][1] += $duration;
                    break;
                }
            }
            // If accompID not yet in accompIDs
            if ($accompIndex === null) {
                // add to array, and increment by duration
                $accompIDs[]=[$accompID,0,0,0];
                $accompIndex=count($accompIDs)-1;
                $accompIDs[$accompIndex][1] += $duration;
            }
        }
    }
    
    // calculate time until next 30min slot
    [$difference,$currentField]=timeDifference($time);

    if ($time->format('H:i')!="17:00"){
        //calculate available time
        for ($i = 0; $i < count($accompIDs); $i++){

            // get availability for accompanist
            $accompID=$accompIDs[$i][0];
            $availabilitySQL="SELECT Start0900,Start0930,Start1000,Start1030,Start1100,
            Start1130,Start1200,Start1230,Start1300,Start1330,Start1400,
            Start1430,Start1500,Start1530,Start1600,Start1630
            FROM ExamAccomps
            WHERE AccompID=$accompID";

            $availabilityResult=$conn->query($availabilitySQL);

            // get associative array of availability
            $availability=$availabilityResult->fetch_assoc();
            
            // if accomp is currently available, add $difference to their available time
            if ($availability[$currentField]==1){
                $accompIDs[$i][2]+=$difference;
            } 

            // shorten array to everything after currentField
            $currentIndex = array_search($currentField,array_keys($availability));
            $availability=array_slice($availability,$currentIndex+1);

            foreach ($availability as $key => $value) {
                //increment available time by 30 for every available slot
                if ($availability[$key]==1){
                    $accompIDs[$i][2]+=30;
                }
            }
        }
    }

    //calculate free time
    for ($i = 0; $i < count($accompIDs); $i++){
        $accompID=$accompIDs[$i][0];
        $examTime = $accompIDs[$i][1];
        $availableTime = $accompIDs[$i][2];
        $freeTime = $availableTime - $examTime;

        if ($freeTime < 0){
            $minutes_more=$freeTime*-1;

            //get name from DB
            $nameSQL="SELECT FirstName, LastName
            FROM Supervisors
            WHERE SupervisorID = $accompID";
            $nameResult=$conn->query($nameSQL);
            $nameRow=$nameResult->fetch_assoc();
            $firstName=$nameRow['FirstName'];
            $lastName=$nameRow['LastName'];

            //update error message with relevant info
            $error.="$firstName $lastName has too many exams / is not available enough.
            They need <u>at least</u> $minutes_more minutes more. <br>";
        }
        // update array to include freeTime
        $accompIDs[$i][3]=$freeTime;

    }
    
    // sort ascending by free time 
    if ($time->format('H:i')=='09:00'){
        //sort strictly at the start
        usort($accompIDs,'freeTimeSort_initial');
    } else {
        //sort with some leniency
        usort($accompIDs,'freeTimeSort');
    }

    $newExamineeIDs=[];
    //convert sorted accompIDs into sorted examineeIDs
    for ($i = 0; $i < count($accompIDs); $i++){
        $temp_array=[];
    
        for ($j=0; $j < count($examineeIDs); $j++){

            //find examinees in examineeIDs with current accompanist
            $accompSQL="SELECT AccompID FROM Examinees 
            WHERE ExamineeID='$examineeIDs[$j]'";
            $accompResult=$conn->query($accompSQL);
            $accompRow=$accompResult->fetch_assoc();
            $examinee_accomp=$accompRow['AccompID'];

            if ($examinee_accomp==$accompIDs[$i][0]){
                //add their info to temp_array
                $instrumentSQL="SELECT Examinees.ExamineeID,
                Families.InstrumentFamily,Families.Instrument,Timings.Grade
                FROM timings 
                JOIN families ON families.InstrumentFamily = timings.InstrumentFamily 
                JOIN examinees ON examinees.Instrument=families.Instrument 
                AND timings.Grade=examinees.Grade
                WHERE examinees.ExamineeID=$examineeIDs[$j]";
                $instrumentResult=$conn->query($instrumentSQL);
                $temp_array[]=$instrumentResult->fetch_assoc();
            }
        }
       
        //sort by family > instrument > grade
        usort($temp_array, 'instrumentSort');

        //temp_array is added to the end of newExamineeIDs
        $newExamineeIDs = array_merge($newExamineeIDs, $temp_array);
        //re-index the array so it is formatted correctly
        $newExamineeIDs = array_values($newExamineeIDs);
    }

    //repeat for examinees with no accompanist
    $temp_array=[];
    for ($i=0; $i < count($examineeIDs); $i++){

        //find examinees in examineeIDs with no accompanist
        $accompSQL="SELECT AccompID FROM Examinees 
        WHERE ExamineeID='$examineeIDs[$i]'";
        $accompResult=$conn->query($accompSQL);
        $accompRow=$accompResult->fetch_assoc();
        $examinee_accomp=$accompRow['AccompID'];

        if ($examinee_accomp==null){
            //add their info to temp_array
            $instrumentSQL="SELECT Examinees.ExamineeID,Families.InstrumentFamily,
            Families.Instrument,Timings.Grade 
            FROM timings 
            JOIN families ON families.InstrumentFamily = timings.InstrumentFamily 
            JOIN examinees ON examinees.Instrument = families.Instrument 
            AND timings.Grade=examinees.Grade
            WHERE examinees.ExamineeID='$examineeIDs[$i]'";
            $instrumentResult=$conn->query($instrumentSQL);
            $temp_array[]=$instrumentResult->fetch_assoc();
        }
    }

    //sort by family > instrument > grade
    usort($temp_array, 'instrumentSort');

    //temp_array is added to the end of newExamineeIDs
    $newExamineeIDs = array_merge($newExamineeIDs, $temp_array);
    //re-index the array so it is formatted correctly
    $newExamineeIDs = array_values($newExamineeIDs);

    //extract just the ExamineeIDs
    $finalExamineeIDs = [];
    foreach ($newExamineeIDs as $subArray) {
        $finalExamineeIDs[] = $subArray['ExamineeID'];
    }
    //var_dump($accompIDs);
    return [$finalExamineeIDs,$error];
}

// find number of minutes until next 30min slot
function timeDifference($time){
    // get rounded time, and field corresponding to time
    [$roundDownTime,$currentField]=getAccompField($time);
    //adjust roundDownTime so it stores time of next field
    $roundDownTime->modify('+30 minutes');

    //find difference between current time and next 30min slot
    $difference = $time->diff($roundDownTime);
    $difference=$difference->i; //format so it stores number of minutes
    return [$difference,$currentField];
}

// Schedule exams
function schedule($conn){

    // empty Schedule table in DB
    $emptySchedule="TRUNCATE TABLE Schedule";
    $conn->query($emptySchedule);

    // initialise variables/classes/constants
    $numCompletedBreaks=0;
    $time = new DateTime('09:00');
    $sessionTime=0;
    $dayExamTime=0;
    $eod=false;
    $error="";
    $interval = new DateInterval("PT10M"); 

    //create examineeIDs
    $examineeSQL="SELECT ExamineeID FROM Examinees";
    $examineeResult=$conn->query($examineeSQL);
    $examineeIDs = [];
    while ($row = $examineeResult->fetch_assoc()) {
        $examineeIDs[] = $row['ExamineeID'];
    }

    // loop until everyone is scheduled / end of day / error is found
    while (count($examineeIDs)!=0 and $eod==false and $error==""){

        //sort examinees by priority and other factors
        [$examineeIDs,$miniError]=recalculateOrder($examineeIDs,$time,$conn);
        $error.= $miniError; //append miniError to error

        //search for available accompanist
        $available=false;
        $i=0;
        // loop until available accompanist is found / end of list
        while ($available == false and $i<count($examineeIDs)){
            $available = accompAvailable($examineeIDs[$i],$time,$conn);
            if (!$available){
                //iterate through examineeIDs
                $i++;
            }
        }

        // if available accompanist is found
        if($available){
            $ExamineeID=$examineeIDs[$i];

            //get duration of examinee
            [$duration,$accompID] = getExamineeInfo($ExamineeID,$conn);
            
            //validate the possible schedule change
            $newTime= clone $time;
            $newTime->modify("+{$duration} minutes");
            $newSessionTime = $sessionTime + $duration;
            $newDayExamTime = $dayExamTime + $duration;
            $isValid = meetingRegulations($newTime,$newDayExamTime,
            $newSessionTime,$numCompletedBreaks);

            // get ideal time of next break
            [$nextBreak,$miniError]=nextBreak($numCompletedBreaks);
            $error.= $miniError;
            // get latest time to schedule a break
            $upperBound = $nextBreak->add($interval);

            // if new schedule is valid and it is not time for a break
            if ($newTime<=$upperBound and $isValid){
                // Schedule an exam

                // insert into schedule
                $StartTime=$time->format('H:i');
                $schedulingSQL="INSERT INTO Schedule (StartTime,ExamineeID)
                VALUES ('$StartTime','$ExamineeID')";
                $conn->query($schedulingSQL);

                // remove examinee from examineeIDs
                unset($examineeIDs[$i]);
                $examineeIDs=array_values($examineeIDs);

                // update time variables
                $time=$newTime;
                $sessionTime=$newSessionTime;
                $dayExamTime=$newDayExamTime;

            } else {
                // Schedule a break
                [$time,$sessionTime,$numCompletedBreaks,$eod,$miniError]
                =addBreak($time,$numCompletedBreaks);
                $error.= $miniError;
            }
        } else {
            //find bounds for allowed break times
            [$nextBreak,$miniError]=nextBreak($numCompletedBreaks);
            $error.= $miniError;
            $upperBound = $nextBreak->add($interval);
            $lowerBound = $nextBreak->sub($interval);

            //can a break be scheduled now?
            if ($time >= $lowerBound and $time <= $upperBound){
                [$time,$sessionTime,$numCompletedBreaks,$eod,$miniError]
                =addBreak($time,$numCompletedBreaks);
                $error.= $miniError;
            } else {
                //find the time difference until the next 30 min slot
                [$difference,$currentField]=timeDifference($time);

                //validate the possible schedule change
                $newTime= clone $time;
                $newTime->modify("+{$difference} minutes");
                $newSessionTime = $sessionTime + $difference;
                $isValid = meetingRegulations($newTime,$dayExamTime,
                $newSessionTime,$numCompletedBreaks);

                if ($isValid){
                    //if valid, increment time to next 30min slot
                    $time=$newTime;
                    $sessionTime=$newSessionTime;
                } else {
                    //if invalid, schedule a break
                    [$time,$sessionTime,$numCompletedBreaks,$eod,$miniError]
                    =addBreak($time,$numCompletedBreaks);
                    $error.= $miniError;
                }
            }
        }
    }

    $alert="";
    if ($eod){
        //display end of day message
        $alert.= "The day has ended.<br> ";
    }
    if (count($examineeIDs) != 0){
        // display unscheduled examinee names
        $names=[];
        for ($i=0 ; $i<count($examineeIDs) ; $i++) {
            // get examinee name
            $getNames = "SELECT FirstName, LastName 
            FROM Students WHERE StudentID='$examineeIDs[$i]'";
            $nameResult = $conn->query($getNames);
            $nameRow=$nameResult->fetch_assoc();
            $fname=$nameRow['FirstName'];
            $lname=$nameRow['LastName'];
            //add name to array
            $names[]="$fname $lname";
        }
        //add all names to alert message
        $alert.="The following examinees have not been scheduled: "
        .implode(" , ",$names)."<br>";
    } else {
        // examineeIDs is empty, so everyone has been scheduled
        $alert.="All examinees have been scheduled!";
    }

    return [$alert,$error];
}

// schedule exam on button click
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // retrieve start date + prevent SQL injection
    $startDate = mysqli_real_escape_string($conn,$_POST['basicinfo']);
    
    $validationError="";
    $today = new DateTime();
    // presence check
    if ($startDate==""){
        $validationError="Please enter a date";
    } else {
        // reject if date is in the past
        if ($startDate < $today->format('Y-m-d')){
            $validationError="The date must be in the future.";
        } else {
            // check if examinees is empty
            $examineeSQL="SELECT * FROM examinees";
            $examineeResult=$conn->query($examineeSQL);
            if ($examineeResult->num_rows == 0 ){
                $validationError="There is no examinee data in the database.";
            } else {
                //enter data into BasicInfo
                $enterBasicinfo="INSERT INTO basicinfo (StartDate) VALUES('$startDate')";
                $conn->query($enterBasicinfo);
                // create schedule
                [$alert,$error]=schedule($conn);
                // redirect to schedule-complete.php with messages
                $_SESSION['alert']=$alert;
                $_SESSION['error']=$error;
                header("Location:schedule-complete.php"); 
                exit();
            }
        }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href='styles.css' rel='stylesheet'>
    <link rel="icon" href="logo/favicon3.png">
    <title>Schedule Exams-SoundBro</title>
</head>

<body class="bg-light">

<!-- link to navigation bar -->
<?php include 'includes/navbar.php' ?>

<div class="container my-5">
    <div class="row p-5 justify-content-center">
        <div class="col-md-8 bg-white p-5 rounded-4 shadow">
            <h2 class="text-center mb-5 text-primary ">Schedule Music Exams</h2>
            <form method="post" action="" class="text-center">

                <!-- display if error is found -->
                <?php if (isset($validationError) and $validationError!="") { ?>
                    <div class="alert alert-danger">
                        <?php echo $validationError; ?>
                    </div>
                <?php } ?>
       
                <!-- enter start date -->
                <div class="form-group p-3">
                    <label for="basicinfo"> Enter the start date</label>
                    <input type="date" class="form-control" id="basicinfo" name="basicinfo" >
                </div>

                <!-- message for entering data -->
                <div class='fw-semibold fst-italic mt-4 p-3'>
                    Please enter examinee and accompanist data directly into the database!
                </div>

                <!-- schedule button -->
                <div class="mt-5 form-group text-center">
                    <button type="submit" name="schedule" class="fs-4 w-75 btn btn-primary border border-4 rounded-pill">
                        <i class="bi bi-magic"></i> Schedule</button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- display footer -->
<?php include 'includes/footer.html'?>
</body>
</html>