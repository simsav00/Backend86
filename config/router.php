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
        $userController->getCategories();
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

            switch ($parts[2] ?? "") {

                case "comments":
                    if ($method === "GET") {
                        $commentController->getComments($postId);
                    }

                    elseif ($method === "POST") {
                        $commentController->newComment($postId);
                    }
                    break;

                case "like":
                    if($method === "PATCH"){
                        $postController->likePost($postId);
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
                "password" => $authController->alterPassword(),
                default => null
            };
        }

    }

    // Users router
    elseif ($parts[0] === "users") {

        if($method === "GET") {
            if (($parts[1] ?? "") === "me") {
                $authController->me();
            }
            elseif(($parts[1] ?? "") === "all") {
                $userController->getAllUsers();
            }
            elseif(ctype_digit($parts[1])) {

                if(!isset($parts[2])) {
                    $userController->getUser((int)$parts[1]);
                }
                elseif(isset($parts[2]) && $parts[2] === "posts") {
                    $postController->getPostsByUserId((int)$parts[1]);
                }
            }
            
        }
        elseif($method === "POST"){
            match($parts[1]){
                "avatar" => $userController->newAvatar(),
                default => null
            };
        }
        elseif($method === "PATCH" && $parts[1] === "bio"){
            $userController->newBio();
        }

    }

    else{
        respond(404, "Unknown page.");
    }
