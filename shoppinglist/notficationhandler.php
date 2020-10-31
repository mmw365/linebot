<?php
require_once "./dbhandler.php";

function handle_notification($notification) {
    $type = $notification["type"];
    $userId = $notification["userId"];
    $listId = $notification["listId"];

    if($type == "UPDATE") {
        $ret = [];
        $listContent = "リストが更新されました\n" . get_list_for_user($userId);
        $sharedInfo = get_list_shared_info($userId, $listId);
        foreach($sharedInfo as $info) {
            $tmpUserId = $info["userId"];
            $tmpListId = $info["listId"];
            $selectedListId = get_list_id_selected($tmpUserId);
            if($tmpListId == $selectedListId) {
                $ret[] = [
                    "userId" => $tmpUserId,
                    "msg" => $listContent
                ];
            }
        }
        return $ret;
    } elseif($type == "UNSHARE-SUB") {
        $ret = [];
        $ret[] = [
            "userId" => $userId,
            "msg" => "リスト${listId}（公開中）の共有が解除されました"
            ];
        return $ret;
    } elseif($type == "UNSHARE-PUB") {
        $ret = [];
        $ret[] = [
            "userId" => $userId,
            "msg" => "リスト${listId}（参照中）の共有が解除されました"
            ];
        return $ret;
    }
    return [];
}