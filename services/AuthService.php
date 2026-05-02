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

        $duplicates = $this->authModel->getUser($username);

        if($duplicates)
            throw new HttpException(409, "User with the name already exists.");

        $hashPwd = password_hash($password, PASSWORD_DEFAULT);

        $this->authModel->insertUser($username, $hashPwd);
    }

    public function validateLogin(string $username, string $password): void
    {
        $username = trim($username);
        $password = trim($password);

        if(!$username || !$password)
            throw new HttpException(400, "Username or password cannot be empty.");

        $user = $this->authModel->getUser($username);

        if(!$user || !password_verify($password, $user["password"]))
            throw new HttpException(401, "Invalid credentials.");

        $session = self::newSessionCookie();

        $this->authModel->insertUserSession($user["id"], $session["hashedToken"], $session["expires"]);
    }

    function dropSession(): void
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

    public function getSessionFromCookie(): ?array
    {
        if(!isset($_COOKIE["session"]))
            throw new HttpException(401, "User is not logged in.");

        $rawToken = COOKIE("session");
        $hashed = hash("sha256", $rawToken);

        $session = $this->authModel->getCurrentSessionByToken($hashed);

        if(!$session || $session["expires"] < time())
            throw new HttpException(401, "Invalid or expired session.");

        if($session["expires"] - time() < (86400 * 2)) {
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
        $expires = time() + (86400 * 14);

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