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

void httpSendNotification(void) {
#if DEBUG
    Serial.println(F("Sending notification"));
#endif
  HTTPClient http;
  http.begin(String(_cfg.url) + F("?status=") + _cfg.doorStatus + F("&batteries=") + _avgVcc + F("&mac=") + getUrlEncodedMacAddress());
  int httpCode = http.GET();
  if (httpCode > 0) {
#if DEBUG
    Serial.print(F("HTTP code: ")); Serial.println(httpCode);
#endif
    if (httpCode == HTTP_CODE_OK) {
      String payload = http.getString();
#if DEBUG
      Serial.println(F("HTTP Response: "));
      Serial.println(payload);
#endif
      StaticJsonBuffer<200> jsonBuffer;
      JsonObject& root = jsonBuffer.parseObject(payload);
      if (root.success()) {
#if DEBUG
        root.prettyPrintTo(Serial);
        Serial.println();
#endif
        _cfg.wakeUpRate = root["wakeUpRate"];
        strcpy(_cfg.url, root["url"]);
#if DEBUG
      } else {
        Serial.println(F("JSON parsing failed"));
#endif
      }
    } 
#if DEBUG
  } else {
    Serial.print(F("HTTP error: ")); Serial.println(http.errorToString(httpCode).c_str());
#endif
  }
  http.end();
}

String getUrlEncodedMacAddress(void) {
    uint8_t mac[6];
    char macStr[18] = {0};
    WiFi.macAddress(mac);
    sprintf(macStr, "%02X%%3A%02X%%3A%02X%%3A%02X%%3A%02X%%3A%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
    return String(macStr);
}

