<?php
declare(strict_types=1);

use Exceptions\HttpException;

class CommentController{

    public function __construct(
        private CommentService $commentService,
        private AuthService $authService
    ){}

    public function getComments(): void
    {   
        try{

            $post_id = GET("post_id");
            $offset  = GET("offset");

            if(!$post_id || filter_var($post_id, FILTER_VALIDATE_INT) === false)
            {
                throw new HttpException(400, "Invalid post id");
            }
            
            if($offset === null || filter_var($offset, FILTER_VALIDATE_INT) === false)
            {
                throw new HttpException(400, "Invalid or missing offset.");
            }

            $comments = $this->commentService->getPostComments($post_id, $offset);

            if(!$comments)
                throw new HttpException(404, "No comments.");

            respond(0, 200, $comments);
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function newComment(): void
    {
        try{

            $issuer = $this->authService->getUserInfo();

            $comment   = POST("comment");
            $issuer_id = $issuer["id"];
            $post_id   = POST("post_id");

            $this->commentService->validateComment(
                $issuer_id, $post_id, $comment
            );

            respond(0, 201, "Comment created successfully.");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }

    }

    public function deleteComment(): void
    {
        try{

            $issuer = $this->authService->getUserInfo();
            $comment_id = POST("comment_id");

            $this->commentService->validateDeleteComment(
               $issuer["id"], (int) $comment_id
            );

            respond(0, 200, "Comment deleted successfully.");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

}