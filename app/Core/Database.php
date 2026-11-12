<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) return self::$connection;
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']);
        self::$connection = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        self::$connection->exec("SET time_zone = '+00:00'");
        return self::$connection;
    }
    private function __construct() {}
}
