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
            
            $offset = GET("offset");

            if($offset === null || filter_var($offset, FILTER_VALIDATE_INT) === false)
                throw new HttpException(400, "Missing offset parameter or invalid number.");

            $posts = $this->postService->getPosts( 
                (int) GET("offset"), 
                GET("category") 
            );

            respond(0, 200, $posts);
        }
        catch(HttpException $e){    
            
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function getPost(): void
    {
        try{
            $postId = GET("id");

            if($postId === null || filter_var($postId, FILTER_VALIDATE_INT) === false)
                throw new HttpException(400, "Missing post id.");

            $post = $this->postService->getPost( (int) $postId );

            respond(0, 200, $post);
        }
        catch(HttpException $e){    
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function newPost(): void
    {
        try{
            $issuer = $this->authService->getUserInfo();

            if( strtolower(trim(POST("category"))) === "changelog" && !$this->authService->isAdmin() )
            {
                throw new HttpException(401 ,"Invalid permission.");
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
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function editPost(): void
    {
        try{

            $issuer = $this->authService->getUserInfo();

            if(POST("post_id") === null)
                throw new HttpException(400, "Post id is not specified");

            $post_id = (int) POST("post_id");

            $this->postService->validateEditPost(
                $issuer["id"], 
                $post_id,
                POST("category"),
                POST("title"),
                POST("description")
            );

            respond(0, 200, "Post editied.");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function deletePost(): void
    {
        try{
            $issuer = $this->authService->getUserInfo();

            $post_id = (int) POST("post_id");

            $this->postService->validateDeletePost(
                $issuer["id"],
                $post_id
            );

            respond(0, 200 ,"Post successfully deleted.");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }
}