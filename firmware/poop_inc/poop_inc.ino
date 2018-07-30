/*
    Copyright (C) 2017 Clément Ronzon

    This file is part of Poop Inc.

    Poop Inc. is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Poop Inc. is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Poop Inc.  If not, see <http://www.gnu.org/licenses/>.
 */

#include <ESP8266WiFi.h>          // https://github.com/esp8266/Arduino/
#include <WiFiClient.h>           // https://github.com/esp8266/Arduino/
#include <ESP8266WebServer.h>     // https://github.com/esp8266/Arduino/
#include <ArduinoJson.h>          // https://github.com/bblanchon/ArduinoJson
#include "FS.h"                   // https://github.com/esp8266/Arduino/
#include "structures.h"           // https://github.com/ClemRz/Introduction-to-IoT#use-structures
#include "HTTPSRedirect.h"        // https://github.com/electronicsguy/ESP8266/tree/master/HTTPSRedirect

ADC_MODE(ADC_VCC);

#define MICROSEC              1000000L
#define MILLISEC              1000L
#define SEC                   1L
#define MINUTE                (unsigned int) 60L*SEC
#define HOUR                  (unsigned int) 60L*MINUTE
#define DAY                   (unsigned long) 24L*HOUR

#define DOT                   "."
#define EMPTY_STR             ""

/* 
 * ======================================
 *      User defined constants
 * ======================================
*/
#define DEFAULT_WAKE_UP_RATE  10*SEC
#define AP_SLEEP_DELAY        30*SEC
#define DEBUG                 1
// ======================================

// Pins allocation
#define REED                  12

// HTTPS parameters
#define MAX_WIFI_ATTEMPTS     60
#define WIFI_REINTENT_DELAY   500 //ms
#define MAX_HTTPS_ATTEMPTS    5
#define HTTPS_REINTENT_DELAY  2*SEC

// Door constants
#define VACANT                0
#define ENGAGED               1

// Sampling
#define ANALOG_READ_SAMPLES   8.0             // Number of samples to compute analog reading average

// File system configs
#define CONFIG_FILE_PATH      "cfg.json"

// Global constants
const size_t _BUFFER_SIZE =   3*JSON_ARRAY_SIZE(4) + JSON_OBJECT_SIZE(10) + 690;

// Global variables
const char *_AP_SSID =        "PoopInc";
HTTPSRedirect* _client =      NULL;
ESP8266WebServer _server(80);
float _avgVcc =               0.00;
Config _cfg;
unsigned long _timer;
bool _dontSleep =             false;

void setup() {
  disableWifi();
  getAvgVcc();
#if DEBUG
  initSerial();
#endif
  initFS();
  fsReadConfig();
  initIO();
  bool doorStatus = getDoorStatus();
  bool shouldSleep = true;
  if (doorStatus != _cfg.doorStatus) {
#if DEBUG
  Serial.print(F("Door status changed, was ")); Serial.print(_cfg.doorStatus); Serial.print(F(" and became ")); Serial.println(doorStatus);
#endif  //DEBUG
    _cfg.doorStatus = doorStatus;
    if (initWiFiSta()) {
      String payload = httpsSendNotification();
      if (payload != "") {
        storePayload(payload);
        fsWriteConfig();
      }
    } else {
#if DEBUG
      Serial.println(F("Couldn't connect to Wifi. Starting AP for settings modification."));
#endif  //DEBUG
      shouldSleep = false;
      initWifiAP();
    }
#if DEBUG
  } else {
    Serial.println(F("Door status didn't change"));
#endif  //DEBUG
  }
  if (shouldSleep) sleep();
  _timer = millis();
}

void loop() {
  if (_dontSleep || millis() - _timer < AP_SLEEP_DELAY * MILLISEC) {
    _server.handleClient();
  } else sleep(true);
}
