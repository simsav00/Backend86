<?php
declare(strict_types=1);

class PostModel{

    public function __construct(
        private PDO $conn
    )
    {}

    public function getPosts(int $limit, int $offset): ?array
    {
        // $stmt = $this->conn->prepare("SELECT p.*, COUNT(pc.id) total_comments, u.username, u.avatar
        //                               FROM posts p 
        //                               LEFT JOIN posts_comments pc ON pc.post_id = p.id
        //                               JOIN users u ON p.author_id = u.id
        //                               GROUP BY p.id
        //                               ORDER BY p.id DESC
        //                               LIMIT ? OFFSET ?");

        $stmt = $this->conn->prepare("SELECT p.*, 
                                             COALESCE(c.total_comments, 0) total_comments,
                                             u.username, 
                                             u.avatar
                                      FROM posts p
                                      LEFT JOIN (
                                        SELECT post_id, COUNT(*) total_comments
                                        FROM posts_comments
                                        GROUP BY post_id
                                      ) c ON c.post_id = p.id
                                      JOIN users u ON p.author_id = u.id
                                      ORDER BY p.post_date DESC, p.id DESC
                                      LIMIT ? OFFSET ?");

        $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        
        return $posts ? attachBaseUrl($posts) : null;
    }

    public function getPostsByCategory(string $category, int $limit, int $offset): ?array
    {
        $stmt = $this->conn->prepare("SELECT p.*,
                                             COALESCE(c.total_comments, 0) total_comments,
                                             u.username,
                                             u.avatar
                                      FROM posts p
                                      LEFT JOIN (
                                        SELECT post_id, COUNT(*) total_comments
                                        FROM posts_comments
                                        GROUP BY post_id
                                      ) c ON c.post_id = p.id
                                      INNER JOIN users u ON p.author_id = u.id
                                      WHERE p.category = ?
                                      ORDER BY p.post_date DESC, p.id DESC
                                      LIMIT ? OFFSET ?");

        $stmt->bindValue(1, (string) $category, PDO::PARAM_STR);
        $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll();
        
        return $posts ? attachBaseUrl($posts) : null;
    }

    public function getPostById(int $postId): ?array
    {
        
        $stmt = $this->conn->prepare("SELECT p.*, u.username, u.avatar 
                                      FROM posts p
                                      JOIN users u ON p.author_id = u.id
                                      WHERE p.id = ?");
        $stmt->execute([ $postId ]);                        
        
        $post = $stmt->fetch();

        return $post ? attachBaseUrl($post) : null;
    }

    public function getPostCommentsById(int $postId): ?array
    {
        $stmt = $this->conn->prepare("SELECT c.*, u.username, u.avatar
                                      FROM posts_comments c
                                      JOIN users u ON c.author_id = u.id
                                      WHERE c.post_id = ?
                                      ORDER BY c.post_date DESC, c.id DESC
                                      ");
        
        $stmt->execute([ $postId ]);

        $comments = $stmt->fetchAll();
        
        return $comments ? attachBaseUrl($comments) : null;
    }

    public function updatePost(int $author_id,
                               int $post_id, 
                               string $category, 
                               string $title, 
                               ?string $description): void
    {
        $stmt = $this->conn->prepare("UPDATE posts SET category = ?, title = ?, description = ? WHERE id = ? AND author_id = ?");
        $stmt->execute([ $category, $title, $description, $post_id, $author_id ]);
    }

    public function deletePost(int $author_id, int $post_id): void
    {
        $stmt = $this->conn->prepare("DELETE FROM posts WHERE id = ? AND author_id = ?");
        $stmt->execute([ $post_id, $author_id ]);
    }

    public function insertPost(int $author_id, 
                               string $category, 
                               string $title, 
                               ?string $description,
                               ?string $file_name,
                               ?string $file_ext,
                               ?string $file_url): void
    {
        $stmt = $this->conn->prepare("INSERT INTO posts 
                                      (author_id, category, title, `description`, file_name, file_ext, file_url)
                                      VALUES (:author_id, :category, :title, :description, :file_name, :file_ext, :file_url)
                                    ");
        $stmt->execute([
            ":author_id"    => $author_id,
            ":category"     => $category,
            ":title"        => $title,
            ":description"  => $description,
            ":file_name"    => $file_name,
            ":file_ext"     => $file_ext,
            ":file_url"     => $file_url
        ]);      
    }
}