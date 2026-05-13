<?php
declare(strict_types=1);

use Exceptions\HttpException;

class UserController{

    public function __construct(
        private UserService $userService,
        private AuthService $authService
    ){}

    public function newAvatar(): void
    {
        try{
            if(!isset($_FILES["avatar"]))
                throw new HttpException(400, "Image is required.");

            $issuer = $this->authService->getUserInfo();
            
            $this->userService->validateAvatar(
                $issuer["id"], FILES("avatar")
            );

            respond(0, 200, "Avatar changed successfully.");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function newBio(): void
    {
        try{
            $issuer = $this->authService->getUserInfo();

            $this->userService->validateBio(
                $issuer["id"], POST("bio")
            );

            respond(0, 200,"Bio changed successfully.");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }
}