<?php
declare(strict_types=1);

class AuthModel{

    public function __construct(
        private PDO $conn
    ){}

    public function getCurrentSessionByToken( string $hashedToken ): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM `sessions` WHERE token = ? AND expires > UNIX_TIMESTAMP() LIMIT 1");
        $stmt->execute([ $hashedToken ]);

        return $stmt->fetch() ?: null;
    }

    public function getUserInfoBySessionToken( ?string $hashedToken ): ?array
    {
        $stmt = $this->conn->prepare("SELECT u.id, u.username, u.bio, u.avatar 
                                      FROM `sessions` s 
                                      JOIN users u ON s.user_id = u.id 
                                      WHERE s.token = ? AND expires > UNIX_TIMESTAMP() 
                                      LIMIT 1");
        $stmt->execute([ $hashedToken ]);     
        
        return $stmt->fetch() ?: null;
    }

    public function insertUserSession(int $user_id, string $token, int $expires): void
    {
        $stmt = $this->conn->prepare("INSERT INTO `sessions` (user_id, token, expires)
                                      VALUES (?, ?, ?)");
        $stmt->execute([ (int) $user_id, $token, $expires ])                                ;      
    }

    public function updateUserSession(string $oldToken, string $newToken, int $expires): void
    {
        $stmt = $this->conn->prepare("UPDATE `sessions` SET token = ?, expires = ? WHERE token = ?");

        $stmt->execute([ $newToken, $expires, $oldToken ]);
    }

    public function deleteUserSession(string $hashedToken): void
    {
        $stmt = $this->conn->prepare("DELETE FROM `sessions` WHERE token = ?");
        $stmt->execute([ $hashedToken ]);
    }

    public function getUser(string $username): ?array
    {

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([ $username ]);

        return $stmt->fetch() ?: null;
    }

    public function insertUser(string $username, 
                               string $password,
                               ?string $avatar = null): void
    {
        $stmt = $this->conn->prepare("INSERT INTO users (username, `password`, avatar)
                                      VALUES (?, ?, ?)");
        $stmt->execute([ $username, $password, $avatar ]);
    }
}