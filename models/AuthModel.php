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
        $stmt = $this->conn->prepare("SELECT u.id, u.username, u.bio, u.avatar, u.role, u.reg_date
                                      FROM `sessions` s 
                                      JOIN users u ON s.user_id = u.id 
                                      WHERE s.token = ? AND expires > UNIX_TIMESTAMP() 
                                      LIMIT 1");
        $stmt->execute([ $hashedToken ]);     

        $user = $stmt->fetch();
        
        return $user ? attachBaseUrl($user) : null;
    }

    public function updatePassword(int $user_id, string $new_password): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET `password` = ? WHERE id = ?");
        $stmt->execute([ $new_password, $user_id ]);
    }

    public function insertUserSession(int $user_id, string $token, int $expires): void
    {
        $stmt = $this->conn->prepare("INSERT INTO `sessions` (user_id, token, expires)
                                      VALUES (?, ?, ?)");
        $stmt->execute([ (int) $user_id, $token, $expires ]);      
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

    public function getUserById(int $user_id): ?array
    {

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([ $user_id ]);

        $user = $stmt->fetch();

        return $user ? attachBaseUrl($user) : null;
    }
    public function getUserByUsername(string $username): ?array
    {

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([ $username ]);

        $user = $stmt->fetch();

        return $user ? attachBaseUrl($user) : null;
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