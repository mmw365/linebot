<?php
require_once "../util/util.php";
require_once "./dbhandler.php";
$notifications = [];

function process_message($userId, $message_text) {
    $command = strtolower($message_text);
    if($command == "リスト" || $command == "りすと" || $command == "list"){
        return process_list_message($userId);
    }
    if($command == "ヘルプ" || $command == "へるぷ" || $command == "？" || $command == "help" || $command == "?") {
        return process_help_message($userId);
    }
    if($command == "クリア" || $command == "くりあ" || $command == "クリアー" || $command == "clear"){
        return process_clear_message($userId);
    }
    if($command == "共有解除" || $command == "解除" || $command == "unshare"){
        return process_unshare_message($userId);
    }
    if($command == "共有" || $command == "シェア" || $command == "share"){
        return process_share_message($userId);
    }
    $command = str_replace("　", " ", $command);
    $split_cmd = explode(" ", $command);
    $command = to_narrow_number($split_cmd[0]);
    if ($command == "リスト" || $command == "りすと" || $command == "list") {
        if(count($split_cmd) > 1) {
            $listId = to_narrow_number($split_cmd[1]);
            if(is_numeric($listId) && $listId >= 1 && $listId <= 5) {
                $listId = (int)$listId;
                $listName = "";
                if(count($split_cmd) > 2) {
                    $listName = $split_cmd[2];
                }
                return process_select_list_message($userId, $listId, $listName);
            }
        }
    }
    for ($i = 1; $i <= 5; $i++) {
        if($command == "リスト" . $i || $command == "りすと" . $i || $command == "list" . $i){
            $listName = "";
            if(count($split_cmd) > 1) {
                $listName = $split_cmd[1];
            }
            return process_select_list_message($userId, $i, $listName);
        } 
    }

    $number_list = parse_number_list($message_text);
    if(count($number_list) > 0) {
        return process_delete_message($userId, $number_list);
    }
    
    if(check_pass_code($message_text, 12)) {
        return process_pass_code($userId, $message_text);
    }

    return process_add_message($userId, $message_text);
}

function process_add_message($userId, $message_text) {
    $listId = get_list_id_selected($userId);
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        $retMsg = add_item($refInfo["refUserId"], $refInfo["refListId"], $message_text);
    } else {
        $retMsg = add_item($userId, $listId, $message_text);
    }
    if($retMsg == "") {
        if(count($refInfo) > 0) {
            set_notification("UPDATE", $userId, $listId);
        } else {
            $sharedInfo = get_list_shared_info($userId, $listId);
            if(count($sharedInfo) > 0) {
                set_notification("UPDATE", $userId, $listId);
            }
        }
        return ["「${message_text}」を追加しました。"];
    } else {
        return [$retMsg];
    }
}

function process_pass_code($userId, $message_text) {
    $listId = get_list_id_selected($userId);
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        return ["共有リストを使用（参照）中です\n解除するか他のリストを選択してください"];
    }
    $sharedInfo = get_list_shared_info($userId, $listId);
    if(count($sharedInfo) > 0) {
        return ["リストが共有（公開）されているため設定できません\n解除するか他のリストを選択してください"];
    }
    $shareCodeInfo = get_list_share_code($userId, $message_text);
    if(count($shareCodeInfo) == 0) {
        return ["有効でないコードです"];
    }
    $refUserId = $shareCodeInfo["userId"];
    $refListId = $shareCodeInfo["listId"];
    add_list_share_info($userId, $listId, $refUserId, $refListId);
    return ["共有を設定しました", get_list_for_user($userId)];
}

function process_list_message($userId) {
    return [get_list_for_user($userId)];
}

function process_select_list_message($userId, $listId, $listName) {
    if($listName != "") {
        update_list_name($userId, $listId, $listName);
    } else {
        $listName = get_list_name($userId, $listId);
    }
    change_selected_list($userId, $listId);
    $ret = ["「リスト${listId}" . ($listName == "" ? "" : "（${listName}）") . "」に切替えました"];
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        $ret[0] .= "\n※リスト${listId}は共有（参照中）のリストです";
    }
    $sharedInfo = get_list_shared_info($userId, $listId);
    if(count($sharedInfo) > 0) {
        $ret[0] .= "\n※リスト${listId}は" . count($sharedInfo) . "名に共有（公開）されています";
    }
    $ret[] = get_list_for_user($userId);
    return $ret;
}

