<?php
########## Handle Errors ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
#BXP: Comment out error handlers on the dev website for debugging

set_error_handler('error_handler'); #BXP: Catch non-fatal errors
set_exception_handler('exception_handler'); #BXP: Catch exceptions
register_shutdown_function('fatal_error_handler'); #BXP: Catch fatal errors

function error_handler(){
    header('location: /time_keeping_test.php?notification=mepr');
    // die();
}

function exception_handler(){
    header('location: /time_keeping_test.php?notification=mepr');
    // die();
}

function fatal_error_handler(){
    $error = error_get_last();
    if ($error['type'] == E_ERROR){        
        header('location: /time_keeping_test.php?notification=mepr');
        // die();
    }
}

########## Include Classes ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'] . "/docusign/autoload.php"); #BXP: Includes all DocuSign requirements
require_once($_SERVER['DOCUMENT_ROOT'] . "/docusign/jwt/autoload.php"); #BXP: Includes all JWT requirements
use \Firebase\JWT\JWT; #BXP: Aliasing?

########## Retrieve Envelope ##########--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
class RetrieveEnvelope{

    function __construct(){} #BXP: Construct object

    function __destruct(){} #BXP: Destruct object

    public function public_retrieve_envelope($document_name, $employee_name){ #BXP: Public access to private method
        $this->retrieve_envelope($document_name, $employee_name);
    }

    private function retrieve_envelope($document_name, $employee_name){ #BXP: Private access (class members not accessible outside the scope)
        ##### General Variables & Constants #####
        $base_url = "https://demo.docusign.net/restapi";  #BXP: REST API URL - Will be different in the live verison
        $account_id = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys and look for "API Account ID"
        $user_id = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys and look for "API Username"
        $integrator_key = "***"; #BXP: Go to DocuSign/Admin/API_and_Keys
        // $app_path = getcwd(); #BXP: Retrieves current directory - Replace with a hard-coded path to the app directory if needed
        $private_key_path = join("/", array($_SERVER['DOCUMENT_ROOT'], "docusign", "private_key", "docusign_private_key.txt")); #BXP: Go to DocuSign/Admin/API_and_Keys/Integrator_Key and look for RSA_Keypairs/Private_Key
        $signer_name = $employee_name;

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
        // $alg = "RS256"; #BXP: Do not change
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
        // $envelope_name = $_POST['envelope_name'];
        // $server_name = "localhost";
        // $user_name = "root";
        // $password = "***";
        // $db_name = "docusign";
        // $connection = new mysqli($server_name, $user_name, $password, $db_name);
        // $sql = "SELECT env_id FROM signers WHERE first_name = '$signer_first_name' AND last_name = '$signer_last_name' AND env_name = '$envelope_name'";
        // $result = mysqli_query($connection, $sql);
        // $row = $result->fetch_assoc();
        // $envelope_id = $row['env_id'];
        $envelope_id = "***"; //BXP: temporary hard-coded envelope id

        ##### Get Envelope Info from DocuSign #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        $envelope_api = new DocuSign\eSign\Api\EnvelopesApi($api_client);
        $envelope_info = $envelope_api->getEnvelope($account_id, $envelope_id);
        // $recipient_info = $envelope_api->listRecipients($account_id, $envelope_id);
        $document_id = "combined"; #BXP: Use "archive" to download as a zip file (change file extention to *.zip - Use combined to combine signed document and certificate - Or use the document number (1,2,3...)
        // $document_list = $envelope_api->listDocuments($account_id, $envelope_id);
        $document_info = $envelope_api->getDocument($account_id, $document_id, $envelope_id);
        // var_dump($envelope_info); #BXP: Use to see all envelope info
        // var_dump($recipient_info); #BXP: Use to see all recipient info
        // var_dump($document_list); #BXP: Use to see the list of documents
        // var_dump($document_info); #BXP: Use to see all document info   
        // $certificate_path = join("/", array($app_path, "certificates", $envelope_id . ".pdf")); #BXP: to save the file on disc
        // file_put_contents($certificate_path, file_get_contents($document_info->getPathname())); #BXP: to save the file on disc
        
        ##### Show Signed Document #####--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        if ($envelope_info['status'] == "completed")
        {
            $base64_file_content =  file_get_contents($document_info->getPathname());
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . $document_name . '"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            echo($base64_file_content);
        }
        else
        {
            echo("This envelope has not been signed yet."); #BXP: use something like header('location: /ds_time_keeping_test_bxp.php?notification=mtsss'); to show the error message
            goto test;
        }

        test:
    }
}
?>