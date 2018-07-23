<!DOCTYPE html>
<html>
<head>
<meta http-equiv="refresh" content="60">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Anton|Raleway">
</head>
<body>
<style>
h1 {
	Font-Family: 'Anton', Sans-Serif;
	margin: 10px 0px 5px 0px;
}

p, ol {
	Font-Family: 'Raleway', Sans-Serif;
}

#weathertable {
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
    border: 1px solid #404040;
}

#weathertable td, #weathertable th {
    /*border: 1px solid #ddd;*/
    padding: 8px;
}

#weathertable tr:nth-child(odd){background-color: #f2f2f2;}

#weathertable tr:hover {background-color: #ffc300;}

#weathertable th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #8e8e8e;
    color: white;
}
</style>

<?php
    //Connect to database and create table
    $servername = "localhost";
    $username = "-------------"; // Database username
    $password = "-------------"; // Database password
    $dbname = "iot_home"; // Database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Database Connection failed: " . $conn->connect_error);
        echo "<a href='install.php'>Install MySQL database.</a>";
    }
?> 

/********************
  * 
  * Willy's notes
  * 
********************/

<h1>Willy's Weather Server</h1>
<p>Both sensors are sensitive to direct sunlight. Moving the sensors behind an object caused the temperature to drop, especially with the DHT22.</p>
<p>Things to change:</p>
<ol>
<li>Pressure value is not the same as what's listed at <a href="https://w1.weather.gov/data/obhistory/KCHO.html" target="blank">KCHO's website</a>. I think that it has to do with the BMP280 library and the altitude- look at an old tutorial and make changes.</li>
<li>Humidity from the DHT looks alright when compared to <a href="https://www.wunderground.com/weather/us/va/charlottesville" target="blank">Weather Underground</a>.</li>
<li>Dew point measurement is way off when compared to <a href="https://www.wunderground.com/weather/us/va/charlottesville" target="blank">Weather Underground</a>.</li>
<li>Heat index is way off. Look at nearby stations at the bottom of the <a href="https://www.wunderground.com/weather/us/va/charlottesville" target="blank">Weather Underground</a> page.</li>
<li>Think about doing a two-panel description with a Google Map.</li>
<li>Look at how to download jdbc to connect to Google Sheets. Something like <a href="https://www.dev2qa.com/how-to-use-jdbc-to-connect-mysql-database/" target="blank">this tutorial</a> but for linux.</li>
</ol>

<?php 
    $sql = "SELECT * FROM weather ORDER BY id DESC LIMIT 15";
    if ($result=mysqli_query($conn,$sql))
    {
      echo "<TABLE id='weathertable'>"; // Table Name
      echo "<TR><TH>Date</TH><TH>Time</TH><TH>DHT Temp</TH><TH>BMP Temp</TH><TH>Humidity</TH><TH>Heat Index</TH><TH>Dew Point</TH><TH>Pressure</TH></TR>";
      while ($row=mysqli_fetch_row($result))
      {
        echo "<TR>";
        //echo "<TD>".$row[0]."</TD>";
        echo "<TD>".$row[1]."</TD>";
        echo "<TD>".$row[2]."</TD>";
        echo "<TD>".$row[3]."</TD>";
        echo "<TD>".$row[4]."</TD>";
        echo "<TD>".$row[5]."</TD>";
        echo "<TD>".$row[6]."</TD>";
        echo "<TD>".$row[7]."</TD>";
        echo "<TD>".$row[8]."</TD>";
        echo "</TR>";
      }
      echo "</TABLE>";
      // Free result set
      mysqli_free_result($result);
    }

    mysqli_close($conn);
?>
</body>
</html>
