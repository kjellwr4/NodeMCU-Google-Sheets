<?php
    
/*************
 * BEGIN MYSQL-NODEMCU PHP CODE
*************/

$servername = "localhost";
$username = "----------"; // Username for phpMyAdmin. See "Database Access: New User" section in tutorial (Step 5).
$password = "----------"; // Password for phpMyadmin. See "Database Access: New User" section in tutorial (Step 5).
$dbname = "----------"; // This is likely iot_home. See "Database and Table Setup" section in tutorial (Step 5). 
$conn = new mysqli($servername, $username, $password, $dbname); // MySQL database connection.
if ($conn->connect_error) 
	{
		die("Database Connection failed: " . $conn->connect_error);
	}

date_default_timezone_set('America/New_York'); // Timezone can be changed, see http://php.net/manual/en/timezones.php.
$dateNow = date("m/d/Y"); // Date format can be changed, see http://php.net/manual/en/function.date.php.
$timeNow = date("g:i A"); // Time format can be changed, see http://php.net/manual/en/function.date.php.

/*************
 * MYSQL: Check NodeMCU Readings
 * 
 * The code below checks to make sure that there are values from all
 * of the sensor readings that were passed from the NodeMCU to the
 * log.php web page. It then formats the values, stores them in variables,
 * and passes the information to the MySQL database.
 * 
*************/

if(!empty($_POST['temp_dht']) && !empty($_POST['temp_bmp']) && !empty($_POST['humidity']) && !empty($_POST['heat_index']) && !empty($_POST['dew_point']) && !empty($_POST['pressure']))
	{
		// The code below stores the readings as string values in PHP variables.
		$temp_dht = mysqli_real_escape_string($conn, $_POST['temp_dht']);
    	$temp_bmp = mysqli_real_escape_string($conn, $_POST['temp_bmp']);
    	$humidity = mysqli_real_escape_string($conn, $_POST['humidity']);
    	$heat_index = mysqli_real_escape_string($conn, $_POST['heat_index']);
    	$dew_point = mysqli_real_escape_string($conn, $_POST['dew_point']);
    	$pressure = mysqli_real_escape_string($conn, $_POST['pressure']);
    	
    	// The code below formats strings to numbers via http://php.net/manual/en/function.number-format.php
    	$temp_dht_format = number_format($temp_dht, 2, '.', '');
    	$temp_bmp_format = number_format($temp_bmp, 2, '.', '');
    	$humidity_format = number_format($humidity, 2, '.', '');
    	$heat_index_format = number_format($heat_index, 2, '.', '');
    	$dew_point_format = number_format($dew_point, 2, '.', '');
    	$pressure_format = number_format($pressure, 2, '.', '');

		// The code below inserts the variable values into the column names in the MySQL database. The table name is "weather."
		$sql = "INSERT INTO weather (date, time, temp_dht, temp_bmp, humidity, heat_index, dew_point, pressure)
		VALUES ('$dateNow', '$timeNow', '$temp_dht_format', '$temp_bmp_format', '$humidity_format', '$heat_index_format', '$dew_point_format', '$pressure_format')";

		// Code for debgugging the NodeMCU-log.php connection. Results will appear in the serial monitor.
		if ($conn->query($sql) === TRUE) 
		{
			echo "OK";
		} 
		else 
		{
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}

$conn->close();

/*************
 * END MYSQL-NODEMCU PHP CODE
 * 
 * BEGIN GOOGLE SHEETS CODE
*************/

require __DIR__ . '/vendor/autoload.php'; // Necessary for Google Sheets API.

/*************
 * GOOGLE FUNCTION: getClient
 * 
 * This function checks the credentials and creates access tokens that
 * are necessary for appending data to the Google Sheet. Install
 * all dependencies in the public_html directory.
 * 
 * For usage limits, see: https://developers.google.com/sheets/api/limits 
 * 
 * Comments contain notes and changes.
 * 
*************/

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Append Values'); // Name should match the OAuth 2.0 client ID name.
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    $client->setAuthConfig('client_secret.json'); // File should be downloaded and placed in the public_html directory.
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory('credentials.json');
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
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

function expandHomeDirectory($path)
{
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
 * specified sheet. Change the $spreadsheetID by looking at the URL in a published sheet.
 * 
 * Comments contain notes and changes.
 * 
*************/

$client = getClient();
$service = new Google_Service_Sheets($client);
$requestBody = new Google_Service_Sheets_ValueRange();
$spreadsheetId = '--------------------------------'; // Replace the characters between quotes with your spreadsheet ID. See URL.
$requestBody->setValues(["values" => [$dateNow, $timeNow, $temp_dht_format, $temp_bmp_format, $humidity_format, $heat_index_format, $dew_point_format, $pressure_format]]); // Add the variables in list form. Order matters in terms of columns on the spreadsheet!
$range = 'A1'; // Do not change this value regardless of the number of values set in the $requestBody. It should always be A1.
$conf = ["valueInputOption" => "USER_ENTERED"]; // See https://developers.google.com/sheets/api/reference/rest/v4/ValueInputOption
$response = $service->spreadsheets_values->append($spreadsheetId, $range, $requestBody, $conf);

?>
