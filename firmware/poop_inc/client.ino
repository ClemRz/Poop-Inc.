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

String httpsSendNotification(void) {
#if DEBUG
    Serial.println(F("Sending notification"));
#endif
  HTTPSRedirect* client = new HTTPSRedirect(_cfg.port);
  bool connected = false;
  int attemps = 0;
  while (!connected && attemps < MAX_HTTPS_ATTEMPTS) {
    yield();
    if (client->connect(_cfg.host, _cfg.port) == 1) {
       connected = true;
       break;
#if DEBUG
    } else {
      Serial.println(F("Connection failed. Retrying..."));
#endif
    }
    attemps ++;
    delay(HTTPS_REINTENT_DELAY*MILLISEC);
  }

  if (!connected) {
#if DEBUG
    Serial.print(F("Could not connect to server"));
#endif
    return "";
  }
  
  String url = String(_cfg.url) + F("&status=") + _cfg.doorStatus + F("&batteries=") + _avgVcc + F("&mac=") + getUrlEncodedMacAddress();
#if DEBUG
    Serial.print(F("URL: ")); Serial.println(url);
#endif
  if (client->GET(url, _cfg.host)) {
    String payload = client->getResponseBody();
#if DEBUG
    Serial.println(F("HTTP Response: "));
    Serial.println(payload);
#endif
    return payload;
  } else {
#if DEBUG
    Serial.print(F("Couldn't get the body, code: ")); Serial.print(client->getStatusCode()); Serial.print(F(", message: ")); Serial.println(client->getReasonPhrase());
#endif
    return "";
  }
  
  delete client;
  client = NULL;
}

String getUrlEncodedMacAddress(void) {
    uint8_t mac[6];
    char macStr[18] = {0};
    WiFi.macAddress(mac);
    sprintf(macStr, "%02X%%3A%02X%%3A%02X%%3A%02X%%3A%02X%%3A%02X", mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
    return String(macStr);
}

