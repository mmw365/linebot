<?php
require_once "../util/util.php";
require_once "./dbhandler.php";

function process_message($userId, $message_text) {
    $message_text = str_replace("　", " ", $message_text);
    $split_msg = explode(" ", $message_text);
    $command = strtolower($split_msg[0]);

    if($command == "リスト" || $command == "list"){
        return process_list_message($userId);
    }
    
    if($command == "追加" || $command == "add") {
        return process_add_message($userId, $split_msg);
    }

    if($command == "削除" || $command == "delete") {
        return process_delete_message($userId, $split_msg);
    }

    if($command == "完了" || $command == "done") {
        return process_done_message($userId, $split_msg);
    }
    
    if($command == "詳細" || $command == "detail") {
        return process_detail_message($userId, $split_msg);
    }

    if($command == "修正" || $command == "update") {
        return process_update_message($userId, $split_msg);
    }
    
    if($command == "ヘルプ" || $command == "help") {
        return process_help_message($split_msg);
    }
    
    return "有効なコマンド：リスト｜詳細｜追加｜完了｜修正｜削除｜ヘルプ（ヘルプ 【コマンド名】でコマンドの説明）";
}

function process_list_message($userId) {
    $tasks = get_tasks_by_user($userId);
    if($tasks === []) {
        return "タスクはありません";
    }
    $ret = "";
    foreach($tasks as $task) {
        if($ret != "") {
            $ret .= "\n";
        }
        $ret .= "#" . $task["taskId"] . " " . $task["taskName"];
        $days = get_days_to_date($task["nextDate"]);
        $ret .= "（" . get_days_description($days) . "）";
    }
    return $ret;
}

function process_add_message($userId, $split_msg) {
    if(count($split_msg) < 3) {
        return "タスク名、期間が未指定です。タスクが追加できませんでした";
    }
    $taskName = $split_msg[1];
    $term = parse_term($split_msg[2]);
    if($term == "err") {
        return "期間を指定してください。タスクが追加できませんでした";
    }
    $nextDate = "";
    if(count($split_msg) > 3) {
        if(($nextTime = strtotime($split_msg[3])) != null) {
            $nextDate = date("Y-m-d", $nextTime);
        }
    }
    if($nextDate == "") {
        $today = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
        $nextDate = get_next_date($term, $today);
    }
    add_task($userId, $taskName, $term, $nextDate);
    return "新しいタスク「${taskName}」を追加しました。\n次の期限は $nextDate です";
}

function process_delete_message($userId, $split_msg) {
    if(count($split_msg) < 2) {
        return "タスクIDが未指定です。タスクが削除できませんでした";
    }
    $taskId = 1 * to_narrow_number($split_msg[1]);
    if($taskId == 0) {
        return "タスクIDが不正です。タスクが削除できませんでした";
    }
    $task_detail = get_task_detail($userId, $taskId);
    if($task_detail === []) {
        return "タスク#${taskId}はありません。";
    }
    $taskName = $task_detail["taskName"];
    delete_task($userId, $taskId);
    return "タスク#${taskId}「${taskName}」を削除しました。";
}

function process_done_message($userId, $split_msg) {
    if(count($split_msg) < 2) {
        return "タスクIDが未指定です。タスクを完了できませんでした";
    }
    $taskId = 1 * to_narrow_number($split_msg[1]);
    if($taskId == 0) {
        return "タスクIDが不正です。タスクを完了できませんでした";
    }
    $task_detail = get_task_detail($userId, $taskId);
    if($task_detail === []) {
        return "タスク#${taskId}はありません。";
    }
    $taskName = $task_detail["taskName"];
    $schedule = $task_detail["schedule"];
    $doneDateTime = mktime(0, 0, 0, date("n"), date("j"), date("Y")); // default to today
    if(count($split_msg) > 2) {
        if($split_msg[2] == "昨日" || $split_msg[2] == "yesterday") {
            $doneDateTime = strtotime("-1 day", $doneDateTime);
        } elseif($split_msg[2] == "一昨日" || $split_msg[2] == "daybefore") {
            $doneDateTime = strtotime("-2 day", $doneDateTime);
        } elseif(($doneTime = strtotime($split_msg[2])) != null) {
            $doneDateTime = $doneTime;
        }
    }
    $nextDate = get_next_date($schedule, $doneDateTime);
    $lastDate = date("Y-m-d", $doneDateTime);
    
    update_task_dates($userId, $taskId, $lastDate, $nextDate);
    return "タスク#${taskId}「${taskName}」を完了しました。\n次の期限は $nextDate です";
}

