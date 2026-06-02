<?php
declare(strict_types=1);

use Exceptions\HttpException;

class PostService{

    public function __construct(
        private PostModel $postModel
    ){}

    public function getPosts(int $offset, int $limit, ?string $category = null, ?int $issuer_id = null): ?array
    {

        if(!$category || strtolower(trim($category)) === "all"){
            $posts = $this->postModel->getPosts($limit, $offset);
        }
        else{
            $posts = $this->postModel->getPostsByCategory($category, $limit, $offset);
        }

        if($issuer_id && $posts){
            foreach($posts as &$post){
                $post["liked"] = (bool)$this->postModel->getLike($issuer_id, $post["id"]);
            }
            unset($post);
        }

        return $posts;
    }

    public function getPost( int $id, ?int $issuer_id = null ): ?array
    {

        $post = $this->postModel->getPostById($id);

        if(!$post)
            throw new HttpException(404, "Post not found.");

        if($issuer_id){

            $post = [
                ...$post,
                "liked" => (bool)$this->postModel->getLike($issuer_id, $post["id"])
            ];
        }

        return $post;
    }

    public function getPostsByUserId(int $offset, int $limit, int $user_id): ?array
    {
        return $this->postModel->getPostsByUserId($user_id, $limit, $offset) ?: [];
    }

    public function validateLikePost(int $issuer, int $post_id): ?array
    {
        $like = $this->postModel->getLike($issuer, $post_id);
        
        if($like){
            $this->postModel->deleteLike($issuer, $post_id);
        }
        else{
            $this->postModel->insertLike($issuer, $post_id);
        }

        return $like;
    }

    public function validateNewPost( int $author_id, string $title, ?string $description, string $category, ?array $file  ): void
    {
        $title = trim($title);
        $description = $description ? trim($description) : null;
        $category = trim($category);

        if($title === "")
            throw new HttpException(400, "Title cannot be empty.");

        if(strlen($title) < NEWPOST_MIN_TITLE_LENGTH || strlen($title) > NEWPOST_MAX_TITLE_LENGTH)
            throw new HttpException(400, "Title must be " . NEWPOST_MIN_TITLE_LENGTH . "-" . NEWPOST_MAX_TITLE_LENGTH . " characters.");

        if($description && strlen($description) > NEWPOST_MAX_DESC_LENGTH)
            throw new HttpException(400, "Description cannot exceed " . NEWPOST_MAX_DESC_LENGTH . "  characters.");

        $newFilename = null;
        $fileExt     = null;
        $server_destination = null;
        $browser_destination = null;

        if(!empty($file["name"])){

            $fileName   = $file["name"];
            $fileExt    = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileTmp    = $file["tmp_name"];
            $fileSize   = $file["size"];
            $fileError  = $file["error"];

            $finfo      = new finfo(FILEINFO_MIME_TYPE);
            $fileMime   = $finfo->file($fileTmp);


            if($fileError !== UPLOAD_ERR_OK)
                throw new HttpException(400, "An error ocurred while uploading file. ($fileError)");

            if(
               NEWPOST_ENABLE_STRICT_MIME_CHECKING &&
               (!isImage($fileExt) && !isVideo($fileExt) ||
               isImage($fileExt) && !isImageMime($fileMime) ||
               isVideo($fileExt) && !isVideoMime($fileMime))
            ){
                throw new HttpException(400, "Only image and video are allowed for uploads.");
            }

            if(!isImage($fileExt) && !isVideo($fileExt)){
                throw new HttpException(400, "Only image and video are allowed for uploads.");
            }

            if($fileSize > NEWPOST_MAX_FILE_SIZE)
                throw new HttpException(413, "File too large, max file size is " . NEWPOST_MAX_FILE_SIZE / 1048576 . " MB");


            $newFilename = sprintf(
                "media_%s_%s_%s",
                (string) $author_id,
                date("Ymd-His"),
                bin2hex(random_bytes(8))
            );
            
            $target_destination = USER_POST_ATTACHMENT_DIR($author_id);

            $server_destination = $target_destination . "/" . $newFilename;
            $browser_destination = USER_POST_ATTACHMENT_URL_BASE($author_id) . "/" . $newFilename;

            if(!is_dir($target_destination)) 
                mkdir($target_destination, 0777, true);


            if(isImage($fileExt)){

                $gd_file = getimagesize($fileTmp);

                if(!$gd_file)
                    throw new HttpException(400, "Invalid image.");
                
                $img = null;

                match($gd_file[2]){

                    IMAGETYPE_JPEG => $img = imagecreatefromjpeg($fileTmp),
                    IMAGETYPE_PNG  => $img = imagecreatefrompng($fileTmp),
                    IMAGETYPE_GIF  => $img = imagecreatefromgif($fileTmp),
                    IMAGETYPE_WEBP => $img = imagecreatefromwebp($fileTmp),

                    IMAGETYPE_AVIF => function_exists("imagecreatefromavif") 
                                      ? $img = imagecreatefromavif($fileTmp) 
                                      : throw new HttpException(500, "Unsupported image file extension."),

                    default => throw new HttpException(500, "Unsupported image mime.")
                };

                imagepalettetotruecolor($img);
                imagealphablending($img, false);
                imagesavealpha($img, true);

                $fileExt = "webp";

                if(!imagewebp($img, $server_destination . "." . $fileExt, NEWPOST_IMAGE_WEBP_COMPRESS_QUALITY))
                    throw new HttpException(500, "Unable to save uploaded image.");

                imagedestroy($img);
                
            }
            elseif(isVideo($fileExt)){

                if(!move_uploaded_file($fileTmp, $server_destination . "." . $fileExt))
                    throw new HttpException(500, "Failed to move uploaded file.");
            }

        }

        $this->postModel->insertPost( 
            $author_id, 
            $category, 
            $title, 
            $description, 
            $newFilename === null && $fileExt === null ? null : $newFilename . "." . $fileExt, 
            $fileExt, 
            $browser_destination === null && $fileExt === null ? null : $browser_destination . "." . $fileExt
        );
    }   

    public function validateEditPost( int $issuer_id, 
                                      int $post_id,
                                      string $category,
                                      string $title, 
                                      ?string $description, 
                                      ): void
    {
        $post = $this->postModel->getPostById( $post_id );

        if(!$post)
            throw new HttpException(404, "Post not found.");

        if($post["author_id"] !== $issuer_id)
            throw new HttpException(403, "Forbidden operation");

        $this->postModel->updatePost(
            $issuer_id,
            $post_id,
            $category,
            $title,
            $description
        );
    }

    public function validateDeletePost( array $issuer, int $post_id ): void
    {
        $post = $this->postModel->getPostById($post_id, false);

        if(!$post)
            throw new HttpException(404, "Post not found.");

        if($post["author_id"] !== $issuer["id"] && $issuer["role"] !== ROLE_ADMIN)
        {
            throw new HttpException(403, "Forbidden operation.");
        }

        $file_url = APP_ROOT() . "/" . $post["file_url"];

        if(!empty($post["file_url"]) && !empty($post["file_ext"]) && file_exists($file_url)){ 
            
            unlink($file_url);
        }

        $this->postModel->deletePost($post_id);
    }


}