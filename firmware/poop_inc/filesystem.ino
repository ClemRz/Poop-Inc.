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

 void fsReadConfig(void) {
#if DEBUG
  Serial.println(F("Read config from SPIFFS"));
#endif
  bool flag = false;
  File file = SPIFFS.open(CONFIG_FILE_PATH, "r");
  if (file) {
    size_t size = file.size();
    if (size <= 1024) {
      std::unique_ptr<char[]> buf(new char[size]);
      file.readBytes(buf.get(), size);
      file.close();
      StaticJsonBuffer<200> jsonBuffer;
      JsonObject& root = jsonBuffer.parseObject(buf.get());
      if (root.success()) {
#if DEBUG
        root.prettyPrintTo(Serial);
        Serial.println();
#endif
        _cfg.wakeUpRate = root["rate"];
        _cfg.doorStatus = root["doorStatus"];
        _cfg.port = root["port"];
        strcpy(_cfg.url, root["url"]);
        strcpy(_cfg.host, root["host"]);
        flag = true;
#if DEBUG
      } else {
        Serial.println(F("JSON parsing failed"));
#endif
      }
#if DEBUG
    } else {
      Serial.println(F("Config file size is too large"));
#endif
    }
#if DEBUG
  } else {
    Serial.println(F("file open failed"));
#endif
  }
  if (!flag) {
    _cfg.wakeUpRate = DEFAULT_WAKE_UP_RATE;
    _cfg.doorStatus = VACANT;
    _cfg.port = DEFAULT_PORT;
    strcpy(_cfg.url, DEFAULT_URL);
    strcpy(_cfg.host, DEFAULT_HOST);
  }
}

void storePayload(String payload) {
  StaticJsonBuffer<200> jsonBuffer;
  JsonObject& root = jsonBuffer.parseObject(payload);
  if (root.success()) {
#if DEBUG
    root.prettyPrintTo(Serial);
    Serial.println();
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
  StaticJsonBuffer<200> jsonBuffer;
  JsonObject& root = jsonBuffer.createObject();
  root["rate"] = _cfg.wakeUpRate;
  root["doorStatus"] = _cfg.doorStatus;
  root["port"] = _cfg.port;
  root["url"] = _cfg.url;
  root["host"] = _cfg.host;
#if DEBUG
  root.prettyPrintTo(Serial);
  Serial.println();
#endif
  File file = SPIFFS.open(CONFIG_FILE_PATH, "w");
  if (file) {
    root.printTo(file);
    file.close();
#if DEBUG
  } else {
    Serial.println(F("file open failed"));
#endif
  }
}

