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
    $sql = "insert into chatbot_message_log values (null, $appId, '$userId', '$type', '$message', now());";
    $mysqli->query($sql);
    $mysqli->close();
}

function get_list_id_selected($userId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $listId = 0;
    $sql = "select listId from shopping_list_selected where userId='$userId'";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $listId = $row[0];
        }
        $result->free();
    }
    if($listId == 0) {
        $sql = "insert into shopping_list_selected values ('$userId', 1, now())";
        $mysqli->query($sql);
        $listId =1;
    }
    $mysqli->close();
    return $listId;
}

function get_list_referencing_info($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $refInfo = [];
    $sql = "select refUserId, refListId from shopping_list_share_info where userId='$userId' and listId=$listId";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $refInfo = [
                "refUserId" => $row[0],
                "refListId" => $row[1]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $refInfo;
}

function get_list_share_code($userId, $code) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "delete from shopping_list_share_code where deadLine < Now()";
    $mysqli->query($sql);
    
    $sharingInfo = [];
    //$sql = "select userId, listId from shopping_list_share_code where userId<>'$userId' and passCode='$code'";
    $sql = "select userId, listId from shopping_list_share_code where passCode='$code'";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $sharingInfo = [
                "userId" => $row[0],
                "listId" => $row[1]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $sharingInfo;
}

function get_list_shared_info($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sharedInfo = [];
    $sql = "select userId, listId from shopping_list_share_info where refUserId='$userId' and refListId=$listId";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $sharedInfo[] = [
                    "userId" => $row[0],
                    "listId" => $row[1]
                ];
            }
        }
        $result->free();
    }
    return $sharedInfo;
}

function add_list_shared_code($userId, $listId, $code) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "insert into shopping_list_share_code values ('$code', '$userId', $listId, now() + interval 1 hour, now());";
    $mysqli->query($sql);
    $mysqli->close();
}

function add_list_share_info($userId, $listId, $refUserId, $refListId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "insert into shopping_list_share_info values ('$userId', $listId, '$refUserId', $refListId, now());";
    $mysqli->query($sql);
    $mysqli->close();
}

function get_list_name($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $listName = "";
    $sql = "select listName from shopping_list_name where userId='$userId' and listId=$listId";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $listName = $row[0];
        }
        $result->free();
    }
    return $listName;
}

function update_list_name($userId, $listId, $listName) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $dataExists = false;
    $sql = "select listName from shopping_list_name where userId='$userId' and listId=$listId";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $dataExists =true;
        }
        $result->free();
    }
    if($dataExists) {
        $sql = "update shopping_list_name set listName='$listName', updateTime=Now() where userId='$userId' and listId=$listId";
    } else {
        $sql = "insert into shopping_list_name values ('$userId', $listId, '$listName', now())";
    }
    $mysqli->query($sql);
}

function delete_list_name($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $dataExists = false;
    $sql = "select listName from shopping_list_name where userId='$userId' and listId=$listId";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $dataExists =true;
        }
        $result->free();
    }
    if($dataExists) {
        $sql = "delete from shopping_list_name where userId='$userId' and listId=$listId";
        $mysqli->query($sql);
    }
}

function delete_share_info($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "delete from shopping_list_share_info where userId='$userId' and listId=$listId";
    $mysqli->query($sql);
}

function delete_shared_info($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "delete from shopping_list_share_info where refUserId='$userId' and refListId=$listId";
    $mysqli->query($sql);
}

function change_selected_list($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $listIdExists = false;
    $sql = "select listId from shopping_list_selected where userId='$userId'";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $listIdExists = true;
        }
        $result->free();
    }
    if($listIdExists) {
        $sql = "update shopping_list_selected set listId=$listId, updateTime=now() where userId='$userId'";
    } else {
        $sql = "insert into shopping_list_selected values ('$userId', $listId, now())";
    }
    $mysqli->query($sql);
    $mysqli->close();
}

function add_item($userId, $listId, $itemName) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $itemId = 0;
    $sql = "select max(itemId) from shopping_list where listId=$listId and userId='$userId'";
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $itemId = $row[0] + 1;
        }
        $result->free();
    }
    if($itemId > 50) {
        return "登録できるのは50件までです。";
    }
    $itemName = str_replace("\\", "\\\\", $itemName);
    $itemName = $mysqli->real_escape_string($itemName);
    $sql = "insert into shopping_list values ('$userId', $listId, $itemId, '$itemName', now());";
    $mysqli->query($sql);
    $mysqli->close();
    return "";
}

function list_items_for_user($userId) {
    $listId = get_list_id_selected($userId);
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        $userId = $refInfo["refUserId"];
        $listId = $refInfo["refListId"];
    }
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if($mysqli->connect_errno) {
        exit();
    }
    $ret = [];
    $sql = "select itemId, itemName from shopping_list where userId='$userId' and listId=$listId order by itemId";
    if($result = $mysqli->query($sql)) {
        if($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $ret[] = [
                    "itemId" => $row[0],
                    "itemName" => $row[1]
                ];
            }
        }
        $result->free();
    }
    $mysqli->close();
    return $ret;
}

function get_list_for_user($userId) {
    $items = list_items_for_user($userId);
    if($items === []) {
        return "リストは空です";
    }
    $ret = "";
    $isFirst = true;
    foreach($items as $item) {
        if($isFirst) {
            $isFirst = false;
        } else {
            $ret .= "\n";
        }
        $ret .= "#" . $item["itemId"] . " " . $item["itemName"];
    }
    return $ret;
}

function delete_items_by_number($userId, $listId, $number_list) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $ret = [];
    foreach($number_list as $itemId) {
        $sql = "select itemName from shopping_list where userId='$userId' and listId=$listId and itemId=$itemId";
        if ($result = $mysqli->query($sql)) {
            if ($result->num_rows > 0) {
                $row = $result->fetch_array(MYSQLI_NUM);
                $ret[] = "「" . $row[0] . "」を削除しました";
            } else {
                $ret[] = "#" . $itemId . " はありません";
            }
            $result->free();
        }
    }
    arsort($number_list);
    foreach($number_list as $itemId) {
        $sql = "delete from shopping_list where userId='$userId' and listId=$listId and itemId=$itemId";
        $mysqli->query($sql);
        $sql = "update shopping_list set itemId=itemId-1 where userId='$userId' and listId=$listId and itemId>$itemId";
        $mysqli->query($sql);
    }
    $mysqli->close();
    return $ret;
}

function delete_all_items($userId, $listId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "delete from shopping_list where userId='$userId' and listId=$listId";
    $mysqli->query($sql);
    $mysqli->close();
}
