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
 
void initWifiAP(void) {
  WiFi.disconnect(true);
  WiFi.persistent(false);
  WiFi.softAP(_AP_SSID);
  _server.on("/", HTTP_GET, handleGetRoot);
  _server.on("/", HTTP_POST, handlePostRoot);
  _server.begin();
}

void handleGetRoot(void) {
  sendWebPage();
}

void handlePostRoot(void) {
  _dontSleep = true;
  _server.arg("ssid").toCharArray(_cfg.ssid, 100);
  _server.arg("pwd").toCharArray(_cfg.pwd, 100);
  _server.arg("host").toCharArray(_cfg.host, 100);
  _cfg.port = _server.arg("port").toInt();
  _server.arg("url").toCharArray(_cfg.url, 200);
  IPAddress ip, gateway, subnet;
  if (ip.fromString(_server.arg("ip"))) for (uint8_t i = 0; i < 4; i++) _cfg.ip[i] = ip[i];
  if (gateway.fromString(_server.arg("gateway"))) for (uint8_t i = 0; i < 4; i++) _cfg.gateway[i] = gateway[i];
  if (subnet.fromString(_server.arg("subnet"))) for (uint8_t i = 0; i < 4; i++) _cfg.subnet[i] = subnet[i];
  fsWriteConfig();
  sendWebPage();
}

void sendWebPage(void) {
  _dontSleep = true;
  String out = "";
  out.concat(F("<!DOCTYPE html><html><head><meta name=viewport content=initial-scale=1,maximum-scale=1,user-scalable=no><style>html,body{font-family:Arial;font-size:14px;background:#fff;padding:3px;color:#000;margin:0;width:100%;line-height:2em;box-sizing:border-box}section{width:250px;margin:0 auto}fieldset>legend{font-weight:bolder}header,footer{text-align:center}footer{color:#888;font-size:.75rem}</style></head><body><header><h1>Poop Inc.</h1></header><section><form method=post><fieldset><legend>Network settings</legend><label for=n>SSID:</label><input type=text name=ssid id=n value=\""));
  out.concat(_cfg.ssid);
  out.concat(F("\"><br><label for=p>Password:</label><input type=password name=pwd id=p value=\""));
  out.concat(_cfg.pwd);
  out.concat(F("\"><br><label for=i>IP:</label><input type=text name=ip id=i value=\""));
  for (uint8_t i = 0; i < 4; i++) out.concat(_cfg.ip[i] + (i < 3 ? DOT : EMPTY_STR));
  out.concat(F("\"><br><label for=g>Gateway:</label><input type=text name=gateway id=g value=\""));
  for (uint8_t i = 0; i < 4; i++) out.concat(_cfg.gateway[i] + (i < 3 ? DOT : EMPTY_STR));
  out.concat(F("\"><br><label for=s>Subnet:</label><input type=text name=subnet id=s value=\""));
  for (uint8_t i = 0; i < 4; i++) out.concat(_cfg.subnet[i] + (i < 3 ? DOT : EMPTY_STR));
  out.concat(F("\"></fieldset><fieldset><legend>Endpoint settings</legend><label for=h>Host:</label><input type=text name=host id=h value=\""));
  out.concat(_cfg.host);
  out.concat(F("\"><br><label for=p>Port:</label><input type=number name=port id=p step=1 value=\""));
  out.concat(_cfg.port);
  out.concat(F("\"><br><label for=u>URL:</label><input type=url name=url id=u value=\""));
  out.concat(_cfg.url);
  out.concat(F("\"><br><input type=submit value=Submit></fieldset></form></section><footer> Poop Inc. &copy; 2017 Cl&eacute;ment Ronzon </footer></body></html>"));
  _server.send(200, F("text/html"), out);
}

