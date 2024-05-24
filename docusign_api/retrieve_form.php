<?php
    session_start();
    $signer_first_name = $_SESSION['signer_first_name'];
    $signer_last_name = $_SESSION['signer_last_name'];
    $count = $_SESSION['count'];
    for ($i=0; $i<$count; $i++) {
        $env_names[$i] = $_SESSION['env_names'][$i];
    }
?>

<!DOCTYPE html>
<html>
    <body>
        Select an envelope for <?php echo $signer_first_name . " " . $signer_last_name?>:
        <form action="retrieve.php" method="post" enctype="multipart/form-data"> <!-- use multipart/form-data when sending files -->
            <select name="envelope_name" id="envelope_name">
            </select><br>
            <input type="submit" value="Retrieve" name="retrieve">
        </form>
    </body>
    <script type='text/javascript'>
        var js_env_names = <?php echo json_encode($env_names)?>; // json_encode converts php array to JSON string to be used in JS array
        var count=<?php echo $count?>;
        for (i=0; i<count; i++) {
            var x = document.getElementById("envelope_name");
            var option = document.createElement("option");
            option.text = js_env_names[i];
            option.value = js_env_names[i];
            x.add(option, x[i]);
        }
    </script>
</html>