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
    
require('IndexPresenter.php');
$presenter = new IndexPresenter();
?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no">
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

            @media screen and (min-width: 1000px) {
                .main_container {
                    display: flex;
                    justify-content: space-between;
                }

                .sub_container {
                    width: 45%;
                }
            }

            @media screen and (max-width: 1000px) {
                #statistics {
                    display: none;
                }
            }
        </style>
        <script type="text/javascript" src="Chart.bundle.min.js"></script>
        <script type="text/javascript" src="../js/vendor/jquery-1.11.1.min.js"></script>
        <script>
            (function ($) {
                const N = 24;
                var originalText;
                var notifications = [];
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
                        datasets: <?php echo $presenter->getDataSetsAsJson(); ?>
                    }
                };

                config.data.datasets.forEach(function (dataSet) {
                    dataSet.backgroundColor = color(dataSet.borderColor).alpha(0.2).rgbString();
                    dataSet.pointBackgroundColor = dataSet.borderColor;
                });

                function sendNotification(name) {
                    if (Notification.permission !== "granted") {
                        Notification.requestPermission();
                    } else {
                        new Notification('A bathroom got free', {
                            icon: 'https://image.freepik.com/free-icon/man-sitting-in-the-bathroom_318-29212.jpg',
                            body: name + ' got free, run!'
                        });
                    }
                }

                function getStatusByName(arr, name) {
                    for (var i = 0; i < arr.length; i ++) {
                        if (arr[i].name === name) {
                            return !!1*arr[i].status;
                        }
                    }
                    return false;
                }

                function clearNotificationByName(name) {
                    notifications.splice(notifications.indexOf(name), 1);
                }

                function setDisabledState(button) {
                    $(button).text(originalText);
                }

                function setEnabledState(button) {
                    $(button).text('cancel notification');
                }

                function clearAllNotifications() {
                    notifications = [];
                    $('.notify-me').each(function (idx, button) {
                        setDisabledState(button);
                    });
                }

                function checkForAvail() {
                    if (notifications.length) {
                        $.get('api/').then(function (response) {
                            for (var i = 0; i <notifications.length; i ++) {
                                var name = notifications[i];
                                console.log('checking "' + name + '"');
                                if (!getStatusByName(response, name)) {
                                    sendNotification(name);
                                    clearAllNotifications();
                                    return;
                                }
                            }
                        });
                    }
                }

                function notifyClickHandler(event) {
                    var button = event.target;
                    var name = button.name;
                    if (notifications.indexOf(name) != -1) {
                        clearNotificationByName(name);
                        setDisabledState(button);
                    } else {
                        notifications.push(name);
                        setEnabledState(button);
                    }
                }

                function init() {
                    var myRadar = new Chart($('#canvas'), config);
                    config.data.datasets[config.data.datasets.length - 1].data = config.data.datasets[config.data.datasets.length - 1].data.map(function (value, key) {
                        return (key >= <?php echo IndexPresenter::LOW_SPEED_START_HOUR;?> || key <= <?php echo IndexPresenter::LOW_SPEED_END_HOUR;?>) ? myRadar.scale.end : value;
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
                    if (Notification && Notification.permission !== 'granted') Notification.requestPermission();
                    setInterval(function () { checkForAvail(); }, <?php echo IndexPresenter::HIGH_SPEED_RATE * IndexPresenter::MILLISECOND; ?>);
                    var $notifyButtons = $('.notify-me');
                    originalText = $notifyButtons.first().text();
                    $notifyButtons.click(notifyClickHandler);
                }

                $(document).ready(init);
            })(jQuery);
        </script>
    </head>
    <body>
        <div class="main_container">
            <div class="sub_container">
                <table>
                    <tr>
                        <th width="22%">Name</th>
                        <th width="22%">Time since last event</th>
                        <th width="22%">Status</th>
                        <th width="22%">Batteries</th>
                        <th width="12%">Notifications</th>
                    </tr>
                    <?php foreach ($presenter->getCurrentStatus() as $row) { ?>
                        <tr>
                            <td title="<?php echo $row['mac']; ?>"><?php echo $row['name']; ?></td>
                            <td title="<?php echo $presenter->getFormattedDateTime($row['date']); ?>"><?php echo $row['duration']; ?></td>
                            <td class="<?php echo $row['status'] ? "red" : "green"; ?>"><?php echo $row['status'] ? "ENGAGED" : "VACANT"; ?></td>
                            <td title="<?php echo $row['batteries']; ?>V"><?php echo $presenter->getBatteryPercent($row['batteries']); ?>%</td>
                            <td><button class="notify-me" name="<?php echo $row['name']; ?>">notify me</button></td>
                        </tr>
                    <?php } ?>
                </table>
                <div>
                    <br>Contingency Plan: <a href="https://goo.gl/AeZY49" target="_blank">click here</a>.
                </div>
            </div>
            <div class="sub_container" id="statistics">
                <canvas id="canvas"></canvas>
            </div>
        </div>
    </body>
</html>