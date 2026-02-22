<?php
class Database {
    private static $host = "mysql80.r2.websupport.sk:3314";
    private static $db_name = "tizaPDT";
    private static $username = "tizaPDT";
    private static $password = "Jd7@VMZ@dg";
    private static $conn = null;

    public static function connect() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . 
                    ";dbname=" . self::$db_name . 
                    ";charset=utf8mb4", 
                    self::$username, 
                    self::$password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC 
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500); 
                die("DB CHYBA: " . $e->getMessage());
            }
        }
        return self::$conn;
    }
}
?>
