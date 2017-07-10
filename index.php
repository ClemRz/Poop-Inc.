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
    
require('indexPresenter.php'); ?>

<!DOCTYPE html>
<html>
<head>
    <title>Poop Inc.</title>
    <style>
        body {
            font-family: arial, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        table, th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #d0d0d0;
        }

        th {
            background-color: #4169e1;
            color: white;
        }

        td.red, td.green {
            color: white;
        }

        td.red {
            background-color: #f00;
        }

        td.green {
            background-color: #008000;
        }

        canvas {
            -moz-user-select: none;
            -webkit-user-select: none;
            -ms-user-select: none;
        }

        .main_container {
            display: flex;
            justify-content: space-between;
        }

        .sub_container {
            width: 45%;
        }
    </style>
    <script type="text/javascript" src="Chart.bundle.min.js"></script>
    <script>
        (function () {
            const N = 24;
            var color = Chart.helpers.color;
            var labels = Array.apply(null, {length: N}).fill().map(function (e, i) {
                return i + ':00';
            });
            var config = {
                type: 'radar',
                options: {
                    title: {
                        display: true,
                        text: "Daily averages (minutes per hour)"
                    },
                    elements: {
                        line: {
                            tension: 0.2
                        }
                    },
                    scale: {
                        beginAtZero: true
                    }
                },
                data: {
                    labels: labels,
                    datasets: <?php echo json_encode($dataSets); ?>
                }
            };

            config.data.datasets.forEach(function (dataSet) {
                dataSet.backgroundColor = color(dataSet.borderColor).alpha(0.2).rgbString();
                dataSet.pointBackgroundColor = dataSet.borderColor;
            });

            window.onload = function () {
                console.log(config.data.datasets[config.data.datasets.length - 1].data);
                var myRadar = new Chart(document.getElementById("canvas"), config);
                config.data.datasets[config.data.datasets.length - 1].data = config.data.datasets[config.data.datasets.length - 1].data.map(function (value, key) {
                    return (key >= <?php echo LOW_SPEED_START_HOUR;?> || key <= <?php echo LOW_SPEED_END_HOUR;?>) ? myRadar.scale.end : value;
                });
                var transparentGray = 'rgba(99, 99, 99, 0.2)';
                var currentHour = (new Date()).getHours();
                var currentRegion = Array.apply(null, {length: N}).fill(NaN);
                currentRegion[currentHour] = myRadar.scale.end;
                currentRegion[currentHour + 1] = myRadar.scale.end;
                var currentTimeDataSet = {
                    label: 'Current time',
                    borderColor: transparentGray,
                    backgroundColor: transparentGray,
                    pointBackgroundColor: transparentGray,
                    lineTension: 0,
                    data: currentRegion
                };
                config.data.datasets.push(currentTimeDataSet);
                myRadar.update();
                //setTimeout("location.reload(true);", <?php echo getSamplingRate() * MILLISECOND; ?>);
            };
        })();
    </script>
</head>
<body>
<div class="main_container">
    <div class="sub_container">
        <table>
            <tr>
                <th width="25%">Name</th>
                <th width="25%">Time since last event</th>
                <th width="25%">Status</th>
                <th width="25%">Batteries</th>
            </tr>
            <?php
            $query = tep_db_query($tableSql);
            while ($row = tep_db_fetch_array($query)) {
                $batteryPercent = max(min(round(($row['batteries'] - LOWER_BATTERY_LIMIT) / (UPPER_BATTERY_LIMIT - LOWER_BATTERY_LIMIT) * 100), 100), 0);
                ?>
                <tr>
                    <td title="<?php echo $row['mac']; ?>"><?php echo $row['name']; ?></td>
                    <td title="<?php echo getDateTimeFromStr($row['date'])->format('Y-m-d H:i:s'); ?>"><?php echo $row['duration']; ?></td>
                    <td class="<?php echo $row['status'] ? "red" : "green"; ?>"><?php echo $row['status'] ? "ENGAGED" : "VACANT"; ?></td>
                    <td title="<?php echo $row['batteries']; ?>V"><?php echo $batteryPercent; ?>%</td>
                </tr>
                <?php
            }
            ?>
        </table>
        <div>
            <br>Contingency Plan: <a href="https://goo.gl/AeZY49" target="_blank">click here</a>.
        </div>
    </div>
    <div class="sub_container">
        <canvas id="canvas"></canvas>
    </div>
</div>
</body>
</html>