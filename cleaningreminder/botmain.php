<?php
require_once "../util/line-api.php";
require_once "./dbhandler.php";
require_once "./msghandler.php";

$props = parse_ini_file('../ini/linebot.ini');
init_token($props['cleaningreminderToken']);
init_db_params($props['servername'], $props['dbuser'], $props['dbpass'], $props['dbname']);

$inputJsonMsg = file_get_contents('php://input');
$inputObj = json_decode($inputJsonMsg);
$userId = $inputObj->{"events"}[0]->{"source"}->{"userId"};
$type = $inputObj->{"events"}[0]->{"type"};
save_message_log(1, $userId, $type, $inputJsonMsg);

if($type != "message"){
    exit;
}

$replyToken = $inputObj->{"events"}[0]->{"replyToken"};
$messagType= $inputObj->{"events"}[0]->{"message"}->{"type"};

if($messagType != "text"){
    exit;
}

$text = $inputObj->{"events"}[0]->{"message"}->{"text"};
$responseText = process_message($userId, $text);
send_reply_message($replyToken, $responseText);
