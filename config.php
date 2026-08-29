<?php

//==================================================
// MySQL 設定
//==================================================

$servername = "localhost";

$username = "root";

$password = "Ya48653om";

$dbname = "content_site";


//==================================================
// PDO 建立 MySQL 連線
//==================================================

try
{
    $pdo = new PDO(
        "mysql:host=$servername;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false
        ]
    );
}
catch (PDOException $e)
{
    die(
        "MySQL 連線失敗："
        .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


//==================================================
// 建立 Database
//==================================================

try
{
    $pdo->exec(
        "
        CREATE DATABASE IF NOT EXISTS
        `$dbname`

        CHARACTER SET utf8mb4

        COLLATE utf8mb4_unicode_ci
        "
    );
}
catch (PDOException $e)
{
    die(
        "建立 Database 失敗："
        .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


//==================================================
// 連接 Database
//==================================================

try
{
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false
        ]
    );
}
catch (PDOException $e)
{
    die(
        "選擇 Database 失敗："
        .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


//==================================================
// 建立 contents
//==================================================

try
{
    $pdo->exec(
        "
        CREATE TABLE IF NOT EXISTS contents
        (
            id INT UNSIGNED NOT NULL
                AUTO_INCREMENT,

            label VARCHAR(255) NOT NULL,

            image VARCHAR(500) NOT NULL,

            status TINYINT(1) NOT NULL
                DEFAULT 1,

            is_enabled TINYINT(1) NOT NULL
                DEFAULT 0,

            created_at DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            INDEX idx_status (status),

            INDEX idx_is_enabled (is_enabled)

        )
        ENGINE=InnoDB

        DEFAULT CHARSET=utf8mb4

        COLLATE=utf8mb4_unicode_ci
        "
    );
}
catch (PDOException $e)
{
    die(
        "建立 contents 資料表失敗："
        .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


//==================================================
// 建立 visitor_stats
//==================================================

try
{
    $pdo->exec(
        "
        CREATE TABLE IF NOT EXISTS visitor_stats
        (
            id BIGINT UNSIGNED NOT NULL
                AUTO_INCREMENT,

            ip_address VARCHAR(45) NOT NULL,

            visit_count BIGINT UNSIGNED NOT NULL
                DEFAULT 1,

            first_visit DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            last_visit DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            UNIQUE KEY uk_ip_address
            (
                ip_address
            )

        )
        ENGINE=InnoDB

        DEFAULT CHARSET=utf8mb4

        COLLATE=utf8mb4_unicode_ci
        "
    );
}
catch (PDOException $e)
{
    die(
        "建立 visitor_stats 資料表失敗："
        .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}

?>
