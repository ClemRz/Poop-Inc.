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
$data = array(
    'mac' => $_GET['mac'],
    'status' => $_GET['status'],
    'batteries' => $_GET['batteries']
);
tep_db_perform('poop', $data);
$config = (object)[
    'wakeUpRate' => getSamplingRate(),
    'url' => 'http://twcc.fr/poop/notify.php' //Beware, the url memory allocation in the microchip is limited to 200 characters
];
header('Content-Type: application/json');
echo json_encode($config);
