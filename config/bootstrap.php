<?php
declare(strict_types=1);

require_once "libraries/envLoader.php";
loadEnv("secrets/.env");

require_once "config/functions.php";
require_once "config/settings.php";

ini_set("display_errors", SHOW_PHP_ERRORS ? 1 : 0);
ini_set("display_startup_errors",SHOW_PHP_ERRORS ? 1 : 0);
error_reporting(SHOW_PHP_ERRORS ? E_ALL : 0);

require_once "config/headers.php";

require_once HTTP_EXCEPTION;
require_once DB_CONN;

require_once AUTH_MODEL;
require_once COMMENT_MODEL;
require_once POST_MODEL;
require_once USER_MODEL;

require_once AUTH_SERVICE;
require_once COMMENT_SERVICE;
require_once POST_SERVICE;
require_once USER_SERVICE;

require_once AUTH_CONTROLLER;
require_once COMMENT_CONTROLLER;
require_once POST_CONTROLLER;
require_once USER_CONTROLLER;


$db = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
$conn = $db->connect();

$authModel = new AuthModel($conn);
$authService = new AuthService($authModel);
$authController = new AuthController($authService);

$postModel = new PostModel($conn);
$postService = new PostService($postModel);
$postController = new PostController($postService, $authService);

$commentModel = new CommentModel($conn);
$commentService = new CommentService($postService, $commentModel);
$commentController = new CommentController($commentService, $authService);

$userModel = new UserModel($conn);
$userService = new UserService($userModel);
$userController = new UserController($userService, $authService);

# automatically execute router
require_once "config/router.php";