function process_detail_message($userId, $split_msg) {
    if(count($split_msg) < 2) {
        return "タスクIDが未指定です。タスクが削除できませんでした";
    }
    $taskId = 1 * to_narrow_number($split_msg[1]);
    if($taskId == 0) {
        return "タスクIDが不正です。タスクが削除できませんでした";
    }
    $task_detail = get_task_detail($userId, $taskId);
    if($task_detail === []) {
        return "タスク#${taskId}はありません。";
    }
    
    $taskName = $task_detail["taskName"];
    $term = $task_detail["schedule"];
    $term = get_term_text($term);
    $lastDate = $task_detail["lastDate"];
    $nextDate = $task_detail["nextDate"];
    
    $ret = "タスク番号: $taskId\n";
    $ret .= "タスク名: $taskName\n";
    $ret .= "スケジュール: $term\n";
    $ret .= "前回実行日: $lastDate\n";
    $ret .= "次回実行日: $nextDate\n";
    $days = get_days_to_date($nextDate);
    return $ret . "（" . get_days_description($days) . "）";
}

function process_update_message($userId, $split_msg) {
    if(count($split_msg) < 4) {
        return "更新に必要な情報がありません。タスクが更新できませんでした";
    }
    
    $taskId = 1 * to_narrow_number($split_msg[1]);
    if($taskId == 0) {
        return "タスクIDが不正です。タスクが更新できませんでした";
    }
    $task_detail = get_task_detail($userId, $taskId);
    if($task_detail === []) {
        return "タスク#${taskId}はありません。";
    }
    $taskName = $task_detail["taskName"];
    $schedule = $task_detail["schedule"];
    $lastDate = $task_detail["lastDate"];
    $nextDate = $task_detail["nextDate"];
    
    $type = $split_msg[2];
    if($type == "名前" || $type == "name") {
        $taskName = $split_msg[3];
    } elseif ($type == "期間" || $type == "term" ) {
        $schedule = parse_term($split_msg[3]);
        if($schedule == "err") {
            return "期間を指定してください。タスクが更新できませんでした";
        }
        $nextDate = get_next_date($schedule, strtotime($lastDate));
    } else {
        return "名前か期間を選択してください。タスクが更新できませんでした";
    }
    update_task($userId, $taskId, $taskName, $schedule, $nextDate);
    return "タスク「${taskName}」を修正しました。\n次の期限は $nextDate です";
}

