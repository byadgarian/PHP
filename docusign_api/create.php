<?php
    ########## Upload File ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    $target_dir = "ipc_documents/";
    $target_file = $target_dir . basename($_FILES["file_to_upload"]["name"]); #BXP: basename = file name without extension, file_to_upload = variable name, name = actual name of the file
    move_uploaded_file($_FILES["file_to_upload"]["tmp_name"], $target_file); #BXP: temp_name = temporary file name on server

    ########## Load Modules and Classes ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    require_once("autoload.php"); #BXP: Loads all DocuSign requirements
    require_once("jwt/autoload.php"); #BXP: Loads all JWT requirements
    use \Firebase\JWT\JWT; #BXP: Aliasing?

    ########## Create Envelope ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    ##### General Variables & Constants #####
    $base_url = "https://demo.docusign.net/restapi";  #BXP: REST API URL - Will be different in live verison
    $account_id = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys and look for "API Account ID"
    $user_id = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys and look for "API Username"
    $integrator_key = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys
    $app_path = getcwd(); #BXP: Retrieves current directory - Replace with a hard-coded path to the app directory if needed
    $private_key_path = join("\\", array($app_path, "private_key", "docusign_private_key.txt")); #BXP: Go to DocuSign/Admin/API_and_Keys/Integrator_Key and look for RSA_Keypairs/Private_Key
                                                                                                 #Replace "\\" separator with "/" for live version
    $document_path = join("\\", array($app_path, "ipc_documents", $_FILES["file_to_upload"]["name"])); #BXP: Replace "\\" separator with "/" for live version
    //$env_id_path = join("\\", array($app_path, "env_id")); #BXP: Store envelope IDs here - Replace "\\" separator with "/" for live version
    $page_number = "1"; #BXP: Document page to be signed - Ask user to select page number?
    $signer_first_name = $_POST["signer_first_name"];
    $signer_last_name = $_POST["signer_last_name"];
    $signer_name = $signer_first_name . " " . $signer_last_name;
    $signer_email = $_POST["signer_email"];
    //$sender_provided_numbers = ["+1 ***"]; #BXP: Array - Provide signer's personal phone number for authentication
    $client_user_id = ""; #BXP: Indicates that the signer will use an Embedded Signing Ceremony (same as signer's IPC user ID) - Leave blank for remote signing

    ##### Prepare Document #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    $content_bytes = file_get_contents($document_path);
    $base64_file_content =  base64_encode ($content_bytes);
    $document = new DocuSign\eSign\Model\Document([  
        'document_base64' => $base64_file_content, 
        'name' => $_FILES["file_to_upload"]["name"], #BXP: Alternatively ask user to enter a name - Eliminate file extension if needed
        'file_extension' => 'pdf',
        'document_id' => '1' #BXP: A label used to reference the document - Use in case of multiple documents
    ]);
        
    ##### Prepare Signer Info #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //$sms_authentication = new DocuSign\eSign\Model\RecipientSMSAuthentication([$sender_provided_numbers]);
    //$phone_authentication = new DocuSign\eSign\Model\RecipientSMSAuthentication([$sender_provided_numbers]); #BXP: Does not work with RecipientPhoneAuthentication - For more attributes refer to recipient_phone_authentication in DocuSign library
    $signer = new DocuSign\eSign\Model\Signer([ 
        'email' => $signer_email, 'name' => $signer_name, 'recipient_id' => "1", 'routing_order' => "1", #BXP: Use recipient_id in case of multiple signers - What is routing_order?
        'client_user_id' => $client_user_id #BXP: Setting the client_user_id indicates that the signer is embedded (this must be commented for non-embedded signing)
        # Signer Authentication Methods #
        //'access_code' => '123456', #BXP: 50 characters max
        //'add_access_code_to_email' => false #BXP: The code is provided by the sender if False and is provided through email by DocuSign if True - For embedded signing only use False (no email is sent)

        //'id_check_configuration_name' => 'ID Check $' #BXP: Uses signer's public records to generate security questions
        
        //'id_check_configuration_name' => 'SMS Auth $',
        //'require_id_lookup' => true,
        //'sms_authentication' => $sms_authentication #BXP: Alternatively use the following JSON code: sms_authentication = {"senderProvidedNumbers": sender_provided_numbers}
        
        //'id_check_configuration_name' => 'Phone Auth $',
        //'require_id_lookup' => true,
        //'phone_authentication' => $phone_authentication #BXP: Alternatively use the following JSON code: phone_authentication = {"senderProvidedNumbers": sender_provided_numbers}
    ]);
    $sign_here = new DocuSign\eSign\Model\SignHere([ 
        //'document_id' => '1', 'page_number' => '1', 'recipient_id' => '1', #BXP: Fixed positioning
        //'tab_label' => 'SignHereTab', 'x_position' => '50', 'y_position' => '50' #BXP: To eliminate the need for complicated scripts to determine the signature position use DocuSign's Sender View method
        'anchor_string' => '*s*', 'anchor_units' => 'pixels', 'anchor_y_offset' => '50', 'anchor_x_offset' => '0' #BXP: Anchor text positioning - Replace *s* with anything
    ]);
    $signer->setTabs(new DocuSign\eSign\Model\Tabs(['sign_here_tabs' => [$sign_here]]));
    $recipients = new DocuSign\eSign\Model\Recipients([
        'signers' => [$signer] #BXP: Array
    ]);
        
    ##### Define Envelope #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    if ($_POST["status"] == "sent") {
        $status = "sent";
    } else {
        $status = "created";
    }
    $envelope_definition = new DocuSign\eSign\Model\EnvelopeDefinition([
        'email_subject' => $_POST["email_subject"],
        'email_blurb' => $_POST["email_message"],
        'documents' => [$document], #BXP: Array - The order in the documents array determines the order in the envelope - Add option for multipe documents
        'recipients' => $recipients, #BXP: The recipients object accepts arrays - Add option for multipe signers
        'status' => $status #BXP: 'sent' means the envelope will be created and 'created' means the envelope will be saved as draft - For Sender View Method use 'created'
    ]);

    ##### Authenticate User #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    #BXP: If encountered authentication problems, use the access token generator first and give required premissions

    # Variables & Constants #
    # Standard JWT claim items and their use by DocuSign
    # More Info: https://tools.ietf.org/html/rfc7519 & https://jwt.io
    # "aud" (Audience) Claim  . . . . . . BXP: Authentication server URL - Use account.docusign.com for live version
    # "sub" (Subject) Claim . . . . . . . BXP: Same as $user_id
    # "iss" (Issuer) Claim  . . . . . . . BXP: Same as $integrator_key         
    # "exp" (Expiration Time) Claim . . . BXP: Authentication expiration time - Max 3600 secs after $current_time
    # "nbf" (Not Before) Claim  . . . . . Current date/time
    # "iat" (Issued At) Claim . . . . . . BXP: Not used
    # "jti" (JWT ID) Claim  . . . . . . . BXP: Not used
    # "scope" . . . . . . . . . . . . . . A list of space delimited scopes requested in this token (e.g. signature)

    $aud = "account-d.docusign.com"; #BXP: Do not include "https://"
    $sub = "***";
    $iss = "***";
    $exp = 3600;
    $scope = "signature"; #BXP: Do not change
    $current_time = time();
    $token = array(
        "iss" => $iss,
        "sub" => $sub,
        "aud" => $aud,
        "scope" => $scope,
        "nbf" => $current_time,
        "exp" => $current_time + $exp
    );
    //$alg = "RS256"; #BXP: Do not change
    $private_key = file_get_contents($private_key_path);
    $grant_type = "urn:ietf:params:oauth:grant-type:jwt-bearer"; #BXP: Do not change
    $jwt = JWT::encode($token, $private_key, 'RS256'); #BXP: Use $alg?
    $token_url = "https://{$aud}/oauth/token"; #BXP: Obtains token from authentication server
    $permission_scopes = "signature%20impersonation"; #BXP: Do not change - "%20" is a separator - Is this needed?
    $redirect_uri = "https://docusign.com"; #BXP: Redirect user here after they authorize the app for the first time
        
    # Send Authentication Request & Retreive Access Token #
    $data = array('grant_type' => $grant_type, 'assertion' => $jwt);
    $body = Unirest\Request\Body::form($data);
    $headers = array('Accept' => 'application/json');
    $response = Unirest\Request::post($token_url, $headers, $body);
    $json = $response->body;
    if (property_exists ($json, 'error') and $json->{'error'} == 'consent_required' ){ #BXP: Grant permission to API app - Applys only when setting up the API app for a new DocuSign user account for the first time
        $consent_url = "https://{$aud}/oauth/auth?response_type=code&scope={$permission_scopes}&client_id={$iss}&redirect_uri={$redirect_uri}";
        echo $consent_url;
    }
    $access_token = $json->{'access_token'};
    $config = new DocuSign\eSign\Configuration();
    $config->setHost($base_url);
    // $config->addDefaultHeader("X-DocuSign-Authentication", "{\"Username\":\"" . "***" . "\",\"Password\":\"" . "***" . "\",\"IntegratorKey\":\"" . $integrator_key . "\"}"); #BXP: Legacy Header Authentication - Use variables instead of hard-coded login info
    // $access_token = 'Access Token'; #BXP: Go to https://developers.docusign.com/oauth-token-generator to get a temporary access token (for testing purposes only)
    $config->addDefaultHeader("Authorization", "Bearer " . $access_token); #BXP: JWT Authentication - Uses JWT request method to generate access token
    $api_client = new DocuSign\eSign\ApiClient($config);
    
    ##### Save Envelope #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    $envelope_api = new DocuSign\eSign\Api\EnvelopesApi($api_client);
    $results = $envelope_api->createEnvelope($account_id, $envelope_definition);
    $envelope_id = $results['envelope_id'];

    ########## Save Envelope ID in Database ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    $server_name = "localhost";
    $user_name = "root";
    $password = "***";
    $db_name = "docusign";
    $connection = new mysqli($server_name, $user_name, $password, $db_name);
    $signer_initials = $_POST["signer_initials"];
    $envelope_name = $_POST["envelope_name"];
    $sql = "INSERT INTO signers (first_name, last_name, initials, env_id, env_name) VALUES ('$signer_first_name', '$signer_last_name', '$signer_initials', '$envelope_id', '$envelope_name')";
    mysqli_query($connection, $sql); #BXP: Alternativly use $connection->query($sql);

    ########## Request Sender View URL ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //$return_url_request = new DocuSign\eSign\Model\ReturnUrlRequest();
    //$return_url_request->setReturnUrl('***'); #BXP: Return sender to this URL when the envelope is sent
    //$sender_view_url = $envelope_api->createSenderView($account_id, $envelope_id, $return_url_request);
    //$url=$sender_view_url['url'];
    //echo $url;

    ########## Next Steps ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    echo "Done!";
?>