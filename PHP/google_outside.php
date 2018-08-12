<?php
require __DIR__ . '/vendor/autoload.php'; // Necessary for Google Sheets API.
date_default_timezone_set('America/New_York'); // Timezone can be changed, see http://php.net/manual/en/timezones.php.
$dateNow = date("m/d/Y"); // Date format can be changed, see http://php.net/manual/en/function.date.php.
$timeNow = date("g:i A"); // Time format can be changed, see http://php.net/manual/en/function.date.php.
$temp = number_format($_POST['temp'], 2, '.', '');
$humidity = number_format($_POST['humidity'], 2, '.', '');
$heat_index = number_format($_POST['heat_index'], 2, '.', '');
$dew_point = number_format($_POST['dew_point'], 2, '.', '');
$pressure = number_format($_POST['pressure'], 2, '.', '');
/**********
 * GOOGLE FUNCTION: getClient
 * 
 * This function checks the credentials and creates access tokens that
 * are necessary for appending data to the Google Sheet. Install
 * all dependencies in the public_html directory except for client_secret.json.
 * 
 * For usage limits, see: https://developers.google.com/sheets/api/limits 
 * 
**********/
function getClient() {
    $client = new Google_Client();
    $client->setApplicationName('Append Values'); // Name should match the OAuth 2.0 client ID name.
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    $client->setAuthConfig('/home/pi/Downloads/client_secret.json'); // File should be downloaded to the Downloads directory by default.
    $client->setAccessType('offline');
    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory('credentials.json');
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } 
    else {
        // Request authorization from the user. Do this through terminal first.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));
        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);
    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}
/*************
 * GOOGLE FUNCTION: expandHomeDirectory
 * 
 * Do not change or take out the function below. It is used to find the
 * appropriate credentials necessary for making the append request.
 * 
*************/
function expandHomeDirectory($path) {
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}
/*************
 * GOOGLE APPEND
 * 
 * The code below calls the functions to insert new data into the 
 * specified sheet. Change the $spreadsheetID by looking at the URL
 * in a published sheet.
 * 
 * Comments contain notes and changes.
 * 
*************/
if ($temp != 0 || $humidity != 0 || $heat_index != 0 || $dew_point != 0 || $pressure != 0) {
	$client = getClient();
	$service = new Google_Service_Sheets($client);
	$requestBody = new Google_Service_Sheets_ValueRange();
	$spreadsheetId = '----------'; // Replace the characters between quotes with your spreadsheet ID. See URL.
	$requestBody->setValues(["values" => [$dateNow, $timeNow, $temp, $humidity, $heat_index, $dew_point, $pressure]]); // Add the variables in list form. Order matters in terms of columns on the spreadsheet!
	$range = 'google_outside!A1'; // Do not change this value regardless of the number of values set in the $requestBody. It should always be A1.
	$conf = ["valueInputOption" => "USER_ENTERED"]; // Or "RAW." See https://developers.google.com/sheets/api/reference/rest/v4/ValueInputOption
	$response = $service->spreadsheets_values->append($spreadsheetId, $range, $requestBody, $conf);
}
?>
