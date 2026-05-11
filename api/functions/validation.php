<?php
function validateFullName($name) {
    return !empty($name) && strlen($name) >= 2 && strlen($name) <= 100;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function validatePhoneNumber($phone) {
    return preg_match('/^09[0-9]{8}$/', $phone);
}
?>