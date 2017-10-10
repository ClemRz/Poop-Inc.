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
        date_default_timezone_set(self::PIVOT_TIME_ZONE);
        $tzHMO = new DateTimeZone(self::CLIENT_TIME_ZONE);
        $tzPAR = new DateTimeZone(self::SERVER_TIME_ZONE);
        $now = new DateTime();
        define('TZ_OFFSET', $tzHMO->getOffset($now) - $tzPAR->getOffset($now));
        unset($now, $tzPAR, $tzHMO);
        date_default_timezone_set(self::CLIENT_TIME_ZONE);

        $this->buildCurrentStatus();
    }

    public function getCurrentStatus() {
        return $this->_currentStatus;
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