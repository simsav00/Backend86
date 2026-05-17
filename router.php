<?php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$path = str_replace("/" . BASE_URL . "/", "", $path);
$parts = explode("/", trim($path, "/"));
$method = $_SERVER["REQUEST_METHOD"];

// Posts router
if($parts[0] === "categories_list" && $method === "GET")
{
    respond(200, categories_list());
}
elseif($parts[0] === "categories" && $method === "GET")
{
    respond(200, categories());
}

elseif ($parts[0] === "posts") {

    if ($method === "GET" && count($parts) === 1) {
        $postController->getAllPosts();
    }

    elseif ($method === "POST" && count($parts) === 1) {
        $postController->newPost();
    }

    elseif (isset($parts[1]) && ctype_digit($parts[1])) {
        $postId = (int)$parts[1];

        if ($method === "GET" && count($parts) === 2) {
            $postController->getPost($postId);
        }

        elseif ($method === "PATCH" && count($parts) === 2) {
            $postController->editPost($postId);
        }

        elseif ($method === "DELETE" && count($parts) === 2) {
            $postController->deletePost($postId);
        }

        elseif (($parts[2] ?? "") === "comments") {

            if ($method === "GET") {
                $commentController->getComments($postId);
            }

            elseif ($method === "POST") {
                $commentController->newComment($postId);
            }
        }
    }
}

// Comments router

elseif ($parts[0] === "comments") {
    if (
        $method === "DELETE" &&
        isset($parts[1]) &&
        ctype_digit($parts[1])
    ) {
        $commentController->deleteComment((int)$parts[1]);
    }
}

// Auth router

elseif ($parts[0] === "auth") {
    $action = $parts[1] ?? "";

    if ($method === "POST") {
        match ($action) {
            "login" => $authController->login(),
            "register" => $authController->register(),
            "logout" => $authController->logout(),
            default => null
        };
    }
}

// Users router
elseif ($parts[0] === "users") {

    if (($parts[1] ?? "") === "me") {
        $authController->me();
    }
    elseif(ctype_digit($parts[1])) {
        $userController->getUser((int)$parts[1]);
    }
}

else{
    respond(404, "Unknown page.");
}