<?php
declare(strict_types=1);

class CommentModel{

    public function __construct(
        private PDO $conn
    ){}    

    public function getCommentsByPostId(int $post_id, int $offset): ?array
    {
        $stmt = $this->conn->prepare("SELECT c.*, u.username, u.avatar
                                      FROM posts_comments c
                                      JOIN users u ON c.author_id = u.id
                                      WHERE c.post_id = ?
                                      ORDER BY c.post_date DESC, c.id DESC
                                      LIMIT 20 OFFSET ?
                                      ");

        $stmt->bindValue(1, $post_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $comments = $stmt->fetchAll();
        
        return $comments ? attachBaseUrl($comments) : null;
    }

    public function getComment(int $comment_id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM posts_comments WHERE id = ?");
        $stmt->execute([ $comment_id ]);

        return $stmt->fetch() ?: null;
    }

    public function insertComment(int $issuer_id, int $post_id, string $comment): void
    {
        $stmt = $this->conn->prepare("INSERT INTO posts_comments (post_id, author_id, comment)
                                      VALUES (?, ?, ?)");
        
        $stmt->execute([ $post_id, $issuer_id, $comment ]);
    }

    public function deleteComment(int $issuer_id, int $comment_id): void
    {
        $stmt = $this->conn->prepare("DELETE FROM posts_comments WHERE id = ? AND author_id = ?");
        $stmt->execute([ $comment_id, $issuer_id]);
    }
}