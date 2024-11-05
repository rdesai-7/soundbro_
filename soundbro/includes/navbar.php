<!-- navigation bar -->
<nav class="navbar navbar-expand-sm bg-primary navbar-dark">
    <!-- display links for navbar -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <!-- display SoundBro -->
            <a class="nav-link logo fw-bolder fs-4 navbar-brand ms-2"" href="index.php">
                SoundBro.
            </a>
        </li>
        <li class="nav-item">
            <!-- display link to dashboard -->
            <a class="nav-link me-2" href="dashboard.php">
                <i class="bi bi-music-note-list"></i> Dashboard
            </a>
        </li>
        <?php
        // Check if the user is logged in
        if (isset($_SESSION['user_id'])) {
            // logged in, so display 'logout'
            echo '<li class="nav-item"><a href="logout.php" class="nav-link me-2"><i class="bi bi-box-arrow-right"></i> Logout</a></li>';
        } else {
            // logged out, so display 'login' and 'register'
            echo '<li class="nav-item"><a class="nav-link me-2" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li><li class="nav-item"><a class="nav-link me-2" href="register.php"><i class="bi bi-pen"></i> Register</a></li>';
        }
        ?>
    </ul>
</nav>

