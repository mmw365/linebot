<?php
function to_narrow_number($str) {
    $str = str_replace("０", "0", $str);
    $str = str_replace("１", "1", $str);
    $str = str_replace("２", "2", $str);
    $str = str_replace("３", "3", $str);
    $str = str_replace("４", "4", $str);
    $str = str_replace("５", "5", $str);
    $str = str_replace("６", "6", $str);
    $str = str_replace("７", "7", $str);
    $str = str_replace("８", "8", $str);
    $str = str_replace("９", "9", $str);
    return $str;
}

function starts_with($str, $part) {
    return (strlen($str) > strlen($part)) ? (substr($str, 0, strlen($part)) == $part) : false;
}
function ends_with($str, $part) {
    return (strlen($str) > strlen($part)) ? (substr($str, -strlen($part)) == $part) : false;
}

function check_pass_code($code, $len) {
    if(strlen($code) != $len) {
        return 0;
    }
    return preg_match('/[A-Z0-9]{' . $len . '}/', $code);
}

function create_pass_code($len) {
    $code = "";
    for($i = 0; $i < $len; $i++) {
        $r = random_int(0, 35);
        if($r < 10) {
            $code .= $r;
        } else {
            $code .= chr($r + 55);
        }
    }
    return $code;
}
