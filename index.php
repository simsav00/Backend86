<?php

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
  exit(0);
}

require_once "config/bootstrap.php";

// if($_SERVER["REQUEST_METHOD"] !== "POST")
//     respond(1, 405, "Method not allowed.");

if(!isset($_GET["t"]))
    respond(1, 400, "Missing type of request.");
    

$type = strtolower(trim($_GET["t"]));

$routes = [
    "login" =>      fn() => $authController->login(),
    "register" =>   fn() => $authController->register(),
    "me" =>         fn() => $authController->me(),
    "logout" =>     fn() => $authController->logout(),

    "posts" =>      fn() => $postController->getAllPosts(),
    "post" =>       fn() => $postController->getPost(),
    "newpost" =>    fn() => $postController->newPost(),
    "editpost" =>   fn() => $postController->editPost(),
    "deletepost" => fn() => $postController->deletePost(),
];

if(array_key_exists($type, $routes)) {
    $routes[$type]();
}
else{
    respond(0, 404, "Unknown page.");
}