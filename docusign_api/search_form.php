<!DOCTYPE html>
<html>
    <body>
        Search for envelope:
        <form action="search.php" method="post" enctype="multipart/form-data"> <!-- use multipart/form-data when sending files -->
            Signer's First Name: <input type="text" name="signer_first_name"><br>
            Signer's Last Name: <input type="text" name="signer_last_name"><br>
            Signer's Initials: <input type="text" name="signer_initials"><br>
            <input type="submit" value="Search" name="search">
        </form>
    </body>
</html>