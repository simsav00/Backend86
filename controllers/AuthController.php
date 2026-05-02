<?php
declare(strict_types=1);
use Exceptions\HttpException;

class AuthController{

    public function __construct(
        private AuthService $authService
    )
    {}

    public function me(): void
    {
        try{
            $user = $this->authService->getUserInfo();

            respond(0, 200, $user);
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }

    }

    public function logout(): void
    {
        try{
            $this->authService->dropSession();

            respond(0, 200, "User is loggout.");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function register(): void
    {
        try{
            $this->authService->validateRegister(
                POST("username"), POST("password")
            );

            respond(0, 201, "User created!");
        }
        catch(HttpException $e){
            respond(1, $e->getStatusCode(), $e->getMessage());  
        }
    }

    public function login(): void
    {
    
        try{
            $this->authService->validateLogin(
                POST("username"), POST("password")
            );

            respond(0, 200, "User logged in!");
        }   
        catch(HttpException $e){
            
            respond(1, $e->getStatusCode(), $e->getMessage());
        }
    }
}

