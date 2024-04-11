// include the file that contains my creds
#include "secrets.h"
// include wifi for basically wifi
#include <WiFi.h>
// include the fingerprint
#include <Adafruit_Fingerprint.h>
// include the webserver
#include <WebServer.h>
//include the client
#include <HTTPClient.h>
// define serial
#define mySerial Serial2

// Variables time
// buzzer pin
#define BUZZER 23

// the fingerprint device
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);
// The wifi network
IPAddress ipaddr(192, 168, 43, 170);
IPAddress gateway(192, 168, 43, 1);
IPAddress subnet(255, 255, 255, 0);
WebServer server(80);

//server configs
bool scanfingers = false;
bool addfinger = false;
HTTPClient http;


void setup() {
  // add some debug
  Serial.begin(115200);

  // set buzzer as output
  pinMode(BUZZER, OUTPUT);

  // alert that boot has started
  beepShort(2);
  delay(500);
  beepLong(1);
  delay(1000);
  debugPrint("Finger Print Application Is starting");
  debugPrint("Connecting to fingerprint UART");
  finger.begin(57600);
  if (finger.verifyPassword()) {
    debugPrint("Found fingerprint sensor!");
    beepShort(1);
    delay(1000);
  } else {
    debugPrint("[-] Did not find fingerprint sensor :(");
    while (1)
      ;
  }

  // start connecting to wifi after the UART has finished
  if (!WiFi.config(ipaddr, gateway, subnet)) {
    debugPrint("STA Failed to configure");
  }
  debugPrint("connecting to wifi:");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    beepLong(1);
  }
  beepShort(1);
  delay(500);
  debugPrint("WiFi connected!");

  // configure and start the webserver
  //handle actions to the scanner
  server.on(F("/scanner"), HTTP_GET, scanHandler);
  // configure actions to register fingerprint
  server.on(F("/register"), HTTP_GET, registerHandler);
  // configure actions to delete fingerprints
  server.on(F("/delete"), HTTP_GET, deleteHandler);
  server.begin();
}
void loop() {
  // delay(1000);
  delay(50);
  server.handleClient();
  while (scanfingers) {
    int response = getFingerprintID();
    if (response != -1) {
      response = signHandler();
      switch (response) {
        case 0:
          //okay signed in class
          beepShort(2);
          break;
        case 1:
          //beep long not allowed in class
          beepLong(2);
          break;
        default:
          //warn of post data
          beepLong(3);
          break;
      }
    }

    server.handleClient();
  }
}

// Some beeps for audio feedback
void beepShort(int count) {
  for (int i = 0; i < count; i++) {
    digitalWrite(BUZZER, HIGH);
    delay(80);
    digitalWrite(BUZZER, LOW);
    delay(80);
  }
}

void beepLong(int count) {
  for (int i = 0; i < count; i++) {
    digitalWrite(BUZZER, HIGH);
    delay(500);
    digitalWrite(BUZZER, LOW);
    delay(500);
  }
}

// Enroll a fingerprint
void fingerPrintEnroll(int id) {
  int p = -1;
  //define boolean success
  bool success = false;
  while (!success && id != 0) {
    debugPrint("Enrolling fingerprint as ID: ", id);
    //iterate two times
    for (int i = 1; i <= 2; i++) {
      int p = -1;
      while (p != FINGERPRINT_OK) {
        p = finger.getImage();
      }
      // convert image to template
      debugPrint("Taking image: ", i);
      p = finger.image2Tz(i);
      if (p == FINGERPRINT_OK && i == 1) {
        delay(1000);
        // OK success beep!
        beepShort(1);
        //wait until the user removes a finger
        while (p != FINGERPRINT_NOFINGER) {
          p = finger.getImage();
        }
      }
    }

    // create a model
    p = finger.createModel();
    debugPrint("Comparing the images");
    if (p == FINGERPRINT_OK) {
      p = finger.storeModel(id);
      if (p == FINGERPRINT_OK) {
        success = true;
        beepShort(2);
        debugPrint("Successfully matched and Stored");
      } else {
        debugPrint("The images did not match");
      }
    }
  }
}

// delete a fingerprint
bool deleteFingerprint(int id) {
  uint8_t p = -1;
  bool deleted = false;

  p = finger.deleteModel(id);

  if (p == FINGERPRINT_OK) {
    debugPrint("Deleted image! ", id);
    deleted = true;
  }
  return deleted;
}

