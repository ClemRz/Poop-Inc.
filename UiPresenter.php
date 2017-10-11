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

require('BasePresenter.php');

class UiPresenter extends BasePresenter {

    const CLIENT_TIME_ZONE = "America/Hermosillo";
    const SERVER_TIME_ZONE = "Europe/Paris";
    const PIVOT_TIME_ZONE = "UTC";

    const MILLISECOND = 1000;

    const HIGH_SPEED_RATE = 10; //seconds
    const LOW_SPEED_RATE = 20; //seconds
    const LOW_SPEED_START_HOUR = 20;
    const LOW_SPEED_END_HOUR = 7;
    const LOW_SPEED_DAYS = 'Sat,Sun';

    const CURRENT_STATUS_SQL = "SELECT
            p1.mac,
            p1.date,
            TIMEDIFF(NOW(), p1.date) AS duration,
            pl.name,
            p1.batteries,
            p1.status XOR pl.invert AS status
        FROM poop p1
        INNER JOIN (SELECT mac, MAX(date) date FROM poop GROUP BY mac) p2 ON p1.mac = p2.mac AND p1.date = p2.date
        INNER JOIN poop_locations pl ON pl.mac = p1.mac
        ORDER BY name DESC";

    private $_currentStatus = [];

    public function __construct() {
        parent::__construct();
        $this->buildCurrentStatus();
    }

    public function getCurrentStatus() {
        return $this->_currentStatus;
    }

    public function getSamplingRateMs() {
        return ($this->lowTrafficSchedule() ? self::LOW_SPEED_RATE : self::HIGH_SPEED_RATE) * self::MILLISECOND;
    }

    private function lowTrafficSchedule(){
        $now = new DateTime();
        $day = $now->format('D');
        return intval($now->format('H')) >= self::LOW_SPEED_START_HOUR || intval($now->format('H')) <= self::LOW_SPEED_END_HOUR || in_array($day, explode(",", self::LOW_SPEED_DAYS));
    }

    private function buildCurrentStatus() {
        $query = tep_db_query(self::CURRENT_STATUS_SQL);
        while ($row = tep_db_fetch_array($query)) {
            $this->addStatus($row);
        }
    }

    private function addStatus($status) {
        array_push($this->_currentStatus, $status);
    }
}