<?php

/* Start session if not started */
function ensure_session(){
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
}

/* Redirect to another page */
function redirect($page){
    header("Location: $page");
    exit();
}

/* Check if request is POST */
function is_post(){
    return $_SERVER["REQUEST_METHOD"] === "POST";
}

/* Escape output for safety */
function h($string){
    return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}

/* Format price */
function format_price($price){
    return "RM " . number_format($price,2);
}

?>