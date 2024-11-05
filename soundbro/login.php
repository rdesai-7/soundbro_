<?php
// Start the session to store user data
session_start();

// If logged in, redirect to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); 
    exit();
}

// connect to database
include 'includes/db-connect.php';


// if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve inputs + prevent sql injection
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);

    // validation - presence check
    if (!$email or !$password){
        $error="You must enter a username and password";
    } else {
        
        // Validate email
        $valid= filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$valid) {
            $error = "Invalid email format";
        } else {

            // Fetch login info for both students and supervisors
            $studentQuery = "SELECT StudentID,Password FROM Students WHERE Email = '$email'";
            $supervisorQuery = "SELECT SupervisorID,Password FROM Supervisors WHERE Email = '$email'";
            $studentResult = $conn->query($studentQuery);
            $supervisorResult = $conn->query($supervisorQuery);

            if (!$studentResult or !$supervisorResult) {
                $error = "Could not retrieve database info. Try again later.";
            } else {

                // Check if email exists in the students table
                if ($studentResult->num_rows > 0) {
                    
                    // student email found
                    $row = $studentResult->fetch_assoc();
                    $passwordDB=$row['Password'];

                    // Verify the password
                    if (password_verify($password,$passwordDB)) {

                        // Password is correct, set session variables
                        $id=$row['StudentID'];
                        $_SESSION['user_type']='student';
                        $_SESSION['user_id'] = $id;

                        // Redirect to dashboard
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        // student password is incorrect
                        $error = "Incorrect password";
                    }

                } else {

                    // check if email exists in the supervisors table
                    if ($supervisorResult->num_rows > 0) {

                        // supervisor email found
                        $row = $supervisorResult->fetch_assoc();
                        $passwordDB=$row['Password'];

                        // Verify the password
                        if (password_verify($password,$passwordDB)) {
                            // Password is correct, set session variables
                            $id=$row['SupervisorID'];
                            $_SESSION['user_id'] = $id;

                            if ($email=='admin@email.com') {
                                // admin login (Ms Pearcey)
                                $_SESSION['user_type']='admin';
                            } else {
                                $_SESSION['user_type']='supervisor';
                            }

                            // Redirect to dashboard
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            //supervisor password is incorrect
                            $error = "Incorrect password";
                        }
                    } else {
                        $error = "Email not found";
                    }
                }
            }
        }
    }
}

// Close the database connection
$conn->close();
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
    <title>Login-SoundBro</title>
</head>

<body class="bg-light">

<!-- link to navigation bar -->
<?php include 'includes/navbar.php' ?>

<!-- login container -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 bg-white p-5 rounded-4 shadow">
            <h2 class="text-center mb-4 text-primary">Login</h2>

            <!-- display if user has just registered -->
            <?php if (isset($_GET['registered']) and $_GET['registered']==1) { ?>
                <div class="alert alert-success">
                    Registration successful!
                </div>
            <?php } ?>
            
            <!-- display if error is found -->
            <?php if (isset($error)) { ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <form method="post" action="">
                <!-- enter email -->
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" >
                </div>

                <!-- enter password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>

                <!-- submit -->
                <div class="mt-2 form-group">
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </div>
            </form>

            <!-- link to registration page to prevent confusion -->
            <p class="mt-3 text-center">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
</div>

<!-- display footer -->
<?php include 'includes/footer.html'?>
</body>
</html>