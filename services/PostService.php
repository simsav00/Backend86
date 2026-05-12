<?php
declare(strict_types=1);

use Exceptions\HttpException;

class PostService{

    public function __construct(
        private PostModel $postModel
    ){}

    public function getPosts(int $offset, ?string $category = null): array
    {

        if(!$category || strtolower(trim($category)) === "all"){
            $posts = $this->postModel->getPosts(20, $offset);
        }
        else{
            $posts = $this->postModel->getPostsByCategory($category, 20, $offset);

            if(!$posts)
                throw new HttpException(404, "No matching posts.");
        }

        return $posts;
    }

    public function getPost( int $id ): array
    {

        $post = $this->postModel->getPostById($id);

        if(!$post)
            throw new HttpException(404, "Post not found.");

        return $post;
    }

    public function validateNewPost( int $author_id, string $title, ?string $description, string $category, ?array $file  ):void
    {
        $title = trim($title);
        $description = $description ? trim($description) : null;
        $category = strtolower(trim($category));

        if($title === "")
            throw new HttpException(400, "Title cannot be empty.");

        if(strlen($title) < 3 || strlen($title) > 192)
            throw new HttpException(400, "Title must be 3-192 in character length.");

        if($description && strlen($description) > 16384)
            throw new HttpException(400, "Description cannot exceed 16384 characters.");

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
                throw new HttpException(400, "An error ocurred while uploading file.");

            if(
               !isImage($fileExt) && !isVideo($fileExt) ||
               isImage($fileExt) && !isImageMime($fileMime) ||
               isVideo($fileExt) && !isVideoMime($fileMime)
            ){
                throw new HttpException(400, "Only image and video are allowed for uploads.");
            }

            if($fileSize > 20 * 1048576)
                throw new HttpException(413, "File too large, max file size is 20MB.");


            $newFilename = sprintf(
                "media_%s_%s_%s.",
                (string) $author_id,
                date("Ymd-His"),
                bin2hex(random_bytes(8))
            );
            
            $target_destination = USER_POST_ATTACHMENT_DIR($author_id);

            $server_destination = $target_destination . "/" . $newFilename;
            $browser_destination = USER_POST_ATTACHMENT_URL_BASE($author_id) . "/" . $newFilename;

            if(!is_dir($target_destination)) 
                mkdir($target_destination, 0777, true);


            if(!empty($file["name"]) && isImageMime($fileMime)){

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

                if(!imagewebp($img, $server_destination . ".webp", 76))
                    throw new HttpException(500, "Unable to save uploaded image.");

                imagedestroy($img);
                
            }
            elseif(isVideoMime($fileMime)){

                if(!move_uploaded_file($fileTmp, $server_destination . ".$fileExt"))
                    throw new HttpException(500, "Failed to move uploaded file.");
            }

        }

        $this->postModel->insertPost( 
            $author_id, 
            $category, 
            $title, 
            $description, 
            $newFilename, 
            $fileExt, 
            $browser_destination
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

    public function validateDeletePost( int $issuer_id, int $post_id ): void
    {
        $post = $this->postModel->getPostById($post_id);

        if(!$post)
            throw new HttpException(404, "Post not found.");

        if($post["author_id"] !== $issuer_id)
            throw new HttpException(403, "Forbidden operation.");

        $file_url = APP_ROOT() . "/" . $post["file_url"];

        if(!empty($file_url) && file_exists($file_url))
            unlink($file_url);

        $this->postModel->deletePost($issuer_id, $post_id);
    }


}