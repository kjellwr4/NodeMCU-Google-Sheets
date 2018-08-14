#include <ESP8266WiFi.h> // Add http://arduino.esp8266.com/stable/package_esp8266com_index.json to Additional Boards Manager URLs in Preferences of Arduino IDE.
#include <WiFiClient.h> 
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <Wire.h>
#include <SPI.h>
#include <Adafruit_Sensor.h> // Install "Adafruit Unified Sensor" in Manage Libraries.
#include <DHT.h> // Install "DHT Sensor Library" in Manage Libraries.

#define DHTPIN 2 // Connect the signal pin of the DHT22 to GPIO2 on the ESP-01.
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

const char *ssid = "----------";  // Wifi network name.
const char *password = "----------"; // Wifi network password.

void setup() {
  
  delay(1000);
  Serial.begin(115200);
  WiFi.mode(WIFI_OFF); // Prevents timeout reconnection issue.
  delay(1000);
  WiFi.mode(WIFI_STA); // Hide NodeMCU as WiFi hotspot.
  
  WiFi.begin(ssid, password); // Connect to the WiFi router.
  Serial.println("");
 
  Serial.print("Connecting");
  // Wait for connection
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
 
  // If connection successful show IP address in serial monitor.
  Serial.println("");
  Serial.print("Connected to ");
  Serial.println(ssid);
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());  // IP address assigned to the NodeMCU.

  dht.begin();

}

void loop() {

  float h = dht.readHumidity(); // DHT22 relative humidity as a percent.
  float f = dht.readTemperature(true); // DHT22 Fahrenheit reading.
  
  String postData = "temp=" + String(f) // The postData string contains all of the data that is sent to log.php.
  + "&humidity=" + String(h);

  // The following serial.print commands display the relevant data in the serial monitor. Make sure that the baud rate is set to 115200.
  Serial.println(f);
  Serial.println(h);
  Serial.print("postData String: ");
  Serial.println(postData);
  
  // The following commands send the data from the NodeMCU to log.php.
  HTTPClient http; 
  http.begin("http://192.168.--.--/google_inside.php"); // Change to include IP Address.
  http.addHeader("Content-Type", "application/x-www-form-urlencoded"); 
  int httpCode = http.POST(postData);   // Send the request.
  String payload = http.getString();    // Get the response payload.
  Serial.println(httpCode);   // Print HTTP return code. Greater than 0 means success.
  Serial.println(payload);    // Print request response payload.
  http.end();

  delay(300000); // Readings every 5 minutes.
}
