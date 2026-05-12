<?php

declare(strict_types=1);

define("APP_NAME",  "Auto86");
define("APP_FNAME", "Auto");
define("APP_LNAME", "86");
define("APP_VERSION", "1.0.0");
define("APP_DESCRIPTION", "");

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

define("CURRENT_URL", (string) $baseUrl);
define("BASE_URL", "Auto86");

define("DB_CONN", "config/connect.php");

define("DIR", __DIR__);
define("AMP", "&");

define("BOOTSTRAP", DIR . "/bootstrap.php");
define("AUTH_CONTROLLER",    "controllers/AuthController.php");
define("COMMENT_CONTROLLER", "controllers/CommentController.php");
define("POST_CONTROLLER",    "controllers/PostController.php");
define("USER_CONTROLLER",    "controllers/UserController.php");

define("AUTH_MODEL",         "models/AuthModel.php");
define("COMMENT_MODEL",      "models/CommentModel.php");
define("POST_MODEL",         "models/PostModel.php");
define("USER_MODEL",         "models/UserModel.php");

define("AUTH_SERVICE",         "services/AuthService.php");
define("COMMENT_SERVICE",      "services/CommentService.php");
define("POST_SERVICE",         "services/PostService.php");
define("USER_SERVICE",         "services/UserService.php");



define("HTTP_EXCEPTION",      "exceptions/HttpException.php");

define("DEFAULT_AVATAR_DIR", APP_ROOT(). "/" . BASE_URL . "/assets/default/avatar.webp");

define("USER_AVATAR_DIR", APP_ROOT() . "/" . BASE_URL . "/uploads/users/avatar");

function USER_AVATAR_URL_BASE(): string
{
    return "/" . BASE_URL . "/uploads/users/avatar";
}

function USER_AVATAR_DIR(): string
{
    return APP_ROOT() . USER_AVATAR_URL_BASE();
    # return USER_AVATAR_URL_BASE() . "/avatar_" . $id . ".webp";
}

function USER_POST_ATTACHMENT_DIR(int $id): string
{
    return APP_ROOT() . "/" . BASE_URL . "/uploads/users/post/" . $id;
}

function USER_POST_ATTACHMENT_URL_BASE(int $id): string
{
    return "/" . BASE_URL . "/uploads/users/post/" . $id;
}

function categories(): array{
    return array(
        "General" => "General",
        "JDM" => "JDM",
        "EU" => "Europe",
        "USDM" => "US Domestic Market",
        "KDM" => "Korea Domestic Market",
        "ADM" => "Aussie Domestic Market",
        "UKDM" => "UK Domestic Market",
        "IDM" => "Indonesia Domestic Market",
        "DM" => "Domestic Market"
    );
}

function categories_list(): array{
    return array(
        "Changelog" => "Changelog",
        "All" => "All posts",
        ...categories()
    );
}

function respond(int $type, int $status, mixed $data): void
{
    http_response_code($status);

    if($type === 1){

        exit(json_encode([
            "status" => $status,
            "message"=> $data
        ], 128));
    }
    elseif($type === 0){

        exit(json_encode([
            "status" => $status,
            "data"   => $data
        ], 128));
    }
    else{
        throw new \Exception("Invalid error type. Got: $type instead of either 0 or 1.");
    }
}


function getSessionCookie(): ?string{
    return $_COOKIE["session"];
}

function isAdmin(): void{
}

function APP_ROOT(): string{
    return SERVER("DOCUMENT_ROOT");
}

function COOKIE(string $name): mixed{
    return $_COOKIE[$name];
}

function SESSION(string $name): mixed{
    return $_SESSION[$name] ?? "";
}

function FILES(string $name, mixed $default = []): ?array{
    return $_FILES[$name] ?? $default;
}

function POST(string $name, mixed $default = null): mixed{
    return $_POST[$name] ?? $default;
}

function GET(string $name, mixed $default = null): mixed{
    return $_GET[$name] ?? $default;
}

function SERVER(string $key): mixed{
    return $_SERVER[$key];
}

function REQUEST_METHOD(string $key): bool{
    return $_SERVER["REQUEST_METHOD"] === $key;
}

function isPost(): bool{
    return REQUEST_METHOD("POST");
}
 
function isGet(): bool{
    return REQUEST_METHOD("GET");
}

function isLoggedIn(): bool{
    return isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;
}

function requirePost(): void{
    if(!isPost())
        respond(1, 405, "Method not allowed.");
}

function requireLogin(): void{
    
}

function attachBaseUrl(array $variable): ?array
{
    $isSingle = isset($variable["file_url"]) || isset($variable["avatar"]);

    if ($isSingle) {
        $variable = [$variable];
    }

    foreach ($variable as &$var) {
        if (isset($var["file_url"]) && !empty($var["file_url"])) {
            $var["file_url"] = CURRENT_URL . $var["file_url"];
        }

        if (isset($var["avatar"]) && !empty($var["avatar"])) {
            $var["avatar"] = CURRENT_URL . $var["avatar"];
        }
    }
    unset($var);

    return $isSingle ? $variable[0] : $variable;
}

function isImage(string $filetype): bool
{
    return in_array($filetype, [
        "jpg",  "webp", "png", "apng",  "gif",  "webp", "avif",
        "heic", "heif", 
        "tif",  "tiff", "bmp",  "ico",  "jxl",

        "dng",  "cr2",  "cr3",  "nef",  "arw",  "raf",  "orf",
    ]);
}

function isVideo(string $filetype): bool
{
    return in_array($filetype, 
            ["mp4",  "mov",  "mkv",  "avi",  "webm",
            "m4v",  "mpg",  "mpeg",
            "3gp",  "3g2",
            "ts",   "mts",  "m2ts",
            "flv",  "f4v",  "ogv"]
        );
}

function isImageMime(string $mime): bool
{
    return in_array($mime, [
        "image/jpeg",
        "image/webp",
        "image/png",
        "image/apng",
        "image/gif",
        "image/avif",
        "image/heic",
        "image/heif",
        "image/tiff",
        "image/bmp",
        "image/x-icon",
        "image/vnd.microsoft.icon",
        "image/jxl",

        // RAW formats
        "image/x-adobe-dng",
        "image/x-canon-cr2",
        "image/x-canon-cr3",
        "image/x-nikon-nef",
        "image/x-sony-arw",
        "image/x-fuji-raf",
        "image/x-olympus-orf",
    ]);
}

function isVideoMime(string $mime): bool
{
    return in_array($mime, [
        "video/mp4",
        "video/quicktime",     // mov
        "video/x-matroska",    // mkv
        "video/x-msvideo",     // avi
        "video/webm",

        "video/x-m4v",         // m4v
        "video/mpeg",

        "video/3gpp",          // 3gp
        "video/3gpp2",         // 3g2

        "video/mp2t",          // ts
        "video/MP2T",          // ts
        "video/avi",           // uncommon alt
        "video/x-flv",         // flv
        "video/ogg",           // ogv
    ]);
}