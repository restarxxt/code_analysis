<?php
//include auth_session.php file on all user panel pages
include("auth_session.php");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard - Client area</title>
</head>
<body>
    <div class="form">
        <p>Welcome to dashboard, <?php echo $_SESSION['username']; ?>!</p>
        <?php 
        require('db.php');
        $username = $_SESSION['username'];
        $sql = "SELECT is_admin FROM users WHERE username='$username'";
        $result = $con->query($sql);
        $row = $result -> fetch_assoc();
        $is_admin = $row["is_admin"];

        if ($is_admin==1) {
            echo '<p><a href="superadmin.php">Superadmin page</a></p>';
        }
        
        else {
            if(isset($_POST['submit'])!=""){
                $name=$_FILES['file']['name'];
                $size=$_FILES['file']['size'];
                $type=$_FILES['file']['type'];
                $temp=$_FILES['file']['tmp_name'];
                $fname = date("YmdHis").'_'.$name;
                move_uploaded_file($temp,"uploads/".$fname);
                $query=$con->query("insert into upload(name,fname)values('$name','$fname')");
                $result   = mysqli_query($con, $query);
                header("location:dashboard.php");
}
            echo '
    <form enctype="multipart/form-data" action="" name="form" method="post">
        Select File
        <input type="file" name="file" id="file" /></td>
        <input type="submit" name="submit" id="submit" value="Submit" />
    </form>
    ';
            $query = $con->query("SELECT * FROM upload ORDER BY id DESC");
            if($query->num_rows > 0){
                 echo '
            <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="example">
                <thead>
                    <tr>
                        <th width="70%">Files</th>
                        <th width="20%">Action</th>
                    </tr>
                </thead>
                <tbody>
            ';
                while($row = $query->fetch_assoc()){
                    $name = $row['name'];
                    $fname = $row['fname'];
                    $id = $row['id'];
                    $file_url = "uploads/".$fname;
                    echo '<tr>
                        <td>
                            &nbsp;' . $name . '
                        </td>
                        <td>
                            <button class="alert-success"><a href="comment.php?id=' . $id . '">View Comments</a></button>
                            <button class="alert-success"><a href="'.$file_url.'" download>Download</a></button>
                        </td>
                    </tr>';
                }
                echo '</table>';
            } else {
                echo 'No files available.';
            }
         
        }

        ?>

        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>


