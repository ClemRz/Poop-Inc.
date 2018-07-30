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

void disableWifi(void) {
  WiFi.mode(WIFI_OFF);
  WiFi.forceSleepBegin();
  delay(1);
  WiFi.persistent(false);
}

bool initWiFiSta(void) {
  int attempts = 0;
#if DEBUG
  Serial.println(F("Start WiFi"));
#endif
  WiFi.mode(WIFI_STA);
  if (!_cfg.dhcp) {
    IPAddress
      ip(_cfg.ip[0], _cfg.ip[1], _cfg.ip[2], _cfg.ip[3]),
      gateway(_cfg.gateway[0], _cfg.gateway[1], _cfg.gateway[2], _cfg.gateway[3]),
      subnet(_cfg.subnet[0], _cfg.subnet[1], _cfg.subnet[2], _cfg.subnet[3]);
    WiFi.config(ip, gateway, subnet);
  }
  WiFi.begin(_cfg.ssid, _cfg.pwd);
  while (WiFi.status() != WL_CONNECTED && attempts <= MAX_WIFI_ATTEMPTS) {
    yield();
#if DEBUG
    Serial.print(F("."));
#endif
    attempts ++;
    delay(WIFI_REINTENT_DELAY);
  }
#if DEBUG
  if (attempts > MAX_WIFI_ATTEMPTS) {
    Serial.print(F("\nFailed to connect to "));
    Serial.println(_cfg.ssid);
  } else {
    Serial.print(F("\nConnected to "));
    Serial.println(_cfg.ssid);
    Serial.print(F("IP address: "));
    Serial.println(WiFi.localIP());
    Serial.print(F("Mac addresss: "));
    Serial.println(WiFi.macAddress());
  }
#endif
  return attempts <= MAX_WIFI_ATTEMPTS;
}
