<?php


/**
 * Fetches the worklogs from the custom api plugin
 *
 * @param $days
 * @param $user
 * @return array|mixed
 */
function getWorklogs($days, $user)
{
    $startDate = $days[0];
    $endDate = $days[count($days) - 1];

    $url = DOMAIN . '/rest/jira-worklog-query/1/find/worklogs?';
    $url .= 'startDate=' . $startDate . '&endDate=' . $endDate . '&user=' . $user;

    /* fetch data */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERPWD, USERNAME . ":" . PASSWORD);
    $response = curl_exec($ch);

    if (curl_error($ch)) {
        curl_close($ch);
        return array(array(array("issueKey" => "Error getting data", "startDate" => $startDate, "duration" => 0)));
    }

    curl_close($ch);

    $response = json_decode($response, true);

    if (!$response) {
        $response = array(array(array("issueKey" => "Error getting data", "startDate" => $startDate, "duration" => 0)));
    }

    return $response;
}

/**
 * Returns an array containing all the tasks
 *
 * @param $response
 * @return array
 */
function getTasks($response)
{
    $tasks = array();

    /* find all tasks */
    foreach ($response[0] as $log) {
        if (!in_array($log['issueKey'], $tasks)) {
            $tasks[] = $log['issueKey'];
        }
    }

    return $tasks;
}

/**
 * Returns an array with all tasks per day and its duration
 *
 * @param $tasks
 * @param $days
 * @param $response
 * @return array
 */
function initializeTasksPerDay($tasks, $days, $response)
{
    $tasksPerDay = array();

    /* initialize tasks per day */
    foreach ($days as $day) {
        $tasksPerDay[$day]["duration"] = 0;
        foreach ($tasks as $task) {
            $tasksPerDay[$day][$task] = 0;
        }
    }

    /* summarize per day duration and set duration per day per task */
    foreach ($response[0] as $log) {
        $date = date("Y-m-d", strtotime($log["startDate"]));
        if (!isset($tasksPerDay[$date][$log['issueKey']])) {
            continue;
        }
        $tasksPerDay[$date][$log['issueKey']] += $log['duration'];
        $tasksPerDay[$date]['duration'] += $log['duration'];
    }

    return $tasksPerDay;
}

/**
 * Returns an array containing all days between the provided dates
 *
 * @param      $startDate
 * @param      $endDate
 * @param bool $weekends
 * @return array
 */
function getDays($startDate, $endDate, $weekends = true)
{
    $days = array();

    $date = new DateTime($startDate . " 00:00:00");
    $date2 = new DateTime($endDate . " 23:59:59");

    $period = (int)$date2->diff($date)->format("%a");

    for ($i = 0; $i < $period + 1; $i++) {
        if ($weekends || !in_array($date->format("w"), array(0, 6))) {
            $days[] = $date->format("Y-m-d");
        }
        $date->modify("+1 day");
    }

    return $days;
}

/**
 * Returns the name of a user based on the username
 *
 * @param $users
 * @param $key
 * @return string
 */
function getUserFromKey($users, $key)
{
    foreach ($users as $team => $members) {
        if (isset($members[$key])) {
            return $members[$key];
        }
    }

    return "";
}
