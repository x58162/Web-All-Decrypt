```php
<?php

//==================================================
// All-Decrypt Web Push
//==================================================
//
// 用途：
// admin.php 新增內容成功後
// 呼叫本檔案發送 Web Push
//
// ★ 重要
// 本檔案會依照 lottery_type
// 自動產生正確的彩種網址
//
// dailycash
// https://all-decrypt.com/index.php?lottery=dailycash
//
// biglottery
// https://all-decrypt.com/index.php?lottery=biglottery
//
// fantasy5
// https://all-decrypt.com/index.php?lottery=fantasy5
//
// marksix
// https://all-decrypt.com/index.php?lottery=marksix
//
//==================================================


//==================================================
// JSON Header
//==================================================

header(
    "Content-Type: application/json; charset=utf-8"
);


//==================================================
// 只允許 POST
//==================================================

if (
    ($_SERVER["REQUEST_METHOD"] ?? "")
    !==
    "POST"
)
{
    http_response_code(405);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "Method Not Allowed"
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// Database
//==================================================

require_once __DIR__ . "/config.php";


//==================================================
// Composer
//==================================================

$autoload =
    __DIR__
    .
    DIRECTORY_SEPARATOR
    .
    "vendor"
    .
    DIRECTORY_SEPARATOR
    .
    "autoload.php";


if (
    !is_file(
        $autoload
    )
)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "找不到 vendor/autoload.php"
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


require_once $autoload;


//==================================================
// VAPID 設定
//==================================================

$pushConfig =
    __DIR__
    .
    DIRECTORY_SEPARATOR
    .
    "push_config.php";


if (
    !is_file(
        $pushConfig
    )
)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "找不到 push_config.php"
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


require_once $pushConfig;


//==================================================
// WebPush Class
//==================================================

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;


//==================================================
// 檢查 VAPID
//==================================================

if (
    !defined("VAPID_SUBJECT")
    ||
    !defined("VAPID_PUBLIC_KEY")
    ||
    !defined("VAPID_PRIVATE_KEY")
)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "VAPID 設定不完整"
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// 網站網址
//==================================================
//
// ★ 全站固定使用這個網址
//
//==================================================

$siteUrl =
    "https://all-decrypt.com";


//==================================================
// 彩票種類
//==================================================
//
// value
// = admin.php / Database 使用的 lottery_type
//
// name
// = 通知標題顯示名稱
//
// url
// = 點擊通知後要前往的完整 HTTPS 網址
//
//==================================================

$lotteryTypes =
[
    "dailycash" =>
    [
        "name" =>
            "今彩539",

        "url" =>
            $siteUrl
            .
            "/index.php?lottery=dailycash"
    ],


    "biglottery" =>
    [
        "name" =>
            "大樂透",

        "url" =>
            $siteUrl
            .
            "/index.php?lottery=biglottery"
    ],


    "fantasy5" =>
    [
        "name" =>
            "美國天天樂",

        "url" =>
            $siteUrl
            .
            "/index.php?lottery=fantasy5"
    ],


    "marksix" =>
    [
        "name" =>
            "六合彩",

        "url" =>
            $siteUrl
            .
            "/index.php?lottery=marksix"
    ]
];


//==================================================
// 取得 POST JSON
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


//==================================================
// JSON 不是陣列
// 改用一般 POST
//==================================================

if (
    !is_array(
        $data
    )
)
{
    $data =
        $_POST;
}


//==================================================
// 取得標題
//==================================================

$label =
    trim(
        $data["label"] ?? ""
    );


//==================================================
// 檢查標題
//==================================================

if (
    $label === ""
)
{
    http_response_code(400);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "通知標題不可為空白"
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// 限制標題長度
//==================================================

if (
    mb_strlen(
        $label,
        "UTF-8"
    )
    >
    255
)
{
    $label =
        mb_substr(
            $label,
            0,
            255,
            "UTF-8"
        );
}


//==================================================
// 取得 lottery_type
//==================================================
//
// ★ 這就是之前缺少的部分
//
// admin.php 傳：
// lottery_type=marksix
//
//==================================================

$lotteryType =
    trim(
        $data["lottery_type"] ?? ""
    );


//==================================================
// 檢查彩票種類
//==================================================

if (
    !isset(
        $lotteryTypes[
            $lotteryType
        ]
    )
)
{
    http_response_code(400);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "找不到正確的彩票種類",

            "lottery_type" =>
                $lotteryType
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// 取得彩票名稱
//==================================================

$lotteryName =
    $lotteryTypes[
        $lotteryType
    ]["name"];


//==================================================
// 取得完整網址
//==================================================
//
// ★ 這裡會產生：
//
// marksix
// ↓
// https://all-decrypt.com/index.php?lottery=marksix
//
//==================================================

$lotteryUrl =
    $lotteryTypes[
        $lotteryType
    ]["url"];


//==================================================
// Debug Log
//==================================================

error_log(
    "[Push] Lottery Type: "
    .
    $lotteryType
    .
    " | Lottery Name: "
    .
    $lotteryName
    .
    " | URL: "
    .
    $lotteryUrl
);


//==================================================
// VAPID
//==================================================

$vapid =
[
    "subject" =>
        VAPID_SUBJECT,

    "publicKey" =>
        VAPID_PUBLIC_KEY,

    "privateKey" =>
        VAPID_PRIVATE_KEY
];


//==================================================
// 建立 WebPush
//==================================================

try
{
    $webPush =
        new WebPush(
            [
                "VAPID" =>
                    $vapid
            ]
        );
}
catch (
    Throwable $e
)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "WebPush 建立失敗："
                .
                $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// 讀取所有 Push 訂閱
//==================================================

try
{
    $statement =
        $pdo->query(
            "
            SELECT
                id,
                endpoint,
                p256dh,
                auth

            FROM push_subscriptions

            ORDER BY id ASC
            "
        );


    $subscriptions =
        $statement->fetchAll(
            PDO::FETCH_ASSOC
        );
}
catch (
    PDOException $e
)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "讀取 Push 訂閱失敗："
                .
                $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// 沒有訂閱
//==================================================

if (
    count(
        $subscriptions
    )
    ===
    0
)
{
    echo json_encode(
        [
            "success" =>
                true,

            "message" =>
                "目前沒有 Push 訂閱",

            "lottery_type" =>
                $lotteryType,

            "lottery_name" =>
                $lotteryName,

            "url" =>
                $lotteryUrl,

            "total" =>
                0,

            "sent" =>
                0,

            "failed" =>
                0,

            "removed" =>
                0
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// Push Payload
//==================================================
//
// ★ 非常重要
//
// 這裡會直接把完整網址送給 sw.js
//
//==================================================

$payloadArray =
[
    //==================================================
    // 通知標題
    //
    // 例如：
    // marksix → 白小姐
    //==================================================

    "title" =>
        $lotteryName,


    //==================================================
    // 通知內容
    //
    // 例如：
    // label = 今日開獎
    //==================================================

    "body" =>
        $label,


    //==================================================
    // Icon
    //==================================================

    "icon" =>
        "/favicon.ico",


    //==================================================
    // Badge
    //==================================================

    "badge" =>
        "/favicon.ico",


    //==================================================
    // ★ 彩種
    //==================================================

    "lottery_type" =>
        $lotteryType,


    //==================================================
    // ★ 彩種名稱
    //==================================================

    "lottery_name" =>
        $lotteryName,


    //==================================================
    // ★ 完整網址
    //
    // 例如：
    //
    // https://all-decrypt.com/index.php?lottery=marksix
    //
    //==================================================

    "url" =>
        $lotteryUrl,


    //==================================================
    // 時間
    //==================================================

    "timestamp" =>
        time()
];


//==================================================
// JSON Encode
//==================================================

try
{
    $payload =
        json_encode(
            $payloadArray,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
            |
            JSON_THROW_ON_ERROR
        );
}
catch (
    Throwable $e
)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "建立 Push JSON 失敗："
                .
                $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// Debug Payload
//==================================================

error_log(
    "[Push] Payload: "
    .
    $payload
);


//==================================================
// 統計
//==================================================

$total =
    count(
        $subscriptions
    );


$sent =
    0;


$failed =
    0;


$removed =
    0;


$errors =
    [];


$queuedEndpoints =
    [];


//==================================================
// 建立並加入 Queue
//==================================================

foreach (
    $subscriptions
    as $row
)
{

    try
    {

        //==================================================
        // 檢查必要資料
        //==================================================

        if (
            empty(
                $row["endpoint"]
            )
            ||
            empty(
                $row["p256dh"]
            )
            ||
            empty(
                $row["auth"]
            )
        )
        {
            $failed++;

            $errors[] =
                [
                    "id" =>
                        intval(
                            $row["id"]
                        ),

                    "message" =>
                        "Push Subscription 資料不完整"
                ];

            continue;
        }


        //==================================================
        // Subscription
        //==================================================

        $subscription =
            Subscription::create(
                [
                    "endpoint" =>
                        $row["endpoint"],

                    "keys" =>
                    [
                        "p256dh" =>
                            $row["p256dh"],

                        "auth" =>
                            $row["auth"]
                    ]
                ]
            );


        //==================================================
        // Queue
        //==================================================

        $webPush->queueNotification(
            $subscription,
            $payload
        );


        //==================================================
        // 記錄 Endpoint
        //
        // flush 回傳時用來找 subscription id
        //
        //==================================================

        $queuedEndpoints[] =
            [
                "id" =>
                    intval(
                        $row["id"]
                    ),

                "endpoint" =>
                    $row["endpoint"]
            ];

    }
    catch (
        Throwable $e
    )
    {

        $failed++;

        $errors[] =
            [
                "id" =>
                    intval(
                        $row["id"]
                    ),

                "message" =>
                    $e->getMessage()
            ];
    }

}


//==================================================
// 沒有成功加入任何 Queue
//==================================================

if (
    count(
        $queuedEndpoints
    )
    ===
    0
)
{
    echo json_encode(
        [
            "success" =>
                true,

            "message" =>
                "Push 沒有可發送的訂閱",

            "title" =>
                $label,

            "lottery_type" =>
                $lotteryType,

            "lottery_name" =>
                $lotteryName,

            "url" =>
                $lotteryUrl,

            "total" =>
                $total,

            "sent" =>
                $sent,

            "failed" =>
                $failed,

            "removed" =>
                $removed,

            "errors" =>
                $errors
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// 執行發送
//==================================================

try
{
    $reports =
        $webPush->flush();
}
catch (
    Throwable $e
)
{
    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "Push flush 失敗："
                .
                $e->getMessage(),

            "lottery_type" =>
                $lotteryType,

            "url" =>
                $lotteryUrl
        ],
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


//==================================================
// 逐筆處理結果
//==================================================

foreach (
    $reports
    as $report
)
{

    //==================================================
    // Endpoint
    //==================================================

    $endpoint =
        "";


    try
    {
        $endpoint =
            $report
            ->getRequest()
            ->getUri()
            ->__toString();
    }
    catch (
        Throwable $e
    )
    {
        $endpoint =
            "";
    }


    //==================================================
    // 找 subscription id
    //==================================================

    $subscriptionId =
        null;


    foreach (
        $queuedEndpoints
        as $queued
    )
    {

        if (
            $queued["endpoint"]
            ===
            $endpoint
        )
        {
            $subscriptionId =
                $queued["id"];

            break;
        }

    }


    //==================================================
    // 成功
    //==================================================

    if (
        $report->isSuccess()
    )
    {
        $sent++;

        continue;
    }


    //==================================================
    // 失敗
    //==================================================

    $failed++;


    //==================================================
    // HTTP Status
    //==================================================

    $status =
        null;


    try
    {
        $response =
            $report->getResponse();


        if (
            $response !== null
        )
        {
            $status =
                $response->getStatusCode();
        }
    }
    catch (
        Throwable $e
    )
    {
        //==================================================
        // 忽略
        //==================================================
    }


    //==================================================
    // 404 / 410
    //
    // Push Subscription 已失效
    // 自動刪除
    //==================================================

    if (
        $status === 404
        ||
        $status === 410
    )
    {

        if (
            $subscriptionId !== null
        )
        {

            try
            {
                $delete =
                    $pdo->prepare(
                        "
                        DELETE FROM
                            push_subscriptions

                        WHERE id = :id
                        "
                    );


                $delete->execute(
                    [
                        ":id" =>
                            $subscriptionId
                    ]
                );


                if (
                    $delete->rowCount()
                    >
                    0
                )
                {
                    $removed++;
                }
            }
            catch (
                PDOException $e
            )
            {
                $errors[] =
                    [
                        "id" =>
                            $subscriptionId,

                        "status" =>
                            $status,

                        "message" =>
                            "刪除失效訂閱失敗："
                            .
                            $e->getMessage()
                    ];
            }

        }

    }


    //==================================================
    // Error Reason
    //==================================================

    $reason =
        "";


    try
    {
        $reason =
            $report->getReason();
    }
    catch (
        Throwable $e
    )
    {
        $reason =
            $e->getMessage();
    }


    if (
        $reason === ""
    )
    {
        $reason =
            "未知錯誤";
    }


    //==================================================
    // 錯誤記錄
    //==================================================

    $errors[] =
        [
            "id" =>
                $subscriptionId,

            "status" =>
                $status,

            "reason" =>
                $reason
        ];

}


//==================================================
// 最終結果
//==================================================

echo json_encode(
    [
        "success" =>
            true,

        "message" =>
            "Push 發送完成",

        //==================================================
        // 標題
        //==================================================

        "title" =>
            $label,

        //==================================================
        // 彩票種類
        //==================================================

        "lottery_type" =>
            $lotteryType,

        //==================================================
        // 彩票名稱
        //==================================================

        "lottery_name" =>
            $lotteryName,

        //==================================================
        // ★ 最終通知網址
        //==================================================

        "url" =>
            $lotteryUrl,

        //==================================================
        // 統計
        //==================================================

        "total" =>
            $total,

        "sent" =>
            $sent,

        "failed" =>
            $failed,

        "removed" =>
            $removed,

        //==================================================
        // 錯誤
        //==================================================

        "errors" =>
            $errors
    ],
    JSON_UNESCAPED_UNICODE
    |
    JSON_UNESCAPED_SLASHES
);

?>
```
