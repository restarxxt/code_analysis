<!DOCTYPE html>
<html>
  <head>
    <title>Sign Up</title>
  </head>
  <body>
<?php
    require('db.php');
    // When form submitted, insert values into the database.
    if (isset($_REQUEST['username'])) {
        // removes backslashes
        $username = stripslashes($_REQUEST['username']);
        //escapes special characters in a string
        $username = mysqli_real_escape_string($con, $username);
        $email    = stripslashes($_REQUEST['email']);
        $email    = mysqli_real_escape_string($con, $email);
        $password = stripslashes($_REQUEST['password']);
        $password = mysqli_real_escape_string($con, $password);
        #$is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] == 'Admin' ? 1 : 0;
        $is_admin = stripslashes($_REQUEST['is_admin']);
        if ($is_admin == '1') {
            $is_admin = mysqli_real_escape_string($con, 1);
        }
        else {$is_admin = mysqli_real_escape_string($con, 2);}
        $password = mysqli_real_escape_string($con, $password);
        $query    = "INSERT into `users` (username, password, email, is_admin)
                     VALUES ('$username', '" . md5($password) . "', '$email', '$is_admin')";
        $result   = mysqli_query($con, $query);
        if ($result) {
            echo "<div class='form'>
                  <h3>You are registered successfully.</h3><br/>
                  <p class='link'>Click here to <a href='login.php'>Login</a></p>
                  </div>";
        } else {
            echo "<div class='form'>
                  <h3>Required fields are missing.</h3><br/>
                  <p class='link'>Click here to <a href='registration.php'>registration</a> again.</p>
                  </div>";
        }
    } else {
?>
    <form class="form" action="" method="post">
        <h1 class="login-title">Registration</h1>
        <input type="text" class="login-input" name="username" placeholder="Username" required />
        <input type="text" class="login-input" name="email" placeholder="Email Adress">
        <input type="password" class="login-input" name="password" placeholder="Password">
        <label for="is_admin">Role:</label>
        <select name="is_admin" id="is_admin">
            <option value="0">Regular User</option>
            <option value="1">Admin</option>
        </select><br><br>
        <input type="submit" name="submit" value="Register" class="login-button">
        <p class="link"><a href="login.php">Click to Login</a></p>
    </form>
<?php
    }
?>
</body>
</html>