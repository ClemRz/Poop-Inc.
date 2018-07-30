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

void sleep(void) {
  sleep(false);
}

void sleep(bool forEver) {
#if DEBUG
  Serial.print(F("Go to sleep for "));
  if (forEver) {
    Serial.println(F("ever"));
  } else {
    Serial.print(_cfg.wakeUpRate);
    Serial.println(F("s."));
  }
#endif  //DEBUG
  unsigned long sleepTime = forEver ? 0 : (_cfg.wakeUpRate == 0 ? 1 : _cfg.wakeUpRate);
  sleepTime *= MICROSEC;
  WiFi.disconnect(true);
  delay(1);
  ESP.deepSleep(sleepTime, WAKE_RF_DISABLED);
}
