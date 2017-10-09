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

define('MILLISECOND', 1000);
define('SECOND', 1);
define('MINUTE', 60 * SECOND);
define('HOUR', 60 * MINUTE);
define('DAY', 24 * HOUR);

define('HIGH_SPEED_RATE', 10 * SECOND);
define('LOW_SPEED_RATE', 20 * SECOND);
define('LOW_SPEED_START_HOUR', 20);
define('LOW_SPEED_END_HOUR', 7);
define('LOW_SPEED_DAYS', serialize(['Sat', 'Sun']));

define('UPPER_BATTERY_LIMIT', 4.06);
define('LOWER_BATTERY_LIMIT', 2.54);

define('CLIENT_TIME_ZONE', "America/Hermosillo");
define('SERVER_TIME_ZONE', "Europe/Paris");
define('PIVOT_TIME_ZONE', "UTC");

define('COLOURS', serialize([
    'rgb(0, 128, 0)', //green
    'rgb(255, 165, 0)', //orange
    'rgb(255, 0, 0)', //red
]));

date_default_timezone_set(PIVOT_TIME_ZONE);
$tzHMO = new DateTimeZone(CLIENT_TIME_ZONE);
$tzPAR = new DateTimeZone(SERVER_TIME_ZONE);
$now = new DateTime();
define('TZ_OFFSET', $tzHMO->getOffset($now) - $tzPAR->getOffset($now));
unset($now, $tzPAR, $tzHMO);
date_default_timezone_set(CLIENT_TIME_ZONE);

function getSamplingRate()
{
    return lowTrafficSchedule() ? LOW_SPEED_RATE : HIGH_SPEED_RATE;
}

function getDateTimeFromStr($dateStr)
{
    $date = new DateTime($dateStr);
    return getDateTime($date);
}

function getDateTime(DateTime $date)
{
    if (TZ_OFFSET > 0) {
        $date->add(new DateInterval('PT' . TZ_OFFSET . 'S'));
    }
    if (TZ_OFFSET < 0) {
        $date->sub(new DateInterval('PT' . (-1 * TZ_OFFSET) . 'S'));
        return $date;
    }
    return $date;
}

function array_average(&$arr, $key, $count)
{
    $now = new DateTime();
    $n = intval($now->format('H')) >= $key ? $count : max($count - 1, 1);
    $arr = array_sum($arr) / $n;
}

function lowTrafficSchedule()
{
    $now = new DateTime();
    $day = $now->format('D');
    return intval($now->format('H')) >= LOW_SPEED_START_HOUR || intval($now->format('H')) <= LOW_SPEED_END_HOUR || in_array($day, unserialize(LOW_SPEED_DAYS));
}