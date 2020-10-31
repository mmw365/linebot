<?php
$accessToken = "";

function init_token($appAccessToken) {
    global $accessToken;
    $accessToken = $appAccessToken;
}

function send_reply_message($replyToken, $responseText) {
    global $accessToken;
    $responseMessage = [
        "type" => "text",
        "text" => $responseText
    ];
    $responseData = [
        "replyToken" => $replyToken,
        "messages" => [$responseMessage]
    ];
    
    $ch = curl_init("https://api.line.me/v2/bot/message/reply");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($responseData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $accessToken
    ));
    curl_exec($ch);
    curl_close($ch);
}

function send_reply_messages($replyToken, $responseTexts) {
    global $accessToken;
    $responseMessages = [];
    foreach($responseTexts as $responseText) {
        $responseMessages[] = [
            "type" => "text",
            "text" => $responseText
        ];
    }
    
    $responseData = [
        "replyToken" => $replyToken,
        "messages" => $responseMessages
    ];
    
    $ch = curl_init("https://api.line.me/v2/bot/message/reply");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($responseData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $accessToken
    ));
    curl_exec($ch);
    curl_close($ch);
}

function send_push_message($userId, $pushText)  {
    global $accessToken;
    $pushMessage = [
        "type" => "text",
        "text" => $pushText
    ];
    $pushData = [
        "to" => $userId,
        "messages" => [$pushMessage]
    ];
    
    $ch = curl_init("https://api.line.me/v2/bot/message/push");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pushData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $accessToken
    ));
    curl_exec($ch);
    curl_close($ch);
}