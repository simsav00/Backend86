<?php
declare(strict_types=1);

class UserModel{

    public function __construct(
        private PDO $conn
    ){}

    public function getUserById( int $user_id ): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, username, role, bio, avatar, reg_date FROM users WHERE id = ?");
        $stmt->execute([ $user_id ]);

        $user = $stmt->fetch();

        return $user ? attachBaseUrl($user) : null;
    }

    public function updateAvatarUrl( int $user_id, string $avatar_url ): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([ $avatar_url, $user_id ]);
    }

    public function updateBio(  int $user_id, ?string $bio ): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET bio = ? WHERE id = ? ");
        $stmt->execute([ $bio, $user_id ]);
    }
}