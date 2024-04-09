// include the file that contains my creds
#include <secrets.h>
// include general arduino input output
#include <Arduino.h>
// include wifi for basically wifi
#include <WiFi.h>
// include the fingerprint
#include <Adafruit_Fingerprint.h>

// include the webserver
#include <WebServer.h>
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
bool readfingers=false;

void setup()
{
    // set buzzer as output
    pinMode(BUZZER, OUTPUT);

    // alert that boot has started
    beepShort(2);
    delay(500);
    beepLong(1);
    delay(1000);

    // add some debug
    Serial.begin(115200);
    Serial.println("[*] Finger Print Application Is starting");
    Serial.println("[*] Connecting to fingerprint UART");
    finger.begin(57600);
    if (finger.verifyPassword())
    {
        Serial.println("[+] Found fingerprint sensor!");
        beepShort(1);
        delay(1000);
    }
    else
    {
        Serial.println("[-] Did not find fingerprint sensor :(");
        while (1)
            ;
    }

    // start connecting to wifi after the UART has finished
    if (!WiFi.config(ipaddr, gateway, subnet))
    {
        Serial.println("STA Failed to configure");
    }
    Serial.print("[*] Connecting to ");
    Serial.println(WIFI_SSID);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    while (WiFi.status() != WL_CONNECTED)
    {
        delay(500);
        Serial.print(".");
        beepLong(1);
    }
    beepShort(1);
    delay(500);
    Serial.println("");
    Serial.println("WiFi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    Serial.print("ESP Mac Address: ");
    Serial.println(WiFi.macAddress());
    Serial.print("Subnet Mask: ");
    Serial.println(WiFi.subnetMask());
    Serial.print("Gateway IP: ");
    Serial.println(WiFi.gatewayIP());

    // configure and start the webserver
    server.on(F("/scan"), []()
              { 
                server.send(200, "text/plain", "hello from esp32!");
                readfingers = true;
               });
    // configure on stop
    server.on(F("/stop"), []()
              { 
                server.send(200, "text/plain", "hello from esp32!"); 
                readfingers = false;
                });
    server.begin();
}
void loop()
{
    // delay(1000);
    // getFreeTemplate();
    delay(50);
    server.handleClient();
    while (readfingers){
        getFingerprintID();
        server.handleClient();
    }

    // fingerPrintEnroll(100);
    // delay(10000);
    // deleteFingerprint(100);
}

// void get the number of fingerprint counts
int getFreeTemplate()
{
    // get fingerprint info
    finger.getParameters();
    // get number of registered fingerprints;
    finger.getTemplateCount();
    Serial.print("Capacity: ");
    Serial.println(finger.capacity);
    Serial.println(finger.templateCount);
    return 0;
}
// Some beeps for audio feedback
void beepShort(int count)
{
    for (int i = 0; i < count; i++)
    {
        digitalWrite(BUZZER, HIGH);
        delay(80);
        digitalWrite(BUZZER, LOW);
        delay(80);
    }
}

void beepLong(int count)
{
    for (int i = 0; i < count; i++)
    {
        digitalWrite(BUZZER, HIGH);
        delay(300);
        digitalWrite(BUZZER, LOW);
        delay(300);
    }
}

// Enroll a fingerprint
bool fingerPrintEnroll(int id)
{
    int p = -1;
    if (id == 0)
    {
        return false;
    }
    else
    {
        Serial.print("[+] Enrolling finger as #");
        Serial.println(id);
        while (p != FINGERPRINT_OK)
        {
            p = finger.getImage();
            switch (p)
            {
            case FINGERPRINT_OK:
                Serial.println("[+] Image taken");
                break;
            case FINGERPRINT_NOFINGER:
                Serial.print(".");
                break;
            case FINGERPRINT_PACKETRECIEVEERR:
                Serial.println("[+] Communication error");
                break;
            case FINGERPRINT_IMAGEFAIL:
                Serial.println("[+] Imaging error");
                break;
            default:
                Serial.println("[+] Unknown error");
                break;
            }
        }

        beepShort(1);
        // OK success!

        // convert image to template one
        p = finger.image2Tz(1);
        switch (p)
        {
        case FINGERPRINT_OK:
            Serial.println("[-] Image converted");
            break;
        case FINGERPRINT_IMAGEMESS:
            Serial.println("[-] Image too messy");
            return p;
        case FINGERPRINT_PACKETRECIEVEERR:
            Serial.println("[-] Communication error");
            return p;
        case FINGERPRINT_FEATUREFAIL:
            Serial.println("[-] Could not find fingerprint features");
            return p;
        case FINGERPRINT_INVALIDIMAGE:
            Serial.println("[-] Could not find fingerprint features");
            return p;
        default:
            Serial.println("[-] Unknown error");
            return p;
        }

        Serial.println("[+] Remove finger");
        delay(2000);
        p = 0;
        while (p != FINGERPRINT_NOFINGER)
        {
            p = finger.getImage();
        }
        Serial.print("ID ");
        Serial.println(id);
        p = -1;
        Serial.println("[+] Place same finger again");
        while (p != FINGERPRINT_OK)
        {
            p = finger.getImage();
            switch (p)
            {
            case FINGERPRINT_OK:
                Serial.println("[+] Image taken");
                break;
            case FINGERPRINT_NOFINGER:
                Serial.print(".");
                break;
            case FINGERPRINT_PACKETRECIEVEERR:
                Serial.println("[-] Communication error");
                break;
            case FINGERPRINT_IMAGEFAIL:
                Serial.println("[-] Imaging error");
                break;
            default:
                Serial.println("[-] Unknown error");
                break;
            }
        }

        // OK success!

        p = finger.image2Tz(2);
        switch (p)
        {
        case FINGERPRINT_OK:
            Serial.println("[+] Image converted");
            break;
        case FINGERPRINT_IMAGEMESS:
            Serial.println("[-] Image too messy");
            return p;
        case FINGERPRINT_PACKETRECIEVEERR:
            Serial.println("[-] Communication error");
            return p;
        case FINGERPRINT_FEATUREFAIL:
            Serial.println("[-] Could not find fingerprint features");
            return p;
        case FINGERPRINT_INVALIDIMAGE:
            Serial.println("[-] Could not find fingerprint features");
            return p;
        default:
            Serial.println("[-] Unknown error");
            return p;
        }

        // OK converted!
        Serial.print("[+] Creating model for #");
        Serial.println(id);

        p = finger.createModel();
        if (p == FINGERPRINT_OK)
        {
            Serial.println("[+] Prints matched!");
        }
        else if (p == FINGERPRINT_PACKETRECIEVEERR)
        {
            Serial.println("[-] Communication error");
            return p;
        }
        else if (p == FINGERPRINT_ENROLLMISMATCH)
        {
            Serial.println("[-] Fingerprints did not match");
            return p;
        }
        else
        {
            Serial.println("[-] Unknown error");
            return p;
        }

        Serial.print("ID ");
        Serial.println(id);
        p = finger.storeModel(id);
        if (p == FINGERPRINT_OK)
        {
            Serial.println("[+] Stored!");
        }
        else if (p == FINGERPRINT_PACKETRECIEVEERR)
        {
            Serial.println("Communication error");
            return p;
        }
        else if (p == FINGERPRINT_BADLOCATION)
        {
            Serial.println("Could not store in that location");
            return p;
        }
        else if (p == FINGERPRINT_FLASHERR)
        {
            Serial.println("Error writing to flash");
            return p;
        }
        else
        {
            Serial.println("Unknown error");
            return p;
        }

        beepShort(2);
        return true;
    }
}

// delete a fingerprint
bool deleteFingerprint(int id)
{
    uint8_t p = -1;
    bool deleted = false;

    p = finger.deleteModel(id);

    if (p == FINGERPRINT_OK)
    {
        Serial.println("[+] Deleted!");
        deleted = true;
    }
    return deleted;
}

// update a fingerprint
bool updateFingerprint(int id)
{
    bool success = false;
    if (fingerPrintEnroll(id))
    {
        success = true;
    }
    else
    {
        success = false;
    }
    return success;
}

// returns -1 if failed, otherwise returns ID #
int getFingerprintID()
{
    uint8_t p = finger.getImage();
    if (p != FINGERPRINT_OK)
        return -1;

    p = finger.image2Tz();
    if (p != FINGERPRINT_OK)
        return -1;

    p = finger.fingerSearch();
    if (p != FINGERPRINT_OK)
        return -1;

    // found a match!
    beepShort(2);
    Serial.print("[+] Found ID #");
    Serial.print(finger.fingerID);
    Serial.print(" with confidence of ");
    Serial.println(finger.confidence);
    return finger.fingerID;
}