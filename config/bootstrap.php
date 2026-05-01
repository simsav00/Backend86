<?php
declare(strict_types=1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once "./functions.php";

require_once AUTH_MODEL;
require_once COMMENT_MODEL;
require_once POST_MODEL;
require_once USER_MODEL;

require_once AUTH_CONTROLLER;
require_once COMMENT_CONTROLLER;
require_once POST_CONTROLLER;
require_once USER_CONTROLLER;

$db = new Database("localhost", "phpautogallery", "root", "12345678");
$conn = $db->connect();

$authModel = new AuthModel($conn);