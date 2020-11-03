<?php
$servername = '';
$dbuser = '';
$dbpass = '';
$dbname = '';

function init_db_params($appServername, $appDbuser, $appDbpass, $appDbname) {
    global $servername, $dbuser, $dbpass, $dbname;
    $servername = $appServername;
    $dbuser = $appDbuser;
    $dbpass = $appDbpass;
    $dbname = $appDbname;
}

function save_message_log($appId, $userId, $type, $message) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $message = $mysqli->real_escape_string($message);
    $sql = "insert into chatbot_message_log values (null, $appId, '$userId', '$type', '$message', now())";
    $mysqli->query($sql);
    $mysqli->close();
}

function add_task($userId, $taskName, $term, $nextDate) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $taskId = 0;
    $sql = "select max(taskId) from cleaning_task";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $taskId = $row[0] + 1;
        }
        $result->free();
    }
    $taskName = $mysqli->real_escape_string($taskName);
    $sql = "insert into cleaning_task values ('$userId', $taskId, '$taskName', '$term', curdate(), '$nextDate', now())";
    $mysqli->query($sql);
    $mysqli->close();
}

function update_task($userId, $taskId, $taskName, $schedule, $nextDate) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $taskName = $mysqli->real_escape_string($taskName);
    $sql = "update cleaning_task"
        . " set taskName='$taskName', schedule='$schedule', nextDate='$nextDate', updateTime=now()"
        . " where userId='$userId' and taskId=$taskId";
    $mysqli->query($sql);
    $mysqli->close();
}

function update_task_dates($userId, $taskId, $lastDate, $nextDate) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "update cleaning_task"
        . " set lastDate='$lastDate', nextDate='$nextDate', updateTime=now()"
        . " where userId='$userId' and taskId=$taskId";
    $mysqli->query($sql);
    $mysqli->close();
}

function get_tasks_by_user($userId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if($mysqli->connect_errno) {
        exit();
    }
    $ret = [];
    $sql = "select taskId, taskName, schedule, lastDate, nextDate from cleaning_task"
        . " where userId='$userId' order by nextDate, taskId";
    if($result = $mysqli->query($sql)) {
        if($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $ret[] = [
                    "taskId" => $row[0],
                    "taskName" => $row[1],
                    "schedule" => $row[2],
                    "lastDate" => $row[3],
                    "nextDate" => $row[4],
                ];
            }
        }
        $result->free();
    }
    $mysqli->close();
    return $ret;
}

function delete_task($userId, $taskId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "delete from cleaning_task where userId='$userId' and taskId=$taskId";
    $mysqli->query($sql);
    $sql = "update cleaning_task set taskId=taskId-1 where userId='$userId' and taskId>$taskId";
    $mysqli->query($sql);
    $mysqli->close();
}

function get_task_detail($userId, $taskId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $ret = [];
    $sql = "select * from cleaning_task where userId='$userId' and taskId=$taskId";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $ret = [
                "userId" => $row[0],
                "taskId" => $row[1],
                "taskName" => $row[2],
                "schedule" => $row[3],
                "lastDate" => $row[4],
                "nextDate" => $row[5],
                "updateTime" => $row[6]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $ret;
}

function get_all_users() {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $ret = [];
    $sql = "select distinct userId from cleaning_task";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $ret[] = $row[0];
            }
        }
        $result->free();
    }
    $mysqli->close();
    return $ret;
}