function process_help_message($split_msg) {
    if(count($split_msg) < 2) {
        return "有効なコマンド\n"
            . "リスト：タスクの一覧を表示する\n"
            . "詳細：タスクの詳細を表示する\n"
            . "追加：タスクを追加する\n"
            . "完了：タスクを完了する\n"
            . "修正：タスクを修正する\n"
            . "削除：タスクを削除する\n"
            . "ヘルプ 【コマンド名】：コマンドの説明";
    }
    
    $command = strtolower($split_msg[1]);
    if($command == "リスト" || $command == "list"){
        return "リスト(list)コマンド：タスクの一覧を表示する\n"
            . "使用法「リスト」";
    }
    
    if($command == "追加" || $command == "add") {
        return "追加(add)コマンド：タスクを追加する\n"
            . "使用法1「追加 【タスク名】 【期間】」\n"
            . "使用法2「追加 【タスク名】 【期間】 【初回実行日】」\n"
            . "【タスク名】：任意のタスク名\n"
            . "【期間】：次回実行までの期間（10日、毎週、2月、2d、3w、1m　等）\n"
            . "【初回実行日】：指定しない場合は本日から計算（20200801 等）\n";
    }
    
    if($command == "削除" || $command == "delete") {
        return "削除(delete)コマンド：タスクを削除する\n"
            . "使用例「削除 【タスク番号」\n"
            . "タスク番号：タスク番号はリストコマンドで確認可能";
    }
    
    if($command == "完了" || $command == "done") {
        return "完了(done)コマンド：タスクを完了する\n"
            . "使用法1「完了 【タスク番号】」\n"
            . "使用法2「完了 【タスク番号】 【完了日】」\n"
            . "【タスク番号】：タスク番号はリストコマンドで確認可能\n"
            . "【完了日】：指定しない場合は本日（20200801 等）\n";
    }
    
    if($command == "詳細" || $command == "detail") {
        return "詳細(detail)コマンド：タスクの詳細を表示する\n"
            . "使用法「詳細 【タスク番号】』\n"
            . "【タスク番号】：タスク番号はリストコマンドで確認可能";
    }
    
    if($command == "修正" || $command == "update") {
        return "修正(update)コマンド：タスクを修正する\n"
            . "使用法1「追加 【タスク番号】 名前 【新しい名前】」\n"
            . "使用法2「追加 【タスク番号】 期間 【新しい期間】」\n"
            . "【新しい名前】：任意のタスク名\n"
            . "【新しい期間】：次回実行までの期間（１０日、毎週、２月、2d、3w、1m　等）\n";
    }
    
    if($command == "ヘルプ" || $command == "help") {
        return "ヘルプ(help)コマンド：コマンドの説明\n"
            . "使用法1「ヘルプ」\n"
            . "使用法2「ヘルプ 【コマンド名】」\n"
            . "【コマンド名】：コマンド名（リスト｜詳細｜追加｜完了｜修正｜削除｜ヘルプ）";
    }
    
    return "指定したコマンドは存在しません";
}

function parse_term($term) {
    $term = strtolower($term);
    if($term == "毎日" || $term == "daily" || $term == "everyday") {
        return "1d";
    }
    if($term == "毎週" || $term == "weekly" || $term == "everyweek") {
        return "1w";
    }
    if($term == "毎月" || $term == "monthly" || $term == "everymonth") {
        return "1m";
    }
    $term = to_narrow_number($term);
    $num = 1 * $term;
    if($num == 0) {
        return "err";
    }
    if(ends_with($term, "日") || ends_with($term, "d") || ends_with($term, "day") || ends_with($term, "days")) {
        return $num . "d";
    }
    if(ends_with($term, "週") || ends_with($term, "w") || ends_with($term, "week")|| ends_with($term, "weeks")) {
        return $num . "w";
    }
    if(ends_with($term, "月") || ends_with($term, "m") || ends_with($term, "month")|| ends_with($term, "months")) {
        return $num . "m";
    }
    return "err";
}

function get_next_date($term, $lastDateTime) {
    $term = "+" . $term;
    $term = str_replace("d", " day", $term);
    $term = str_replace("w", " week", $term);
    $term = str_replace("m", " month", $term);
    return date("Y-m-d", strtotime($term, $lastDateTime));
}

function get_term_text($term) {
    $term = str_replace("d", "日", $term);
    $term = str_replace("w", "週間", $term);
    $term = str_replace("m", "ヶ月", $term);
    return $term . "ごと";
}

function get_days_to_date($date) {
    return (strtotime($date) - mktime(0, 0, 0, date("n"), date("j"), date("Y"))) / 86400;
}

function get_days_description($days) {
    if($days < 0) {
        $days = -1 * $days;
        return "超過${days}日";
    } 
    if($days > 0) {
        return "あと${days}日";
    }
    return "本日";
}