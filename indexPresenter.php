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
    
require('utils.php');

$dataSetSql = "SELECT
	pl.name,
	p.status XOR pl.invert AS status,
	p.date
FROM poop p
INNER JOIN poop_locations pl ON p.mac = pl.mac
WHERE p.date >= NOW() - INTERVAL 1 MONTH
ORDER BY pl.name, p.date";
$query = tep_db_query($dataSetSql);
$grouped = [];
$lastDoor = null;
$lastStatus = 0;
while ($row = tep_db_fetch_array($query)) {
    if ($lastDoor != $row['name']) {
        $lastStatus = 0;
        $startDate = 0;
        $lastDoor = $row['name'];
    }
    if ($row['status'] && !$lastStatus) {
        $startDate = getDateTimeFromStr($row['date']);
    }
    if ($startDate != 0 && !$row['status'] && $lastStatus) {
        if (!array_key_exists($row['name'], $grouped)) {
            $grouped[$row['name']] = [];
        }
        $hour = intval($startDate->format('H'));
        if (!array_key_exists($hour, $grouped[$row['name']])) {
            $grouped[$row['name']][$hour] = [];
        }
        $endDate = getDateTimeFromStr($row['date']);
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

$dayCountSql = "SELECT
    pl.name,
    5 * (DATEDIFF(MAX(p.date), MIN(p.date)) DIV 7) + MID('0123444401233334012222340111123400001234000123440', 7 * WEEKDAY(MIN(p.date)) + WEEKDAY(MAX(p.date)) + 1, 1) AS days
FROM poop p
INNER JOIN poop_locations pl ON p.mac = pl.mac
WHERE p.date >= NOW() - INTERVAL 1 MONTH
GROUP BY pl.name
";
$count = [];
$query = tep_db_query($dayCountSql);
while ($row = tep_db_fetch_array($query)) {
    $count[$row['name']] = intval($row['days']);
}

foreach ($grouped as $name => &$hours) {
    array_walk($hours, "array_average", $count[$name]);
}
unset($hours);

$dataSets = [];
$i = 0;
foreach ($grouped as $key => $averages) {
    $dataSet = (object)[
        'label' => $key,
        'borderColor' => unserialize(COLOURS)[$i],
        'data' => []
    ];
    for ($j = 0; $j <= 23; $j++) {
        array_push($dataSet->data, array_key_exists($j, $averages) ? round(min($averages[$j], 1) * 60) : 0);
    }
    array_push($dataSets, $dataSet);
    $i++;
}
$dataSet = (object)[
    'label' => 'Low speed sampling',
    'borderColor' => 'rgba(5, 64, 255, 0.1)',
    'data' => array_fill(0, 24, 'null')
];
array_push($dataSets, $dataSet);

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
$tableSql = "SELECT
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