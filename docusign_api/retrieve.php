<?php
    session_start();
    
    ########## Load Modules and Classes ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    require_once("autoload.php"); #BXP: Loads all DocuSign requirements
    require_once("jwt/autoload.php"); #BXP: Loads all JWT requirements
    use \Firebase\JWT\JWT; #BXP: Aliasing?

    ########## Retrieve Envelope ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    ##### General Variables & Constants #####
    $base_url = "https://demo.docusign.net/restapi";  #BXP: REST API URL - Will be different in the live verison
    $account_id = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys and look for "API Account ID"
    $user_id = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys and look for "API Username"
    $integrator_key = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys
    $app_path = getcwd(); #BXP: Retrieves current directory - Replace with a hard-coded path to the app directory if needed
    $private_key_path = join("\\", array($app_path, "private_key", "docusign_private_key.txt")); #BXP: Go to DocuSign/Admin/API_and_Keys/Integrator_Key and look for RSA_Keypairs/Private_Key
    $signer_first_name = $_SESSION["signer_first_name"];
    $signer_last_name = $_SESSION["signer_last_name"];

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
        
    ##### Get Envelope ID from Database #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    $envelope_name = $_POST['envelope_name'];
    $server_name = "localhost";
    $user_name = "root";
    $password = "***";
    $db_name = "docusign";
    $connection = new mysqli($server_name, $user_name, $password, $db_name);
    $sql = "SELECT env_id FROM signers WHERE first_name = '$signer_first_name' AND last_name = '$signer_last_name' AND env_name = '$envelope_name'";
    $result = mysqli_query($connection, $sql);
    $row = $result->fetch_assoc();
    $envelope_id = $row['env_id'];

    ##### Get Envelope Info from DocuSign #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    $envelope_api = new DocuSign\eSign\Api\EnvelopesApi($api_client);
    $envelope_info = $envelope_api->getEnvelope($account_id, $envelope_id);
    $recipient_info = $envelope_api->listRecipients($account_id, $envelope_id);
    $document_id = "combined"; #BXP: Use archive to download as a zip file (change file extention to *.zip - Use combined to combine signed document and certificate - Or use the document number (1,2,3...)
    $document_info = $envelope_api->getDocument($account_id, $document_id, $envelope_id);
    //$document_list = $envelope_api->listDocuments($account_id, $envelope_id);
    //var_dump($envelope_info); #BXP: Use to see all envelope info
    //var_dump($recipient_info); #BXP: Use to see all recipient info
    //var_dump($document_list); #BXP: Use to see the list of documents
    //var_dump($document_info); #BXP: Use to see all document info   
    $certificate_path = join("\\", array($app_path, "certificates", $envelope_id . ".pdf")); #BXP: Replace "\\" separator with "/" for live version
    file_put_contents($certificate_path, file_get_contents($document_info->getPathname()));
    $_SESSION['signer_email'] = $recipient_info['signers'][0]['email']; #BXP: $recipient_info is an array containing multiple arrays (there is a separate array per signer withing $recipient_info) - Each signer array
                                                                        #contains detaild info about that signer - e.g [0] is the first signer's array (for multiple signers this must be a variable like [x])
    $_SESSION['envelope_name'] = $envelope_name;
    $_SESSION['email_subject'] = $envelope_info['email_subject'];
    $_SESSION['email_message'] = $envelope_info['email_blurb'];
    $_SESSION['status'] = $envelope_info['status'];
    $_SESSION['envelope_id'] = $envelope_id;
    header("location:update_form.php"); #BXP: Run the next php file
?>