<?php
session_start();

// if logged in redirect to dashboard.php
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); 
    exit();
}

// connect to database
include 'includes/db-connect.php';

// if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve inputs + prevent sql injection
    $firstName=mysqli_real_escape_string($conn,$_POST['firstName']);
    $lastName=mysqli_real_escape_string($conn,$_POST['lastName']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);
    $confirmPassword = mysqli_real_escape_string($conn,$_POST['confirmPassword']);

    // --- VALIDATION ---
    // presence check

    if (!$firstName or !$lastName or !$email or !$password or !$confirmPassword) {
        $error="You must enter data for all fields";

    } else {

        // check first name validity - Name must be 2-50 chars
        $validLength_fname = strlen($firstName) >= 2 && strlen($firstName) <= 50;
        // no special chars (only lowercase, uppercase, hyphens, and spaces)
        $characterSetValid_fname = preg_match("/^[a-zA-Z\- ]+$/", $firstName);
        // No numbers 
        $noNumbers_fname = !preg_match("/[0-9]/", $firstName);

        if ($validLength_fname and $characterSetValid_fname and $noNumbers_fname) {

            // check last name validity - Name must be 2-50 chars
            $validLength_lname = strlen($lastName) >= 2 && strlen($lastName) <= 50;
            // no special chars (only lowercase, uppercase, hyphens, and spaces)
            $characterSetValid_lname = preg_match("/^[a-zA-Z\- ]+$/", $lastName);
            // No numbers 
            $noNumbers_lname = !preg_match("/[0-9]/", $lastName);

            if ($validLength_lname and $characterSetValid_lname and $noNumbers_lname){
                //check email validity
                $email=filter_var($email,FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $error="Invalid email";
                } else {

                    //check if user already has a login
                    $checkStudents="SELECT * FROM Students WHERE Email = '$email'";
                    $checkSupervisors="SELECT * FROM Supervisors WHERE Email = '$email'";
                    $studentResult=$conn->query($checkStudents);
                    $supervisorResult=$conn->query($checkSupervisors);
                    
                    if (!$studentResult or !$supervisorResult) {
                        //handle unexpected error
                        $error = "Could not retrieve database info. Try again later.";
                    } else {

                        if ($studentResult->num_rows>0) {
                            // email found in Students table
                            $error = "Email already registered as a student. Please log in";
                        } else {

                            if($supervisorResult->num_rows>0) {
                                // email found in Supervisors table
                                $error = "Email already registered as a music teacher / accompanist. Please log in";
                            } else {

                                // check passwords match
                                if ($password !== $confirmPassword) {
                                    $error='Passwords do not match';
                                } else {
                                    
                                    //check password constraints
                                    $uppercase = preg_match('@[A-Z]@', $password);
                                    $lowercase = preg_match('@[a-z]@', $password);
                                    $number = preg_match('@[0-9]@', $password);
                                    if (!$uppercase or !$lowercase or !$number or strlen($password) < 8) {
                                        $error="The password must be at least 8 characters long and include at least one 
                                        uppercase letter, one lowercase letter, and one number.";
                                    } else {

                                        // hash password for security
                                        $hashedPassword=password_hash($password,PASSWORD_DEFAULT);
                                        // insert into database
                                        $sql="INSERT INTO Supervisors (FirstName,LastName,Email,Password) 
                                        VALUES ('$firstName','$lastName','$email','$hashedPassword')";
                                        if ($conn->query($sql) === TRUE){
                                            // redirect to login page with registration message
                                            header("Location: login.php?registered=1");
                                            exit();
                                        } else {
                                            $error= "Error with database: Try again later";
                                        }
                                    }
                                }
                            }
                        }
                    }
                } 
            } else {
                //invalid last name
                $error="Both names must be 2-50 characters, with no numbers or special characters (except hyphens)";
            }
        } else {
            //invalid first name
            $error="Both names must be 2-50 characters, with no numbers or special characters (except hyphens)";
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
            <h2 class="text-center mb-4 text-primary">Register a Music Teacher / Accompanist</h2>

            <!-- display error if found -->
            <?php if (isset($error)) { ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <form method="post" action="">
                <!-- enter first name -->
                <div class="form-group">
                    <label for="firstName">First name</label>
                    <input type="text" class="form-control" id="firstName" name="firstName">
                    <small class="text-muted">
                        Names must be 2-50 characters, with no numbers or special characters (except hyphens).
                    </small>
                </div>

                <!-- enter last name -->
                <div class="form-group">
                    <label for="lastName">Last name</label>
                    <input type="text" class="form-control" id="lastName" name="lastName">
                </div>

                <!-- enter email-->
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email">
                    <small class="text-muted">
                        We'll only send you an email when the schedule is created.
                    </small>
                </div>
                
                <!-- enter password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <small class="text-muted">
                        Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter and one number.
                    </small>
                </div>

                <!-- confirm password -->
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                </div>

                <!-- submit -->
                <div class="mt-2 form-group">
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </div>
            </form>

            <!-- link back to login page -->
            <p class="h6 mt-3 text-center">
                If you have an account, login <a href="login.php">here</a>
            </p>
        </div>
    </div>
</div>

<!-- display footer -->
<?php include 'includes/footer.html'?>
</body>

</html>