// returns -1 if failed, otherwise returns ID #
int getFingerprintID() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK)
    return -1;
  debugPrint("Image Captured");
  p = finger.image2Tz();
  if (p != FINGERPRINT_OK)
    return -1;

  p = finger.fingerSearch();
  if (p != FINGERPRINT_OK) {
    beepLong(1);
    return -1;
  }

  // found a match!
  debugPrint("Found image with id: ", finger.fingerID);
  return finger.fingerID;
}


void debugPrint(const char* msg) {
  // Custom print for short messages
  if (Serial.availableForWrite() >= sizeof(msg)) {
    // Print data to the serial port
    Serial.print(F("[*] "));
    Serial.println(F(msg));
  }
}

void debugPrint(const char* msg, int num) {
  // Calculate the total size of the data to be printed
  // Size of the prefix "[*] " + size of the string + size of the integer
  size_t totalSize = sizeof("[*] ") + strlen(msg) + sizeof(num) + 1;  // Add 1 for the null terminator

  // Check if there is enough space available in the serial output buffer
  if (Serial.availableForWrite() >= totalSize) {
    // Print data to the serial port
    Serial.print(F("[*] "));
    Serial.print(F(msg));
    Serial.println(num);
  }
}


//handle scan request
void scanHandler() {
  if (server.hasHeader("Authorization")) {
    String authHeader = server.header("Authorization");
    if (authHeader == AUTH_HEADER) {
      if (server.hasArg("action")) {
        String scan = server.arg("action");
        if (scan == "start") {
          scanfingers = true;
          addfinger = false;
          server.send(200, "text/plain", "Started the scanning of fingers");
        } else if (scan == "stop") {
          scanfingers = false;
          server.send(200, "text/plain", "Stopped the scanning of fingers");

        } else {
          server.send(400, "text/plain", "please specify start or stop");
        }
      } else {
        server.send(400, "text/plain", "please specify an ?action");
      }

    } else {
      server.send(401, "text/plain", "unauthorzied");
      return;
    }
  } else {
    server.send(401, "text/plain", "unauthorzied");
    return;
  }
}

//handle register request
void registerHandler() {
  if (server.hasHeader("Authorization")) {
    String authHeader = server.header("Authorization");
    if (authHeader == AUTH_HEADER) {
      scanfingers = false;
      if (server.hasArg("id")) {
        int id = server.arg("id").toInt();
        if (id > 0) {
          fingerPrintEnroll(id);
          server.send(200, "text/plain", "added successfully");

        } else {
          server.send(400, "text/plain", "please specify an id greater than zero");
        }
      } else {
        server.send(200, "text/plain", "please specify an ?id");
      }

    } else {
      server.send(401, "text/plain", "unauthorzied");
      return;
    }
  } else {
    server.send(401, "text/plain", "unauthorzied");
    return;
  }
}

//handle delete request
void deleteHandler() {
  if (server.hasHeader("Authorization")) {
    String authHeader = server.header("Authorization");
    if (authHeader == AUTH_HEADER) {
      scanfingers = false;
      if (server.hasArg("id")) {
        int id = server.arg("id").toInt();
        if (id > 0) {
          deleteFingerprint(id);
          server.send(200, "text/plain", "deleted successfully");

        } else {
          server.send(400, "text/plain", "please specify an id greater than zero");
        }
      } else {
        server.send(200, "text/plain", "please specify an ?id");
      }

    } else {
      server.send(401, "text/plain", "unauthorzied");
      return;
    }
  } else {
    server.send(401, "text/plain", "unauthorzied");
    return;
  }
}

//post the id to the server and check if authorized

int signHandler() {
  //return 0 for a successful finger
  //return 1 for non-allowed finger
  int value = -1;
  // Your custom headers
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", "Bearer your-access-token");

  // Your POST data
  String postData = "{\"id\":";
  postData += finger.fingerID;
  postData += "}";
  Serial.println(postData);
  int httpResponseCode = http.POST(postData);
  switch (httpResponseCode) {
    case 200:
      value = 0;
      debugPrint("successfully signed in");
      break;
    case 403:
      value = 1;
      debugPrint("Not allowed");
      break;
    default:
      debugPrint("Error in post");
      debugPrint("http response code is ", httpResponseCode);
      value = -1;
      break;
  }
  http.end();
  return value;
}