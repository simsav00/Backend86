<?php
declare(strict_types=1);

class AuthModel{

    public function __construct(
        private PDO $conn
    ){}

    public function getUserSession(string $sha256Token): array
    {
        $stmt = $this->conn->prepare("SELECT s.user_id, u.id, u.username, u.bio, u.avatar 
                                      FROM `sessions` s 
                                      JOIN users u ON s.user_id = u.id 
                                      WHERE s.token = ? LIMIT 1");
        $stmt->execute([ $sha256Token ]);     
        
        return $stmt->fetch() ?: [];
    }

    public function getUser(string $username): array
    {

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([ $username ]);

        return $stmt->fetch() ?: [];
    }

    public function insertUser(string $username, 
                               string $password,
                               ?string $avatar): void
    {
        $stmt = $this->conn->prepare("INSERT INTO users (username, `password`, avatar)
                                      VALUES (?, ?, ?)");
        $stmt->execute([ $username, $password, $avatar ]);
    }
}