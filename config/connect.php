<?php 

declare(strict_types=1);

class Database{

    public function __construct(
        private string $db_host,
        private string $db_name,
        private string $db_username,
        private string $db_password,

        protected ?PDO $conn = null
    ){    }

    public function connect(): PDO{

        if($this->conn === null)
        {
            try{
                $this->conn = new PDO(
                    "mysql:host=$this->db_host;dbname=$this->db_name;charset=utf8mb4", $this->db_username, $this->db_password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );

                echo "success";

            }
            catch(PDOException $e){
                throw new \Exception($e->getMessage());
            }
        }

        return $this->conn;

    }
}