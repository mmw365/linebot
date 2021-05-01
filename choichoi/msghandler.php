<?php
require_once "../util/util.php";
require_once "./dbhandler.php";

function process_message($userId, $message_text) {
    $message_text = to_narrow_number($message_text);
    if($message_text == "あ") {
        $message_text = "1";
    } else if($message_text == "い") {
        $message_text = "2";
    } else if($message_text == "う") {
        $message_text = "3";
    } else if($message_text == "え") {
        $message_text = "4";
    } else if($message_text == "お") {
        $message_text = "5";
    } else if($message_text == "q" || $message_text == "0") {
        update_session($userId, "INIT", 0, 0, 0, 0, 0);
        $msg = "クイズを終了してカテゴリ選択に戻ります。\n\n";
        $categories = get_categories();
        return $msg . create_catselect_msg($categories);
    }
    
    $session = get_session($userId);
    if($session === []) {
        update_session($userId, "INIT", 0, 0, 0, 0, 0);
        $categories = get_categories();
        return create_catselect_msg($categories);
    }
    
    if($session["status"]  == "INIT") {
        return handle_init_status($userId, $message_text);
    }
    
    if($session["status"]  == "CATSELECT") {
        return handle_catselect_status($userId, $message_text, $session);
    }

    if($session["status"]  == "SUBCATSELECT") {
        return handle_subcatselect_status($userId, $message_text, $session);
    }

    if($session["status"]  == "QUIZSELECTED") {
        return handle_quizselected_status($userId, $message_text, $session);
    }

    if($session["status"]  == "QUIZEND") {
        return handle_quizend_status($userId, $message_text, $session);
    }
}

function handle_init_status($userId, $message_text) {
    if(!is_numeric($message_text)) {
        return "カテゴリーを選択してください";
    }
    $categoryId = (int)$message_text;
    $category = get_category($categoryId);
    if($category === []) {
        return "カテゴリーを選択してください";
    }
    update_session($userId, "CATSELECT", $categoryId, 0, 0, 0, 0);
    $subcategories = get_subcategories($categoryId);
    if(count($subcategories) == 1) {
        $subcategoryId =$subcategories[0]["subcategoryId"];
        update_session($userId, "SUBCATSELECT", $categoryId, $subcategoryId, 0, 0, 0);
        $quizes = get_quizes($subcategoryId);
        return create_quizselect_msg($quizes);
    }
    return create_subcatselect_msg($subcategories);
}

function handle_catselect_status($userId, $message_text, $session) {
    if(!is_numeric($message_text)) {
        return "サブカテゴリーを選択してください";
    }
    $categoryId = $session["categoryId"];
    $categoryOrder = (int)$message_text;
    $subcategory = get_subcategory($categoryId, $categoryOrder);
    if($subcategory === []) {
        return "サブカテゴリーを選択してください";
    }
    $subcategoryId =$subcategory["subcategoryId"];
    update_session($userId, "SUBCATSELECT", $categoryId, $subcategoryId, 0, 0, 0);
    $quizes = get_quizes($subcategoryId);
    return create_quizselect_msg($quizes);
}

function handle_subcatselect_status($userId, $message_text, $session) {
    if(!is_numeric($message_text)) {
        return "クイズ番号を選択してください";
    }
    $categoryId = $session["categoryId"];
    $subcategoryId = $session["subcategoryId"];
    $quizOrder = (int)$message_text;
    $quiz = get_quiz($subcategoryId, $quizOrder);
    if($quiz === []) {
        return "クイズ番号を選択してください";
    }
    $quizId = $quiz["quizId"];
    $quizNum = 1;
    update_session($userId, "QUIZSELECTED", $categoryId, $subcategoryId, $quizId, $quizNum, 0);
    $question = get_question($quizId, $quizNum);
    return create_question_msg($question);
}

function handle_quizselected_status($userId, $message_text, $session) {
    if(!is_numeric($message_text)) {
        return "回答番号を選択してください";
    }
    $categoryId = $session["categoryId"];
    $subcategoryId = $session["subcategoryId"];
    $quizId = $session["quizId"];
    $quizNum = $session["quizNum"];
    $point = $session["point"];
    $ans = (int)$message_text;
    if($ans < 1 || $ans > 4) {
        return "回答番号を選択してください";
    }
    $answer = get_answer($quizId, $quizNum);
    if($answer["answer"] == $ans) {
        $msg = "正解です。\n";
        $point += 1;
    } else {
        $msg = "不正解。正解は" . $answer["answer"] . ":「" . $answer["answerChoice"] . "」です。\n";
    }
    if($answer["explanation"] != '') {
        $msg .= $answer["explanation"] . "\n";
    }
    $msg .= "\n";
    
    $quizNum += 1;
    $question = get_question($quizId, $quizNum);
    if($question == []) {
        update_session($userId, "QUIZEND", $categoryId, $subcategoryId, $quizId, $quizNum, $point);
        $msg .= "これでクイズは終わりです。\n" . ($quizNum - 1) . "問中${point}問正解でした。\n\n";
        $msg .= "1. もう1度同じクイズ\n2. 他のクイズ\n3. カテゴリ選択に戻る";
        return $msg;
    }
    
    update_session($userId, "QUIZSELECTED", $categoryId, $subcategoryId, $quizId, $quizNum, $point);
    $msg .= create_question_msg($question);
    return $msg;
}

function handle_quizend_status($userId, $message_text, $session) {
    if(!is_numeric($message_text)) {
        return "番号を選択してください";
    }
    $categoryId = $session["categoryId"];
    $subcategoryId = $session["subcategoryId"];
    $quizId = $session["quizId"];
    $quizNum = $session["quizNum"];
    $action = (int)$message_text;
    if($action < 1 || $action > 3) {
        return "番号を選択してください";
    }
    if($action == 1) {
        $quizNum = 1;
        $question = get_question($quizId, $quizNum);
        update_session($userId, "QUIZSELECTED", $categoryId, $subcategoryId, $quizId, $quizNum, 0);
        return create_question_msg($question);
    }
    if($action == 2) {
        update_session($userId, "SUBCATSELECT", $categoryId, $subcategoryId, 0, 0, 0);
        $quizes = get_quizes($categoryId, $subcategoryId);
        return create_quizselect_msg($quizes);
    }
    if($action == 3) {
        update_session($userId, "INIT", 0, 0, 0, 0, 0);
        $categories = get_categories();
        return create_catselect_msg($categories);
    }
}

function create_catselect_msg($categories) {
    $msg = "カテゴリーを選択してください";
    foreach($categories as $category) {
        $msg .= "\n" . $category["categoryId"] . ". " . $category["categoryName"];
    }
    return $msg;
}

function create_subcatselect_msg($subcategories) {
    $msg = "サブカテゴリーを選択してください";
    foreach($subcategories as $subcategory) {
        $msg .= "\n" . $subcategory["categoryOrder"] . ". " . $subcategory["subcategoryName"];
    }
    return $msg;}

function create_quizselect_msg($quizes) {
    $msg = "クイズ番号を選択してください";
    foreach($quizes as $quiz) {
        $msg .= "\n" . $quiz["quizOrder"] . ". " . $quiz["quizName"];
    }
    return $msg;
}

function create_question_msg($question) {
    $msg = "【問題】" . $question["question"];
    $choiseChar = [1 => "あ", 2 => "い", 3 => "う", 4 => "え"];
    foreach (array(1, 2, 3, 4) as $i) {
        if($question["choice" . $i] != "") {
            $msg .= "\n$i($choiseChar[$i]). " . $question["choice" . $i];
        }
    }
    return $msg;
}
    