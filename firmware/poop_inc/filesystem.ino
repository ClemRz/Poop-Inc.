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

void initFS(void) {
  SPIFFS.begin();
  //SPIFFS.remove(CONFIG_FILE_PATH);
}

 void fsReadConfig(void) {
#if DEBUG
  Serial.println(F("Read config from SPIFFS"));
#endif
  bool flag = false;
  File file = SPIFFS.open(CONFIG_FILE_PATH, "r");
  if (file) {
    String content = file.readStringUntil('\n');
    Parser::JsonParser<JSON_TOKENS> parser;
    Parser::JsonObject root = parser.parse((char*)content.c_str());
    if (root.success()) {
#if DEBUG
      Serial.println(content);
#endif
      strcpy(_cfg.ssid, root["ssid"]);
      strcpy(_cfg.pwd, root["pwd"]);
      _cfg.wakeUpRate = root["rate"];
Serial.print(F("_cfg.wakeUpRate="));Serial.println(_cfg.wakeUpRate);
      _cfg.doorStatus = root["status"];
      _cfg.port = root["port"];
      strcpy(_cfg.url, root["url"]);
      strcpy(_cfg.host, root["host"]);
      for (uint8_t i = 0; i < 4; i++) {
        _cfg.ip[i] = root["ip"][i];
        _cfg.gateway[i] = root["gateway"][i];
        _cfg.subnet[i] = root["subnet"][i];
      }
      _cfg.dhcp = false;
      flag = true;
#if DEBUG
    } else {
      Serial.println(F("JSON parsing failed"));
#endif
    }
#if DEBUG
  } else {
    Serial.println(F("file open failed"));
#endif
  }
  file.close();
  if (!flag) {
    _cfg.wakeUpRate = DEFAULT_WAKE_UP_RATE;
    _cfg.doorStatus = VACANT;
    _cfg.dhcp = true;
  }
}

void storePayload(String payload) {
  String tmp = payload;
  Parser::JsonParser<JSON_TOKENS> parser;
  Parser::JsonObject root = parser.parse((char*)tmp.c_str());
  if (root.success()) {
#if DEBUG
    Serial.println(payload);
#endif
    _cfg.wakeUpRate = root["wakeUpRate"];
    _cfg.port = root["port"];
    strcpy(_cfg.url, root["url"]);
    strcpy(_cfg.host, root["host"]);
#if DEBUG
  } else {
    Serial.println(F("JSON parsing failed"));
#endif
  }
}

void fsWriteConfig(void) {
#if DEBUG
  Serial.println(F("Write config to SPIFFS"));
#endif
  Generator::JsonArray<4> ip;
  Generator::JsonArray<4> gateway;
  Generator::JsonArray<4> subnet;
  for (uint8_t i = 0; i < 4; i++) {
    ip.add(_cfg.ip[i]);
    gateway.add<0>(_cfg.gateway[i]);
    subnet.add<0>(_cfg.subnet[i]);
  }
  Generator::JsonObject<10> root;
  root["ssid"] = _cfg.ssid;
  root["pwd"] = _cfg.pwd;
  root["rate"] = _cfg.wakeUpRate;
  root["status"] = _cfg.doorStatus;
  root["port"] = _cfg.port;
  root["url"] = _cfg.url;
  root["host"] = _cfg.host;
  root["ip"] = ip;
  root["gateway"] = gateway;
  root["subnet"] = subnet;
#if DEBUG
  Serial.println(root);
#endif
  File file = SPIFFS.open(CONFIG_FILE_PATH, "w");
  if (file) {
    file.print(root);
#if DEBUG
  } else {
    Serial.println(F("file open failed"));
#endif
  }
  file.close();
}

