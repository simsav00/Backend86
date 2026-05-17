<?php
declare(strict_types=1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once "config/functions.php";
require_once "libraries/envLoader.php";

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

loadEnv(".env");

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