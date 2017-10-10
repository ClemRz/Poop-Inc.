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

class IndexPresenter extends BasePresenter {

    const MILLISECOND = 1000;

    const HIGH_SPEED_RATE = 10; //seconds
    const LOW_SPEED_RATE = 20; //seconds
    const LOW_SPEED_START_HOUR = 20;
    const LOW_SPEED_END_HOUR = 7;
    const LOW_SPEED_DAYS = 'Sat,Sun';

    const UPPER_BATTERY_LIMIT = 4.06;
    const LOWER_BATTERY_LIMIT = 2.54;

    const GREEN = 'rgb(0, 128, 0)';
    const ORANGE = 'rgb(255, 165, 0)';
    const RED = 'rgb(255, 0, 0)';

    const DATA_SET_SQL = "SELECT
            pl.name,
            p.status XOR pl.invert AS status,
            p.date
        FROM poop p
        INNER JOIN poop_locations pl ON p.mac = pl.mac
        WHERE p.date >= NOW() - INTERVAL 1 MONTH
        ORDER BY pl.name, p.date";
    const DAY_COUNT_SQL = "SELECT
            pl.name,
            5 * (DATEDIFF(MAX(p.date), MIN(p.date)) DIV 7) + MID('0123444401233334012222340111123400001234000123440', 7 * WEEKDAY(MIN(p.date)) + WEEKDAY(MAX(p.date)) + 1, 1) AS days
        FROM poop p
        INNER JOIN poop_locations pl ON p.mac = pl.mac
        WHERE p.date >= NOW() - INTERVAL 1 MONTH
        GROUP BY pl.name
        ";

    private $_dataSets = [];

    public function __construct() {
        parent::__construct();
        $this->buildDataSets();
    }

    public function getDataSets() {
        return $this->_dataSets;
    }

    public function getDataSetsAsJson() {
        return json_encode($this->_dataSets);
    }

    public function getBatteryPercent($voltage) {
        return max(min(round(($voltage - self::LOWER_BATTERY_LIMIT) / (self::UPPER_BATTERY_LIMIT - self::LOWER_BATTERY_LIMIT) * 100), 100), 0);
    }

    public function getFormattedDateTime($dateTime) {
        return $this->getDateTimeFromStr($dateTime)->format('Y-m-d H:i:s');
    }

    public function getSamplingRateMs() {
        return ($this->lowTrafficSchedule() ? self::LOW_SPEED_RATE : self::HIGH_SPEED_RATE) * self::MILLISECOND;
    }

    private function getDateTimeFromStr($dateStr) {
        $date = new DateTime($dateStr);
        return $this->getDateTime($date);
    }

    private function array_average(&$arr, $key, $count) {
        $now = new DateTime();
        $n = intval($now->format('H')) >= $key ? $count : max($count - 1, 1);
        $arr = array_sum($arr) / $n;
    }

    private function getDateTime(DateTime $date) {
        if (TZ_OFFSET > 0) {
            $date->add(new DateInterval('PT' . TZ_OFFSET . 'S'));
        }
        if (TZ_OFFSET < 0) {
            $date->sub(new DateInterval('PT' . (-1 * TZ_OFFSET) . 'S'));
            return $date;
        }
        return $date;
    }

    private function lowTrafficSchedule(){
        $now = new DateTime();
        $day = $now->format('D');
        return intval($now->format('H')) >= self::LOW_SPEED_START_HOUR || intval($now->format('H')) <= self::LOW_SPEED_END_HOUR || in_array($day, explode(",", self::LOW_SPEED_DAYS));
    }

