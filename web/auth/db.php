<?php
/**
 * db.php — returns a shared mysqli connection for the Archspace auth service.
 *
 * Connection parameters are read from environment variables so that no
 * credentials live in version-controlled source files:
 *
 *   DB_HOST  (default: 127.0.0.1)
 *   DB_USER  (default: root)
 *   DB_PASS  (default: comconq1)
 *   DB_NAME  (default: Archspace)
 *
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $db = db();          // returns the same mysqli instance every call
 */

declare(strict_types=1);

/**
 * Returns (and lazily creates) a shared mysqli connection.
 *
 * @throws RuntimeException if the connection cannot be established.
 */
function db(): mysqli
{
    static $conn = null;

    if ($conn !== null) {
        return $conn;
    }

    $host = (string)(getenv('DB_HOST') ?: '127.0.0.1');
    $user = (string)(getenv('DB_USER') ?: 'root');
    $pass = (string)(getenv('DB_PASS') ?: 'comconq1');
    $name = (string)(getenv('DB_NAME') ?: 'Archspace');

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli($host, $user, $pass, $name);
    } catch (mysqli_sql_exception $e) {
        throw new RuntimeException(
            'Database connection failed: ' . $e->getMessage(),
            (int)$e->getCode(),
            $e
        );
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
