<?php
declare(strict_types=1);

use Exceptions\HttpException;

class PostController{

    public function __construct(
        private PostService $postService,
        private AuthService $authService
    ){}

    public function getAllPosts(): void
    {
        try{

            $issuer = $this->authService->getUserInfo();

            $params = self::validateParameters();
            
            $category = GET("category");

            $posts = $this->postService->getPosts($params["offset"], $params["limit"], $category, $issuer["id"]);

            respond(200, $posts);
        }
        catch(HttpException $e){    
            
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function getPostsByUserId(int $user_id): void
    {
        try{

            if(!$user_id || !is_int($user_id))
                throw new HttpException(400, "Invalid user_id");

            $params = self::validateParameters();

            respond(200, $this->postService->getPostsByUserId($params["offset"],$params["limit"], $user_id));   
        }
        catch(HttpException $e){    
            respond($e->getStatusCode(), $e->getMessage());
        }


    }

    public function getPost(int $post_id): void
    {
        try{

            $issuer = $this->authService->getUserInfo();

            if($post_id === null || filter_var($post_id, FILTER_VALIDATE_INT) === false)
                throw new HttpException(400, "Missing post id.");

            $post = $this->postService->getPost($post_id, $issuer["id"]);

            respond(200, $post);
        }
        catch(HttpException $e){    
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function newPost(): void
    {
        try{
            $issuer = $this->authService->getUserInfo();

            $category = strtolower(trim(POST("category")));
            # $categories = array_change_key_case(categories(), CASE_LOWER);

            if( $category === "changelog" && $issuer["role"] !== "admin" )
            {
                throw new HttpException(403 , "Forbidden operation.");
            }

            /*if(!array_key_exists($category, $categories))
            {
                throw new HttpException(400, "Category does not exist.");
            }*/
                
            $this->postService->validateNewPost(
                $issuer["id"],
                POST("title"),
                POST("description"),
                POST("category"),
                FILES("file")
            );

            respond(201, "Post created successfully.");
        }
        catch(HttpException $e){    
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function likePost(int $post_id): void
    {
        try{
            $issuer = $this->authService->getUserInfo();

            $res = $this->postService->validateLikePost($issuer["id"], $post_id);

            if(!$res)
                respond(200, "Post liked successfully.");

            else
                respond(204, "Post like removed successfully.");
        }
        catch(HttpException $e){
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function editPost(int $post_id): void
    {
        try{

            $issuer = $this?->authService->getUserInfo();

            $post = getBody();

            if(!isset($post["title"]) || !isset($post["category"])){
                throw new HttpException(400, "Missing title or category.");
            }

            $this->postService->validateEditPost(
                $issuer["id"], 
                $post_id,
                $post["category"],
                $post["title"],
                $post["description"] ?? ""
            );

            respond(200, "Post editied successfully.");
        }
        catch(HttpException $e){
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function deletePost(int $post_id): void
    {
        try{
            $issuer = $this->authService->getUserInfo();

            $this->postService->validateDeletePost(
                $issuer,
                $post_id
            );

            respond(200 ,"Post successfully deleted.");
        }
        catch(HttpException $e){
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    private static function validateParameters(): array{

        if(GET("offset") === null || GET("limit") === null)
        {
            throw new HttpException(400, "Parameters does not satisfy.");
        }

        if(filter_var(GET("offset"), FILTER_VALIDATE_INT) === false || (int)GET("offset") < 0)
            throw new HttpException(400, "Missing offset parameter or invalid number.");

        if(filter_var(GET("limit"), FILTER_VALIDATE_INT) === false || (int)GET("limit") <= 0)
            throw new HttpException(400, "Missing limit parameter or invalid number.");

        return [
            "offset" => (int)GET("offset"),
            "limit"  => (int)GET("limit")
        ];
    }
}