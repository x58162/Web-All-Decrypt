<?php

//==================================================
// JSON
//==================================================

header(
    "Content-Type: application/json; charset=utf-8"
);


//==================================================
// Database
//==================================================

require_once __DIR__ . "/config.php";


//==================================================
// POST
//==================================================

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
)
{
    http_response_code(405);

    echo json_encode(
        [
            "success" => false,
            "message" => "Method Not Allowed"
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


//==================================================
// 取得 JSON
//==================================================

$raw =
    file_get_contents(
        "php://input"
    );


$data =
    json_decode(
        $raw,
        true
    );


if (
    !is_array($data)
)
{
    http_response_code(400);

    echo json_encode(
        [
            "success" => false,
            "message" => "無效 JSON"
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


//==================================================
// 取得 subscription
//==================================================

$endpoint =
    trim(
        $data["endpoint"] ?? ""
    );


$p256dh =
    trim(
        $data["keys"]["p256dh"] ?? ""
    );


$auth =
    trim(
        $data["keys"]["auth"] ?? ""
    );


if (
    $endpoint === ""
    ||
    $p256dh === ""
    ||
    $auth === ""
)
{
    http_response_code(400);

    echo json_encode(
        [
            "success" => false,
            "message" => "Push Subscription 資料不完整"
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


//==================================================
// 儲存
//==================================================

try
{
    $sql = "
        INSERT INTO push_subscriptions
        (
            endpoint,
            p256dh,
            auth
        )
        VALUES
        (
            :endpoint,
            :p256dh,
            :auth
        )
        ON DUPLICATE KEY UPDATE
            p256dh = VALUES(p256dh),
            auth = VALUES(auth),
            updated_at = CURRENT_TIMESTAMP
    ";


    $statement =
        $pdo->prepare(
            $sql
        );


    $statement->execute(
        [
            ":endpoint" =>
                $endpoint,

            ":p256dh" =>
                $p256dh,

            ":auth" =>
                $auth
        ]
    );


    echo json_encode(
        [
            "success" => true,
            "message" => "訂閱成功"
        ],
        JSON_UNESCAPED_UNICODE
    );
}
catch (PDOException $e)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" => false,
            "message" =>
                "資料庫錯誤："
                .
                $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
}
