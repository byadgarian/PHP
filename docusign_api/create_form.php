<!DOCTYPE html>
<html>
    <body>
        Create envelope:
        <form action="create.php" method="post" enctype="multipart/form-data"> <!-- use multipart/form-data when sending files -->
            Signer's First Name: <input type="text" name="signer_first_name"><br>
            Signer's Last Name: <input type="text" name="signer_last_name"><br>
            Signer's Initials: <input type="text" name="signer_initials"><br>
            Signer's E-mail: <input type="text" name="signer_email"><br>
            Envelope Name: <input type="text" name="envelope_name"><br>
            Email Subject: <input type="text" name="email_subject"><br>
            Email Message: <textarea type="text" name="email_message" rows="5" cols="50"></textarea><br>
            Status:
            <select name="status" id="status">
                <option value="sent">Send</option>
                <option value="created">Save as Draft</option>
            </select><br>
            Document: <input type="file" name="file_to_upload" id="file_to_upload"> <!-- Don't use break at the end - Name is used to identify file in create.php not id -->
            <input type="submit" value="Save" name="save">
        </form>
    </body>
</html>