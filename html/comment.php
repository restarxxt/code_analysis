<?php
//include auth_session.php file on all user panel pages
    include("auth_session.php");
    require('db.php');

    // Check if user is logged in
    if(!isset($_SESSION['username'])){
        header('Location: login.php');
        exit();
    }

    // Get file ID from URL
    $file_id = $_GET['id'];

    // Retrieve file name from database
    $query = $con->query("SELECT name FROM upload WHERE id = '$file_id'");
    $row = $query->fetch_assoc();
    $file_name = $row['name'];

    // Retrieve comments from database
    $comment_query = $con->query("SELECT * FROM comments WHERE file_id = '$file_id' ORDER BY created_at DESC");

    // Display file name and comments
    echo '<h3>File Name: ' . $file_name . '</h3>';
    echo '<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" id="example">
            <thead>
                <tr>
                    <th width="70%">Comments</th>
                    <th width="20%">Date</th>
                    <th width="10%">Action</th>
                </tr>
            </thead>
            <tbody>';

    // Display each comment
    while($comment_row = $comment_query->fetch_assoc()){
        $comment_id = $comment_row['id'];
        $comment = $comment_row['comment'];
        $created_at = $comment_row['created_at'];

        echo '<tr>
                <td>
                    ' . $comment . '
                </td>
                <td>
                    ' . $created_at . '
                </td>
                <td>
                    <button class="alert-danger"><a href="delete_comment.php?id=' . $comment_id . '">Delete</a></button>
                </td>
            </tr>';
    }

    // Display comment form
    echo '<tr>
            <td>
                <form method="post" action="">
                    <input type="hidden" name="file_id" value="' . $file_id . '">
                    <textarea name="comment" rows="5" cols="50"></textarea>
                    <input type="submit" name="submit_comment" value="Add Comment">
                </form>
            </td>
            <td></td>
            <td></td>
        </tr>';

    echo '</tbody>
        </table>
        <p><a href="dashboard.php">Dashboard</a></p>';

    // Process comment form submission
    if(isset($_POST['submit_comment'])){
        $file_id = $_POST['file_id'];
        $comment = $_POST['comment'];

        // Insert new comment into database
        $con->query("INSERT INTO comments(file_id, comment) VALUES ('$file_id', '$comment')");

        // Redirect back to comment section
        header("Location: comment.php?id=$file_id");
        exit();
    }
?>
