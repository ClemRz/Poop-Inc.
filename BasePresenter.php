<?php
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

require($_SERVER['DOCUMENT_ROOT'] . '/includes/application_top.php');

class BasePresenter {

    const CLIENT_TIME_ZONE = "America/Hermosillo";
    const SERVER_TIME_ZONE = "Europe/Paris";
    const PIVOT_TIME_ZONE = "UTC";

    const MILLISECOND = 1000;

    const HIGH_SPEED_RATE = 10; //seconds
    const LOW_SPEED_RATE = 20; //seconds
    const LOW_SPEED_START_HOUR = 20;
    const LOW_SPEED_END_HOUR = 7;
    const LOW_SPEED_DAYS = 'Sat,Sun';

    const ENDPOINT_PORT = 443;
    const ENDPOINT_HOST = 'twcc.fr'; // 'domain.com'; //Beware, the url memory allocation in the microchip is limited to 100 characters
    const ENDPOINT_URL = '/poop/notify.php'; //Beware, the url memory allocation in the microchip is limited to 200 characters
    const ENDPOINT_KEY = 'SmeXZmAh83BdqTFysdEd'; // '<key>';

    public function __construct() {
        date_default_timezone_set(self::PIVOT_TIME_ZONE);
        $tzHMO = new DateTimeZone(self::CLIENT_TIME_ZONE);
        $tzPAR = new DateTimeZone(self::SERVER_TIME_ZONE);
        $now = new DateTime();
        define('TZ_OFFSET', $tzHMO->getOffset($now) - $tzPAR->getOffset($now));
        unset($now, $tzPAR, $tzHMO);
        date_default_timezone_set(self::CLIENT_TIME_ZONE);
    }

    public function getSamplingRateMs() {
        return $this->getSamplingRate() * self::MILLISECOND;
    }

    public function savedData($arr) {
        tep_db_perform('poop', $this->buildData($arr));
    }

    public function isValidKey($arr) {
        return $arr['key'] == self::ENDPOINT_KEY;
    }

    public function getConfig() {
        return (object)[
            'wakeUpRate' => $this->getSamplingRate(),
            'url' => 'https://' . self::ENDPOINT_HOST . self::ENDPOINT_URL . '?key=' . self::ENDPOINT_KEY,
            'host' => self::ENDPOINT_HOST,
            'port' => self::ENDPOINT_PORT
        ];
    }

    private function lowTrafficSchedule(){
        $now = new DateTime();
        $day = $now->format('D');
        return intval($now->format('H')) >= self::LOW_SPEED_START_HOUR || intval($now->format('H')) <= self::LOW_SPEED_END_HOUR || in_array($day, explode(",", self::LOW_SPEED_DAYS));
    }

    private function buildData($arr) {
        return array(
            'mac' => $arr['mac'],
            'status' => $arr['status'],
            'batteries' => $arr['batteries']
        );
    }

    private function getSamplingRate() {
        return $this->lowTrafficSchedule() ? self::LOW_SPEED_RATE : self::HIGH_SPEED_RATE;
    }
}