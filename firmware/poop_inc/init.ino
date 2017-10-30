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

void initSerial(void) {
  Serial.begin(9600);
  Serial.println();
  //Serial.setDebugOutput(true);
}

void initIO(void) {
  pinMode(REED, INPUT_PULLUP);
}

void initFS(void) {
  SPIFFS.begin();
  //SPIFFS.remove(CONFIG_FILE_PATH);
}

void initWiFi(void) {
  int attempts = 0;
#if DEBUG
  Serial.println(F("Start WiFi"));
#endif
  WiFi.persistent(true);
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.begin(SSID, PASSWORD);
    while (WiFi.status() != WL_CONNECTED && attempts <= MAX_WIFI_ATTEMPTS) {
      yield();
#if DEBUG
      Serial.print(F("."));
#endif
      attempts ++;
      delay(WIFI_REINTENT_DELAY);
    }
  }
#if DEBUG
  Serial.println();
  if (attempts > MAX_WIFI_ATTEMPTS) {
    Serial.print(F("Failed to connect to "));
    Serial.println(SSID);
  } else {
    Serial.print(F("Connected to "));
    Serial.println(SSID);
    Serial.print(F("IP address: "));
    Serial.println(WiFi.localIP());
    Serial.print(F("Mac addresss: "));
    Serial.println(WiFi.macAddress());
  }
#endif
  if (attempts > MAX_WIFI_ATTEMPTS) sleep();
}

