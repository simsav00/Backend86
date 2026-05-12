<?php
declare(strict_types=1);

use Exceptions\HttpException;

class CommentService{
    public function __construct(
        private PostService $postService,
        private CommentModel $commentModel
    ){}

    public function getPostComments(int $post_id, int $offset): ?array
    {
        $comments = $this->commentModel->getCommentsByPostId($post_id, $offset);
    
        return $comments ? attachBaseUrl($comments)     : null;
    }

    public function validateComment(int $issuer_id, int $post_id, string $comment): void
    {
        $comment = trim($comment);

        if(!$issuer_id || !$post_id)
            throw new HttpException(400, "Issuer and post id is required.");

        if($comment === "")
            throw new HttpException(400, "Comment is required.");

        if(strlen($comment) > 16384)
            throw new HttpException(400, "Comment must be under 16384 characters");

        $this->postService->getPost($post_id);

        $this->commentModel->insertComment($issuer_id, $post_id, $comment);
    }

    public function validateDeleteComment(int $issuer_id, int $comment_id): void
    {
        if(!$issuer_id || !$comment_id)
            throw new HttpException(400, "Unspecified issuer or post id.");

        $cmt = $this->commentModel->getComment($comment_id);

        if(!$cmt)
            throw new HttpException(404, "Comment not found.");

        if($cmt["author_id"] !== $issuer_id)
            throw new HttpException(403,"Forbidden operation.");

        $this->commentModel->deleteComment($issuer_id, $comment_id);
    }
}