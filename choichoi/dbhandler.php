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

function get_session($userId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select status, categoryId, subcategoryId, quizId, quizNum, point from quizbot_session where userId='$userId'";
    $mysqli->query($sql);

    $status = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $status = [
                "status" => $row[0],
                "categoryId" => $row[1],
                "subcategoryId" => $row[2],
                "quizId" => $row[3],
                "quizNum" => $row[4],
                "point" => $row[5]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $status;
}

function update_session($userId, $status, $categoryId, $subcategoryId, $quizId, $quizNum, $point) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    
    if($status == "INIT") {
        $sql = "delete from quizbot_session where userId='$userId';";
        $mysqli->query($sql);
        $sql = "insert into quizbot_session values ('$userId', '$status', $categoryId, $subcategoryId, $quizId, $quizNum, $point, now());";
        $mysqli->query($sql);
    } else {
        $sql = "update quizbot_session set status='$status', categoryId=$categoryId, subcategoryId=$subcategoryId, quizId=$quizId, quizNum=$quizNum, point=$point, updateTime=now() where userId='$userId';";
        $mysqli->query($sql);
    }
    $mysqli->close();
}

function delete_status($userId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    
    $sql = "delete from quizbot_session where userId='$userId';";
    $mysqli->query($sql);
    $mysqli->close();
}


function get_categories() {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select categoryId, categoryName from quizbot_category order by categoryId";
    $mysqli->query($sql);
    
    $categories = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $categories[] = [
                    "categoryId" => $row[0],
                    "categoryName" => $row[1]
                ];
                
            }
        }
        $result->free();
    }
    $mysqli->close();
    return $categories;
}

function get_category($categoryId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select categoryId, categoryName from quizbot_category where categoryId=$categoryId";
    $mysqli->query($sql);
    
    $category = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $category = [
                "categoryId" => $row[0],
                "categoryName" => $row[1]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $category;
}

function get_subcategories($categoryId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select categoryId, subcategoryId, subcategoryName from quizbot_sub_category where categoryId=$categoryId order by subcategoryId";
    $mysqli->query($sql);
    
    $subcategories = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $subcategories[] = [
                    "categoryId" => $row[0],
                    "subcategoryId" => $row[1],
                    "subcategoryName" => $row[2]
                ];
            }
        }
        $result->free();
    }
    $mysqli->close();
    return $subcategories;
}

function get_subcategory($categoryId, $subcategoryId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select categoryId, subcategoryId, subcategoryName from quizbot_sub_category where categoryId=$categoryId and subcategoryId=$subcategoryId";
    $mysqli->query($sql);
    
    $subcategory = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $subcategory = [
                "categoryId" => $row[0],
                "subcategoryId" => $row[1],
                "subcategoryName" => $row[2]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $subcategory;
}

function get_quizes($subcategoryId) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select quizOrder, quizName from quizbot_quiz where subcategoryId=$subcategoryId order by quizOrder";
    $mysqli->query($sql);
    
    $quizes = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $quizes[] = [
                    "quizOrder" => $row[0],
                    "quizName" => $row[1]
                ];
                
            }
        }
        $result->free();
    }
    $mysqli->close();
    return $quizes;
}

function get_quiz($subcategoryId, $quizOrder) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select quizId, quizName from quizbot_quiz where subcategoryId=$subcategoryId and quizOrder=$quizOrder";
    $mysqli->query($sql);
    
    $quiz = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $quiz = [
                "quizId" => $row[0],
                "quizName" => $row[1]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $quiz;
}

function get_question($quizId, $quizNum) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select question, choice1, choice2, choice3, choice4 from quizbot_question where quizId=$quizId and quizNum=$quizNum";
    $mysqli->query($sql);
    
    $question = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $question = [
                "question" => $row[0],
                "choice1" => $row[1],
                "choice2" => $row[2],
                "choice3" => $row[3],
                "choice4" => $row[4]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $question;
}

function get_answer($quizId, $quizNum) {
    global $servername, $dbuser, $dbpass, $dbname;
    $mysqli = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_errno) {
        exit();
    }
    $sql = "select answer, explanation from quizbot_question where quizId=$quizId and quizNum=$quizNum";
    $mysqli->query($sql);
    
    $question = [];
    if ($result = $mysqli->query($sql)) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $question = [
                "answer" => $row[0],
                "explanation" => $row[1]
            ];
        }
        $result->free();
    }
    $mysqli->close();
    return $question;
}