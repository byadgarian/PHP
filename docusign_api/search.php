<?php
    $signer_first_name = $_POST['signer_first_name'];
    $signer_last_name = $_POST['signer_last_name'];
    $signer_initials = $_POST['signer_initials'];
    $server_name = "localhost";
    $user_name = "root";
    $password = "***";
    $db_name = "docusign";
    $connection = new mysqli($server_name, $user_name, $password, $db_name);    
    $sql = "SELECT env_name FROM signers WHERE first_name = '$signer_first_name' AND last_name = '$signer_last_name' AND initials = '$signer_initials'";
    $results = mysqli_query($connection, $sql); #BXP: Alternativly use $results = $connection->query($sql);
    $count="0";
    while($rows = $results->fetch_assoc()) {
        $env_names[] = $rows["env_name"];
        $count++;
    }
    session_start();
    for($i=0; $i<$count; $i++) {
    $_SESSION['env_names'][$i] = $env_names[$i];
    }
    $_SESSION['count'] = $count;
    $_SESSION['signer_first_name'] = $signer_first_name; #BXP: retrieve this info at the end (before sending to update form) if session issues occur
    $_SESSION['signer_last_name'] = $signer_last_name; #BXP: retrieve this info at the end (before sending to update form) if session issues occur
    $_SESSION['signer_initials'] = $signer_initials; #BXP: retrieve this info at the end (before sending to update form) if session issues occur
    header("location:retrieve_form.php"); #BXP: Run the next php file
?>