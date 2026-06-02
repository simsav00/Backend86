<?php
declare(strict_types=1);

use Exceptions\HttpException;

class CommentController{

    public function __construct(
        private CommentService $commentService,
        private AuthService $authService
    ){}

    public function getComments(int $post_id): void
    {   
        try{

            $offset  = GET("offset");
            $limit   = GET("limit");

            if(!$post_id || filter_var($post_id, FILTER_VALIDATE_INT) === false)
            {
                throw new HttpException(400, "Invalid post id");
            }
            
            if($offset === null || filter_var($offset, FILTER_VALIDATE_INT) === false)
            {
                throw new HttpException(400, "Invalid or missing offset.");
            }

            if($limit === null || filter_var($limit, FILTER_VALIDATE_INT) === false)
            {
                throw new HttpException(400, "Invalid or missing limit.");
            }

            $comments = $this->commentService->getPostComments($post_id, (int)$limit, (int)$offset);

            respond(200, $comments);
        }
        catch(HttpException $e){
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

    public function newComment(int $post_id): void
    {
        try{

            $issuer = $this->authService->getUserInfo();

            $comment   = POST("comment");
            $issuer_id = $issuer["id"];

            $this->commentService->validateComment(
                $issuer_id, $post_id, $comment
            );

            respond(201, "Comment created successfully.");
        }
        catch(HttpException $e){
            respond($e->getStatusCode(), $e->getMessage());
        }

    }

    public function deleteComment(int $comment_id): void
    {
        try{

            $issuer = $this->authService->getUserInfo();

            $this->commentService->validateDeleteComment(
               $issuer, $comment_id
            );

            respond(200, "Comment deleted successfully.");
        }
        catch(HttpException $e){
            respond($e->getStatusCode(), $e->getMessage());
        }
    }

}