    private function buildDataSets() {
        $query = tep_db_query(self::DATA_SET_SQL);
        $grouped = [];
        $lastDoor = null;
        $lastStatus = 0;
        $startDate = 0;
        while ($row = tep_db_fetch_array($query)) {
            if ($lastDoor != $row['name']) {
                $lastStatus = 0;
                $startDate = 0;
                $lastDoor = $row['name'];
            }
            if ($row['status'] && !$lastStatus) {
                $startDate = $this->getDateTimeFromStr($row['date']);
            }
            if ($startDate != 0 && !$row['status'] && $lastStatus) {
                if (!array_key_exists($row['name'], $grouped)) {
                    $grouped[$row['name']] = [];
                }
                $hour = intval($startDate->format('H'));
                if (!array_key_exists($hour, $grouped[$row['name']])) {
                    $grouped[$row['name']][$hour] = [];
                }
                $endDate = $this->getDateTimeFromStr($row['date']);
                $endDateStr = $endDate->format('Y-m-d');
                if (!array_key_exists($endDateStr, $grouped[$row['name']][$hour])) {
                    $grouped[$row['name']][$hour][$endDateStr] = 0;
                }
                $interval = $startDate->diff($endDate);
                $hours = $interval->h + $interval->i / 60 + $interval->s / 3600;
                $grouped[$row['name']][$hour][$endDateStr] += $hours;
            }
            $lastStatus = $row['status'];
        }

        $count = [];
        $query = tep_db_query(self::DAY_COUNT_SQL);
        while ($row = tep_db_fetch_array($query)) {
            $count[$row['name']] = intval($row['days']);
        }

        foreach ($grouped as $name => &$hours) {
            array_walk($hours, array($this, "array_average"), $count[$name]);
        }
        unset($hours);

        $colours = [self::GREEN, self::ORANGE, self::RED];
        $i = 0;
        foreach ($grouped as $key => $averages) {
            $dataSet = (object)[
                'label' => $key,
                'borderColor' => $colours[$i],
                'data' => []
            ];
            for ($j = 0; $j <= 23; $j++) {
                array_push($dataSet->data, array_key_exists($j, $averages) ? round(min($averages[$j], 1) * 60) : 0);
            }
            $this->addDataSet($dataSet);
            $i++;
        }
        $dataSet = (object)[
            'label' => 'Low speed sampling',
            'borderColor' => 'rgba(5, 64, 255, 0.1)',
            'data' => array_fill(0, 24, 'null')
        ];
        $this->addDataSet($dataSet);
    }

    private function addDataSet($dataSet) {
        array_push($this->_dataSets, $dataSet);
    }

/*
SET @dat = DATE(0);
SELECT
    *,
    TIMESTAMPDIFF(SECOND, @dat, date) time,
    @dat + INTERVAL TIMESTAMPDIFF(SECOND, @dat, date)/2 SECOND median_date,
    @dat lag_date,
    @dat:=date curr_date
FROM poop
ORDER BY date


SET @dat = DATE(0);
SELECT
    *,
    TIMESTAMPDIFF(SECOND, @dat, date) time,
    @dat lag_date,
    @dat:=date curr_date,
    HOUR(CONVERT_TZ(date, SELECT CASE WHEN NOW() - INTERVAL YEAR(NOW()) YEAR BETWEEN '0000-03-26' AND '0000-10-30' THEN '+02:00' ELSE '+01:00' END, '-07:00')) hour
FROM poop
ORDER BY date

SELECT
	p.mac,
	p.status XOR pl.invert AS status,
	HOUR(CONVERT_TZ(p.date, CASE WHEN NOW() - INTERVAL YEAR(NOW()) YEAR BETWEEN '0000-03-26' AND '0000-10-30' THEN '+02:00' ELSE '+01:00' END, '-07:00')) AS hour,
	SUM(p.status XOR pl.invert) AS count
FROM poop p
INNER JOIN poop_locations pl ON p.mac = pl.mac
GROUP BY 1, 2, 3
HAVING status IS TRUE
ORDER BY 3

SELECT CASE WHEN NOW() - INTERVAL YEAR(NOW()) YEAR BETWEEN '0000-03-26' AND '0000-10-30' THEN '+02:00' ELSE '+01:00' END

 * */

}