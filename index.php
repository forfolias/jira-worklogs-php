<?php

require_once "config.php";
require_once "functions.php";

$availableUsers = json_decode(AVAILABLE_USERS, true);
$usersToDisplay = !empty($_REQUEST['users']) ? $_REQUEST['users'] : array(USERNAME);
$startDate = !empty($_REQUEST['startDate']) ? $_REQUEST['startDate'] : date("Y-m-d");
$endDate = !empty($_REQUEST['endDate']) ? $_REQUEST['endDate'] : date("Y-m-d");
if ($endDate < $startDate) {
    $endDate = $startDate;
}
$displayWeekends = !empty($_REQUEST['displayWeekends']) ? $_REQUEST['displayWeekends'] : "0";
$days = getDays($startDate, $endDate, $displayWeekends);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Jira Worklogs</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & JQuery -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script type="text/javascript" src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- Datatables -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/dataTables.bootstrap.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.13/css/dataTables.bootstrap.min.css">

    <!-- Bootstrap Multiselect -->
    <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.min.js">
    </script>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css"
          type="text/css"/>

    <!-- DatePicker -->
    <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js">
    </script>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker3.css"
          type="text/css"/>

    <!-- Custom css -->
    <link rel="stylesheet" href="custom.css" type="text/css"/>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1><span class="glyphicon glyphicon-time"></span> Jira worklogs</h1>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <form action="index.php" method="get">
                <div class="row">
                    <div class="col-md-7 col-sm-12">
                        <div class="row">
                            <div class="form-group col-xs-4 col-md-5">
                                <div class="input-group">
                                    <label class="input-group-addon" for="startDate">Start</label>
                                    <input type="text" class="form-control date-input" id="startDate" name="startDate"
                                           value="<?= $startDate ?>"/>
                                </div>
                            </div>
                            <div class="form-group col-xs-4 col-md-5">
                                <div class="input-group">
                                    <label class="input-group-addon" for="endDate">End</label>
                                    <input type="text" class="form-control date-input" id="endDate" name="endDate"
                                           value="<?= $endDate ?>"/>
                                </div>
                            </div>
                            <div class="checkbox col-xs-4 col-md-2">
                                <label>
                                    <input type="checkbox" name="displayWeekends"
                                           value="1" <?= $displayWeekends ? 'checked="checked"' : '' ?>> Display
                                    weekends
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5 col-sm-12">
                        <div class="row">
                            <div class="form-group col-xs-8">
                                <label class="sr-only" for="user-select">Users:</label>
                                <div class="input-group">
                                    <div class="input-group-addon">Users</div>
                                    <select id="user-select" multiple="multiple" name="users[]">
                                        <?php
                                        foreach ($availableUsers as $team => $members) {
                                            echo '<optgroup label="' . $team . '">' . "\n";
                                            foreach ($members as $id => $name) {
                                                echo '<option value="' . $id . '" ';
                                                echo in_array($id, $usersToDisplay) ? 'selected="selected"' : '';
                                                echo '>' . $name . '</option>' . "\n";
                                            }
                                            echo '</optgroup>' . "\n";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <input type="submit" class="btn btn-primary pull-right" value="Submit"/>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <ul class="nav nav-tabs">
                <?php
                $first = true;
                foreach ($usersToDisplay as $user) {
                    echo '<li class="' . ($first ? 'active' : '') . '">';
                    echo '<a href="#' . $user . '" data-toggle="tab">' . getUserFromKey($availableUsers,
                            $user) . '</a></li>';
                    $first = false;
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tab-content">
                <?php

                $first = true;
                foreach ($usersToDisplay as $user) {

                    $response = getWorklogs($days, $user);
                    $tasks = getTasks($response);
                    $tasksPerDay = initializeTasksPerDay($tasks, $days, $response);

                    echo '<div role="tabpanel" class="table-responsive tab-pane ' . ($first ? 'active' : '') . '" ';
                    echo 'id="' . $user . '">';
                    echo '<table class="tasks-table table table-striped table-hover"><thead><tr><th>Task</th>';

                    /* display header */
                    foreach ($days as $day) {
                        echo '<th class="' .
                            (strtotime($day . " 00:00:00") > time() ? 'text-italic text-muted' : '') . '">';
                        echo date("D d/m", strtotime($day)) . "</th>";
                    }
                    echo "<th>Task Summary</th></tr></thead>";

                    /* display hours per task per day */
                    $totalDuration = 0;
                    foreach ($tasks as $task) {
                        echo '<tr><td><a href="' . DOMAIN . "/browse/" . $task . '">' . $task . "</a></td>";
                        $taskDuration = 0;
                        foreach ($days as $day) {
                            $tempDuration = round($tasksPerDay[$day][$task] / 60 / 60, 2);
                            echo '<td class="' . ($tempDuration == 0 ? 'text-muted' : 'text-bold') . ' ' .
                                (strtotime($day . " 00:00:00") > time() ? 'text-italic text-muted' : '') . '">';
                            echo $tempDuration . '</td>';
                            $taskDuration += $tasksPerDay[$day][$task];
                        }
                        echo '<td class="bg-info"><strong>' . round($taskDuration / 60 / 60, 2) . "</strong></td>";
                        echo "</tr>";
                        $totalDuration += $taskDuration;
                    }

                    /* display summary per day */
                    echo '<tfoot><tr class="info"><td><strong>Day Summary</strong></td>';
                    foreach ($days as $day) {
                        $tempDuration = round($tasksPerDay[$day]['duration'] / 60 / 60, 2);
                        echo "<td><strong>" . $tempDuration . "</strong> (" .
                            round((100 * $tempDuration) / HOURS_PER_DAY, 2) . "%)</td>";
                    }
                    $workingHours = HOURS_PER_DAY * count($days);
                    $workedHours = round($totalDuration / 60 / 60, 2);
                    echo "<td><strong>" . $workedHours . "/" . $workingHours . "</strong>";
                    echo "(" . round((100 * $workedHours) / $workingHours, 2) . "%)</td>";
                    echo "</tr></tfoot></table>";
                    echo '</div>';

                    $first = false;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#user-select').multiselect({
            numberDisplayed: 1,
            enableClickableOptGroups: true
        });

        $('.date-input').datepicker({
            format: "yyyy-mm-dd",
            todayHighlight: true,
            autoclose: true
        });

        $('.tasks-table').DataTable({
            "ordering": true,
            "order": [[0, "asc"]],
            "info": true,
            "paging": true,
            "pageLength": 10,
            "pagingType": "numbers",
            "dom": '<"top">rt<"pull-right"f><"pull-left"p><"text-center"i><"clear">'
        });
    });
</script>

</body>
</html>
