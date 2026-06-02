<?php
declare(strict_types=1);
use Exceptions\HttpException;

class AuthService{

    public function __construct(
        private AuthModel $authModel
    ){}

    public function validateRegister(string $username, string $password): void
    {
        $username = trim($username);
        $password = trim($password);

        if(!$username || !$password)
            throw new HttpException(400, "Username or password cannot be empty.");

        if(strlen($username) < REGISTER_USERNAME_MIN_CHARS || strlen($username) > REGISTER_USERNAME_MAX_CHARS) {
            throw new HttpException(400, "Username must be " . REGISTER_USERNAME_MIN_CHARS . "-" . REGISTER_USERNAME_MAX_CHARS . " characters.");
        }

        if(strlen($password) < REGISTER_PASSWORD_MIN_CHARS || strlen($password) > REGISTER_PASSWORD_MAX_CHARS) {
            throw new HttpException(400, "Password must be " . REGISTER_PASSWORD_MIN_CHARS . "-" . REGISTER_PASSWORD_MAX_CHARS . " characters.");
        }

        $duplicates = $this->authModel->getUserByUsername($username);

        if($duplicates) {
            throw new HttpException(409, "User with the name already exists.");
        }

        $hashPwd = password_hash($password, PASSWORD_DEFAULT);

        $this->authModel->insertUser($username, $hashPwd, REGISTER_DEFAULT_AVATAR);
    }

    public function validateLogin(string $username, string $password): void
    {
        $username = trim($username);
        $password = trim($password);

        if(!$username || !$password)
            throw new HttpException(400, "Username or password cannot be empty.");

        $user = $this->authModel->getUserByUsername($username);

        if(!$user || !password_verify($password, $user["password"]))
            throw new HttpException(401, "Invalid credentials.");

        $session = self::newSessionCookie();

        $this->authModel->insertUserSession($user["id"], $session["hashedToken"], $session["expires"]);
    }

    public function validateAlterPassword(int $user_id, string $oldPassword, string $newPassword) : void
    {
        if(!$newPassword || !$oldPassword){
            throw new HttpException(400, "New password and old password are required.");
        }
        
        $oldPassword = trim($oldPassword);
        $newPassword = trim($newPassword);

        $user = $this->authModel->getUserById($user_id);

        if(!$user) {
            throw new HttpException(400, "User not found.");
        }

        if(strlen($newPassword) < REGISTER_PASSWORD_MIN_CHARS || strlen($newPassword) > REGISTER_PASSWORD_MAX_CHARS) {
            throw new HttpException(400, "Password must be " . REGISTER_PASSWORD_MIN_CHARS . "-" . REGISTER_PASSWORD_MAX_CHARS . " characters.");
        }

        if(!password_verify($oldPassword, $user["password"])) {
            throw new HttpException(400, "Old password does not match.");
        }

        $hashPwd = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->authModel->updatePassword($user_id, $hashPwd);

        $session = $this->getSessionFromCookie();

        self::rotateSessionCookie($session["token"]);
    }

    public function isAdmin(): bool
    {
        $user = $this->getUserInfo();

        return $user["role"] === "admin";
    }

    public function dropSession(): void
    {   
        $session = $this->getSessionFromCookie();
        
        $this->authModel->deleteUserSession( $session["token"] );

        self::deleteSessionCookie();
    }

    public function getUserInfo(): ?array
    {
        $session = $this->getSessionFromCookie();

        $user = $this->authModel->getUserInfoBySessionToken( $session["token"] );

        return $user ?: null;
    }

    public function getSessionFromCookie(bool $rehash_if_expired = true): ?array
    {
        if(!isset($_COOKIE["session"]))
            throw new HttpException(401, "User is not logged in.");

        $rawToken = COOKIE("session");
        $hashed = hash("sha256", $rawToken);

        $session = $this->authModel->getCurrentSessionByToken($hashed);

        if(!$session || $session["expires"] < time())
            throw new HttpException(401, "Invalid or expired session.");

        if($rehash_if_expired && $session["expires"] - time() < (SESSION_EXPIRY_BEFORE_ROTATION)) {
            
            $newSession = self::newSessionCookie();

            $this->authModel->updateUserSession(
                $hashed,
                $newSession["hashedToken"],
                $newSession["expires"]
            );

            $session["token"] = $newSession["hashedToken"];
            $session["expires"] = $newSession["expires"];
        }

        return $session;
    }

    public function rotateSessionCookie(string $oldHashedToken): void
    {
        $session = self::newSessionCookie();

        $this->authModel->updateUserSession(
            $oldHashedToken,
            $session["hashedToken"],
            $session["expires"]
        );
    }

    public static function newSessionCookie(): array
    {
        $rawToken = bin2hex(random_bytes(64));
        $hashedToken = hash("sha256", $rawToken);
        $expires = time() + (SESSION_CREATION_EXPIRY);

        self::setSessionCookie($rawToken, $expires);

        return [
            "rawToken" => $rawToken,
            "hashedToken" => $hashedToken,
            "expires" => $expires
        ];
    }

    public static function setSessionCookie(string $rawToken, int $expires): void
    {
        setcookie("session", (string) $rawToken, [
            "expires" => $expires,
            "path" => "/",
            "secure" => true,
            "httponly" => true,
            "samesite" => "None"
        ]);
    }

    public static function deleteSessionCookie(): void
    {
        self::setSessionCookie("", time() - 2147638);
        unset($_COOKIE["session"]);
    }
}