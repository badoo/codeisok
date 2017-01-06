<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
setlocale(LC_CTYPE, "en_US.UTF-8");

$post = file_get_contents("php://input");
$post = json_decode($post);

if (!empty($post)) {
    //do something
}
