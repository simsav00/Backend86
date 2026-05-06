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

    public function getPost( int $id, bool $withComments = true ): array
    {

        $post = $this->postModel->getPostById($id);

        if(!$post)
            throw new HttpException(404, "Post not found.");

        $comments = $this->postModel->getPostCommentsById($id);

        if($withComments && $comments){
            
            $post = [
                ...$post,
                "comments" => $comments
            ];
        }

        return $post;
    }

    public function validateNewPost( int $author_id, string $title, ?string $description, string $category, ?array $file,  ):void
    {
        $title = trim($title);
        $description = trim($description);
        $category = strtolower(trim($category));

        if($title === "")
            throw new HttpException(400, "Title cannot be empty.");

        if(strlen($title) < 3 || strlen($title) > 192)
            throw new HttpException(400, "Title must be 3-192 in character length.");

        if(strlen($description) > 16384)
            throw new HttpException(400, "Description cannot exceed 16384 characters.");


        if(!empty($file["name"])){

            $fileName   = $file["name"];
            $fileExt    = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileTmp    = $file["tmp_name"];
            $fileSize   = $file["size"];
            $fileError  = $file["error"];

            $newFilename = sprintf(
                "media_%s_%s_%s.%s",
                (string) $author_id,
                date("Ymd-His"),
                bin2hex(random_bytes(8)),
                $fileExt
            );

            $destination = USER_POST_ATTACHMENT_DIR($author_id). "/" . $newFilename;

            if($fileError !== UPLOAD_ERR_OK)
                throw new HttpException(500, "An error ocurred while uploading file.");

            if(!isImage($fileExt) && !isVideo($fileExt))
                throw new HttpException(400, "Only image and video files can be uploaded.");

            if($fileSize > 20 * 1048576)
                throw new HttpException(413, "File too large, max file size is 20MB.");

            if(!is_dir($destination)) 
                mkdir($destination);

            if(!move_uploaded_file($fileTmp, $destination))
                throw new HttpException(500, "Failed to move uploaded file.");

        }

        $this->postModel->insertPost( 
            $author_id, 
            $category, 
            $title, 
            $description, 
            $newFilename ?? null, 
            $fileExt ?? null, 
            (($description ?? null) !== null) ? $destination : null
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