function process_delete_message($userId, $number_list) {
    $listId = get_list_id_selected($userId);
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        $item_detail = delete_items_by_number($refInfo["refUserId"], $refInfo["refListId"], $number_list);
    } else {
        $item_detail = delete_items_by_number($userId, $listId, $number_list);
    }        
    
    $msg = "";
    $isFirst = true;
    foreach($item_detail as $item) {
        if($isFirst) {
            $isFirst = false;
        } else {
            $msg .= "\n";
        }
        $msg .= $item;
    }
    if($msg != "") {
        if(count($refInfo) > 0) {
            set_notification("UPDATE", $userId, $listId);
        } else {
            $sharedInfo = get_list_shared_info($userId, $listId);
            if(count($sharedInfo) > 0) {
                set_notification("UPDATE", $userId, $listId);
            }
        }
        return [$msg, get_list_for_user($userId)];
    }
    return [""];
}

function process_clear_message($userId) {
    $listId = get_list_id_selected($userId);
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        return ["共有リストは変更できません"];
    }
    delete_all_items($userId, $listId);
    delete_list_name($userId, $listId);
    $sharedInfo = get_list_shared_info($userId, $listId);
    if(count($sharedInfo) > 0) {
        set_notification("UPDATE", $userId, $listId);
    }
    return ["リストを空にしました"];
}

function process_unshare_message($userId) {
    $listId = get_list_id_selected($userId);
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        delete_share_info($userId, $listId);
        set_notification("UNSHARE-SUB", $refInfo["refUserId"], $refInfo["refListId"]);
        return ["共有を解除しました"];
    }
    $sharedInfo = get_list_shared_info($userId, $listId);
    if(count($sharedInfo) > 0) {
        delete_shared_info($userId, $listId);
        foreach($sharedInfo as $info) {
            set_notification("UNSHARE-PUB", $info["userId"], $info["listId"]);
        }
        return ["共有を解除しました"];
    }
    return ["共有リストではありません"];
}

function process_share_message($userId) {
    $listId = get_list_id_selected($userId);
    $refInfo = get_list_referencing_info($userId, $listId);
    if(count($refInfo) > 0) {
        return ["共有リストは共有できません"];
    }
    $code = create_pass_code(12);
    add_list_shared_code($userId, $listId, $code);
    return ["リストを共有したい友達に、下記のコードを渡してください", $code];
}

function process_help_message($split_msg) {
    return ["メッセージの送信でリストを作成します。コマンド以外は全てリストに追加されます。\n"
        . "コマンド一覧：\n"
        . "「リスト(list)」リストを表示します。\n"
        . "「リスト番号」リストから指定番号のアイテムを削除します。\n"
        . "（※コンマ／スペース区切りで複数指定できます。"
        . "※削除されると番号がふりなおされます。）\n"
        . "「クリア(clear)」リストを全削除します。\n"
        . "「リスト１〜５」リストを切替えます。\n"
        . "（※「リスト１　＜リスト名＞」でリスト名の設定ができます。）"];
}

function set_notification($type, $userId, $listId) {
    global $notifications;
    $notifications[] = [
        "type" => $type,
        "userId" => $userId,
        "listId" => $listId
    ];
}

function get_notifications() {
    global $notifications;
    return $notifications;
}

function parse_number_list($input) {
    $input = str_replace("　", " ", $input);
    $input = str_replace("、", " ", $input);
    $input = str_replace(",", " ", $input);
    $len = -1;
    while($len != strlen($input)) {
        $input = str_replace("  ", " ", $input);
        $len = strlen($input);
    }
    $input = to_narrow_number($input);
    $split_input = explode(" ", $input);
    foreach($split_input as $val) {
        if(!is_numeric($val)) {
            return [];
        }
    }
    $ret = [];
    foreach($split_input as $val) {
        if(is_numeric($val)) {
            $ret[] = intval($val);
        }
    }
    return array_unique($ret);
}