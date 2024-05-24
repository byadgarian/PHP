<?php
    session_start();
    $signer_first_name = $_SESSION['signer_first_name'];
    $signer_last_name = $_SESSION['signer_last_name'];
    $signer_initials = $_SESSION['signer_initials'];
    $signer_email = $_SESSION['signer_email'];
    $envelope_name = $_SESSION['envelope_name'];
    $email_subject = $_SESSION['email_subject'];
    $email_message = $_SESSION['email_message'];
    $status = $_SESSION['status'];
?>

<!DOCTYPE html>
<html>
    <body>
        Update Envelope:
        <form action="update.php" method="post" enctype="multipart/form-data"> <!-- use multipart/form-data when sending files -->
            Signer's First Name: <input type="text" name="signer_first_name" value="<?php echo $signer_first_name?>"><br>
            Signer's Last Name: <input type="text" name="signer_last_name" value="<?php echo $signer_last_name?>"><br>
            Signer's Initials: <input type="text" name="signer_initials" value="<?php echo $signer_initials?>"><br>
            Signer's E-mail: <input type="text" name="signer_email" value="<?php echo $signer_email?>"><br>
            Envelope Name: <input type="text" name="envelope_name" value="<?php echo $envelope_name?>"><br>
            Email Subject: <input type="text" name="email_subject" value="<?php echo $email_subject?>"><br>
            Email Message: <textarea type="text" name="email_message" rows="5" cols="50"><?php echo $email_message?></textarea><br>
            Document: <input type="file" name="file_to_upload" id="file_to_upload"> <!-- Don't use break at the end - Name is used to identify file in php not ID - Find a way to show the initial document -->
            Current status: <?php echo $status?><br>
            Status:
            <select name="status" id="status">
                <option value="sent">Sent</option>
                <option value="created">Draft</option>
                <option value="voided">Void</option>
            </select><br>
            Reason for voiding (if void is selected): <input type="text" name="voided_reason"><br>
            <input type="submit" value="Update" name="update">
        </form>
    </body>
    <script>
        var status="<?php echo $status?>";
        if (status=="sent" || status=="delivered" || status=="completed") {
            document.getElementById("status").selectedIndex="0";
        } else if (status=="created") {
            document.getElementById("status").selectedIndex="1";
        } else {
            document.getElementById("status").selectedIndex="2";
        }
    </script>
</html>