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

            if(GET("offset") === null || GET("limit") === null)
            {
                throw new HttpException(400, "Parameters does not satisfy.");
            }
            
            $offset   = (int)GET("offset");
            $limit    = (int)GET("limit");
            $category = GET("category");

            if($offset === null || filter_var($offset, FILTER_VALIDATE_INT) === false)
                throw new HttpException(400, "Missing offset parameter or invalid number.");

            if($limit === null || filter_var($limit, FILTER_VALIDATE_INT) === false)
                throw new HttpException(400, "Missing limit parameter or invalid number.");

            $posts = $this->postService->getPosts($offset, $limit, $category);

            respond(200, $posts);
        }
        catch(HttpException $e){    
            
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function getPost(int $post_id): void
    {
        try{

            if($post_id === null || filter_var($post_id, FILTER_VALIDATE_INT) === false)
                throw new HttpException(400, "Missing post id.");

            $post = $this->postService->getPost($post_id);

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

            if( strtolower(trim(POST("category"))) === "changelog" && !$this->authService->isAdmin() )
            {
                throw new HttpException(401 , "Forbidden operation.");
            }
                
            $this->postService->validateNewPost(
                $issuer["id"],
                POST("title"),
                POST("description"),
                POST("category"),
                FILES("attachment")
            );
        }
        catch(HttpException $e){    
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function editPost(int $post_id): void
    {
        try{

            $issuer = $this->authService->getUserInfo();

            if(POST("post_id") === null)
                throw new HttpException(400, "Post id is not specified");

            $this->postService->validateEditPost(
                $issuer["id"], 
                $post_id,
                POST("category"),
                POST("title"),
                POST("description")
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
                $issuer["id"],
                $post_id
            );

            respond(200 ,"Post successfully deleted.");
        }
        catch(HttpException $e){
            respond($e->getStatusCode(), $e->getMessage());
        }
    }
}