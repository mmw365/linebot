<?php
require_once "../util/line-api.php";
require_once "./dbhandler.php";
require_once "./msghandler.php";

$props = parse_ini_file('../ini/linebot.ini');
init_token($props['cleaningreminderToken']);
init_db_params($props['servername'], $props['dbuser'], $props['dbpass'], $props['dbname']);

$users = get_all_users();
foreach($users as $userId) {
    $push_text = "タスクリストをお送りします。\n";
    $push_text .= process_list_message($userId);
    send_push_message($userId, $push_text);
}
