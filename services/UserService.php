<?php
declare(strict_types=1);

use Exceptions\HttpException;

class UserService{

    public function __construct(
        private UserModel $userModel
    ){}

    public function getAllUsers(): ?array
    {
        return $this->userModel->getAllUsers();
    }

    public function validateGetUser(int $user_id): ?array
    {
        return $this->userModel->getUserById($user_id);
    }

    public function validateAvatar(int $user_id, array $avatar): void
    {
        if(!$user_id)
            throw new HttpException(400, "Unspecified user id.");

        if(!$avatar)
            throw new HttpException(400, "An image must be uploaded.");

        $avatarName = $avatar["name"];
        $avatarSize = $avatar["size"];
        $avatarTmp  = $avatar["tmp_name"];
        $avatarExt  = strtolower(pathinfo($avatarName, PATHINFO_EXTENSION));
        $avatarError = $avatar["error"];
        $new_filename = "avatar_" . (string) $user_id . ".webp";
        $browser_destination = USER_AVATAR_URL_BASE() . "/" . $new_filename;
        $server_destination = USER_AVATAR_DIR() . "/" . $new_filename;

        $gd_info = getimagesize($avatarTmp);

        if($avatarError !== UPLOAD_ERR_OK)
            throw new HttpException(400, "Failed to upload avatar. ($avatarError)");

        if($avatarSize > AVATAR_IMAGE_MAX_FILE_SIZE)
            throw new HttpException(413, "File too large, maximum file size is " . AVATAR_IMAGE_MAX_FILE_SIZE / 1048576 . "MB");

        if(!$gd_info)
            throw new HttpException(400, "Invalid image file.");

        if(!isImage($avatarExt))
            throw new HttpException(400, "Only image files are allowed for upload.");

        if($gd_info[0] > AVATAR_IMAGE_MAX_WIDTH || $gd_info[1] > AVATAR_IMAGE_MAX_HEIGHT)
            throw new HttpException(400, "Image resolution too large, maximum is " . AVATAR_IMAGE_MAX_WIDTH . "x" . AVATAR_IMAGE_MAX_HEIGHT . ".");

        $img = null;

        match($gd_info["2"]){

            IMAGETYPE_JPEG => $img = imagecreatefromjpeg($avatarTmp),
            IMAGETYPE_PNG  => $img = imagecreatefrompng($avatarTmp),
            IMAGETYPE_GIF  => $img = imagecreatefromgif($avatarTmp),
            IMAGETYPE_WEBP => $img = imagecreatefromwebp($avatarTmp),

            IMAGETYPE_AVIF => function_exists("imagecreatefromavif") 
                                ? $img = imagecreatefromavif($avatarTmp) 
                                : throw new HttpException(500, "Unsupported image type."),

            default => throw new HttpException(500, "Unsupported image mime.")
        };

        imagepalettetotruecolor($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        if(!is_dir(USER_AVATAR_DIR()))
            mkdir(USER_AVATAR_DIR(), 0777, true);
        
        if(!imagewebp(
            $img, 
            $server_destination,
            AVATAR_IMAGE_WEBP_COMPRESS_QUALITY
        ))
        {
            throw new HttpException(500, "Internal Server Error: Unable to save image.");
        }

        imagedestroy($img);

        $this->userModel->updateAvatarUrl($user_id, $browser_destination . "?=" . time());
    }

    public function validateBio( int $user_id, ?string $bio ): void
    {
        if(!$user_id)
            throw new HttpException(400, "Unspecified user id.");

        if(strlen($bio) > BIO_MAX_LENGTH)
            throw new HttpException(400, "Bio cannot exceed ". BIO_MAX_LENGTH . " characters.");

        if($bio === "" || $bio === null) $bio = null;

        $this->userModel->updateBio($user_id, $bio);
    }
}