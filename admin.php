
<?php

//==================================================
// All-Decrypt 管理後台
//==================================================


//==================================================
// 管理者 IP
//==================================================

$adminIPs =
[
    "127.0.0.1",
    "::1",
    "2407:4b00:4b04:0:9cfa:d1b6:db:8c7f"
];


//==================================================
// Cloudflare 真實 IP
//==================================================

if (
    isset($_SERVER["HTTP_CF_CONNECTING_IP"])
    &&
    $_SERVER["HTTP_CF_CONNECTING_IP"] !== ""
)
{
    $clientIP =
        trim(
            $_SERVER["HTTP_CF_CONNECTING_IP"]
        );
}
else
{
    $clientIP =
        trim(
            $_SERVER["REMOTE_ADDR"] ?? ""
        );
}


//==================================================
// IP 驗證
//==================================================

if (
    !in_array(
        $clientIP,
        $adminIPs,
        true
    )
)
{
    http_response_code(403);

    die("403 Forbidden");
}


//==================================================
// Database
//==================================================

require_once __DIR__ . "/config.php";


//==================================================
// Web Push 設定
//==================================================

require_once __DIR__ . "/push_config.php";


//==================================================
// Composer
//==================================================

$autoloadFile =
    __DIR__
    . DIRECTORY_SEPARATOR
    . "vendor"
    . DIRECTORY_SEPARATOR
    . "autoload.php";


if (
    !is_file(
        $autoloadFile
    )
)
{
    die(
        "找不到 Composer autoload.php："
        .
        htmlspecialchars(
            $autoloadFile,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


require_once $autoloadFile;


//==================================================
// Web Push Class
//==================================================

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;


//==================================================
// 網站主網址
//==================================================

$siteUrl =
    "https://all-decrypt.com";


//==================================================
// Push 圖示
//==================================================

$pushIcon =
    $siteUrl
    .
    "/favicon.ico";


//==================================================
// Upload 資料夾
//==================================================

$uploadDirectory =
    __DIR__
    . DIRECTORY_SEPARATOR
    . "uploads";


if (
    !is_dir(
        $uploadDirectory
    )
)
{
    mkdir(
        $uploadDirectory,
        0777,
        true
    );
}


//==================================================
// 彩票種類
//
// value = Database lottery_type
// name  = 顯示名稱
// url   = Push 點擊後網址
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
// 訊息
//==================================================

$message = "";

$error = "";

$pushMessage = "";

$pushError = "";


//==================================================
// 發送 Web Push
//==================================================

function sendPushNotification(
    PDO $pdo,
    string $lotteryName,
    string $contentLabel,
    string $lotteryType,
    array $lotteryTypes,
    string $pushIcon
): array
{

    $result =
    [
        "success" =>
            0,

        "failed" =>
            0,

        "expired" =>
            0,

        "total" =>
            0,

        "errors" =>
            []
    ];


    //==================================================
    // 檢查彩票種類
    //==================================================

    if (
        !isset(
            $lotteryTypes[$lotteryType]
        )
    )
    {
        $result["errors"][] =
            "找不到彩票種類。";

        return $result;
    }


    //==================================================
    // 取得彩票網址
    //==================================================

    $lotteryUrl =
        $lotteryTypes[
            $lotteryType
        ]["url"];


    //==================================================
    // 取得所有 Push 訂閱
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
        Throwable $e
    )
    {
        $result["errors"][] =
            "讀取 Push 訂閱失敗："
            .
            $e->getMessage();

        return $result;
    }


    $result["total"] =
        count(
            $subscriptions
        );


    //==================================================
    // 沒有訂閱
    //==================================================

    if (
        $result["total"] === 0
    )
    {
        $result["errors"][] =
            "目前沒有任何 Push 訂閱。";

        return $result;
    }


    //==================================================
    // VAPID
    //==================================================

    $vapid =
    [
        "VAPID" =>
        [
            "subject" =>
                VAPID_SUBJECT,

            "publicKey" =>
                VAPID_PUBLIC_KEY,

            "privateKey" =>
                VAPID_PRIVATE_KEY
        ]
    ];


    //==================================================
    // 建立 WebPush
    //==================================================

    try
    {
        $webPush =
            new WebPush(
                $vapid
            );
    }
    catch (
        Throwable $e
    )
    {
        $result["errors"][] =
            "建立 WebPush 失敗："
            .
            $e->getMessage();

        return $result;
    }


    //==================================================
    // ★ Push Payload
    //
    // 注意：
    //
    // 不再使用：
    //
    // "data" =>
    // [
    //     "url" => ...
    // ]
    //
    // 改成最外層直接傳：
    //
    // title
    // body
    // lottery_type
    // url
    //
    //==================================================

    $payloadArray =
    [
        "title" =>
            $lotteryName,

        "body" =>
            $contentLabel,

        "lottery_type" =>
            $lotteryType,

        "url" =>
            $lotteryUrl,

        "icon" =>
            $pushIcon,

        "badge" =>
            $pushIcon,

        "timestamp" =>
            time()
    ];


    //==================================================
    // JSON
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
        $result["errors"][] =
            "建立 Push JSON 失敗："
            .
            $e->getMessage();

        return $result;
    }


    //==================================================
    // Debug Log
    //==================================================

    error_log(
        "[All-Decrypt Push]"
        .
        " Lottery Type: "
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
        .
        " | Payload: "
        .
        $payload
    );


    //==================================================
    // 逐筆發送
    //==================================================

    foreach (
        $subscriptions
        as $row
    )
    {

        try
        {

            //==================================================
            // 建立 Subscription
            //==================================================

            $subscription =
                Subscription::create(
                    [
                        "endpoint" =>
                            $row["endpoint"],

                        "publicKey" =>
                            $row["p256dh"],

                        "authToken" =>
                            $row["auth"]
                    ]
                );


            //==================================================
            // 發送
            //==================================================

            $report =
                $webPush->sendOneNotification(
                    $subscription,
                    $payload
                );


            //==================================================
            // 成功
            //==================================================

            if (
                $report->isSuccess()
            )
            {
                $result["success"]++;

                continue;
            }


            //==================================================
            // HTTP Status
            //==================================================

            $statusCode =
                null;


            try
            {
                if (
                    $report->getResponse()
                    !==
                    null
                )
                {
                    $statusCode =
                        $report
                        ->getResponse()
                        ->getStatusCode();
                }
            }
            catch (
                Throwable $e
            )
            {
                // 忽略
            }


            //==================================================
            // 404 / 410
            //
            // 訂閱失效
            //==================================================

            if (
                $statusCode === 404
                ||
                $statusCode === 410
            )
            {

                try
                {
                    $deleteStatement =
                        $pdo->prepare(
                            "
                            DELETE FROM
                                push_subscriptions

                            WHERE id = :id
                            "
                        );


                    $deleteStatement->execute(
                        [
                            ":id" =>
                                $row["id"]
                        ]
                    );


                    if (
                        $deleteStatement->rowCount()
                        >
                        0
                    )
                    {
                        $result["expired"]++;
                    }
                }
                catch (
                    Throwable $e
                )
                {
                    $result["failed"]++;

                    $result["errors"][] =
                        "清除失效訂閱失敗："
                        .
                        $e->getMessage();
                }


                continue;
            }


            //==================================================
            // 其他錯誤
            //==================================================

            $reason =
                $report->getReason();


            if (
                $reason === ""
            )
            {
                $reason =
                    "未知錯誤";
            }


            if (
                $statusCode !== null
            )
            {
                $reason =
                    "HTTP "
                    .
                    $statusCode
                    .
                    "："
                    .
                    $reason;
            }


            $result["failed"]++;

            $result["errors"][] =
                $reason;
        }
        catch (
            Throwable $e
        )
        {
            $result["failed"]++;

            $result["errors"][] =
                $e->getMessage();
        }

    }


    return $result;
}


//==================================================
// POST
//==================================================

if (
    $_SERVER["REQUEST_METHOD"]
    ===
    "POST"
)
{

    $action =
        $_POST["action"] ?? "";


    //==================================================
    // 新增
    //==================================================

    if (
        $action === "add"
    )
    {

        $label =
            trim(
                $_POST["label"] ?? ""
            );


        $lotteryType =
            trim(
                $_POST["lottery_type"] ?? ""
            );


        //==================================================
        // 標題
        //==================================================

        if (
            $label === ""
        )
        {
            $error =
                "請輸入標題。";
        }


        //==================================================
        // 彩票種類
        //==================================================

        elseif (
            !array_key_exists(
                $lotteryType,
                $lotteryTypes
            )
        )
        {
            $error =
                "請選擇正確的彩票種類。";
        }


        //==================================================
        // 圖片
        //==================================================

        elseif (
            !isset(
                $_FILES["image"]
            )
            ||
            $_FILES["image"]["error"]
            !==
            UPLOAD_ERR_OK
        )
        {
            $error =
                "請選擇圖片。";
        }


        else
        {

            $file =
                $_FILES["image"];


            //==================================================
            // 檔案大小
            //==================================================

            if (
                $file["size"]
                >
                20 * 1024 * 1024
            )
            {
                $error =
                    "圖片不可超過 20MB。";
            }

            else
            {

                //==================================================
                // 副檔名
                //==================================================

                $extension =
                    strtolower(
                        pathinfo(
                            $file["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                //==================================================
                // 允許格式
                //==================================================

                $allowedExtensions =
                [
                    "jpg",
                    "jpeg",
                    "png",
                    "gif",
                    "webp"
                ];


                if (
                    !in_array(
                        $extension,
                        $allowedExtensions,
                        true
                    )
                )
                {
                    $error =
                        "只允許 JPG、JPEG、PNG、GIF、WEBP。";
                }

                else
                {

                    //==================================================
                    // JPEG 統一 JPG
                    //==================================================

                    if (
                        $extension === "jpeg"
                    )
                    {
                        $extension =
                            "jpg";
                    }


                    //==================================================
                    // 建立隨機檔名
                    //==================================================

                    try
                    {
                        $filename =
                            bin2hex(
                                random_bytes(16)
                            )
                            .
                            "."
                            .
                            $extension;
                    }
                    catch (
                        Throwable $e
                    )
                    {
                        $filename =
                            "";

                        $error =
                            "建立圖片檔名失敗："
                            .
                            $e->getMessage();
                    }


                    if (
                        $filename !== ""
                    )
                    {

                        $target =
                            $uploadDirectory
                            .
                            DIRECTORY_SEPARATOR
                            .
                            $filename;


                        $relativePath =
                            "uploads/"
                            .
                            $filename;


                        //==================================================
                        // 上傳
                        //==================================================

                        if (
                            move_uploaded_file(
                                $file["tmp_name"],
                                $target
                            )
                        )
                        {

                            try
                            {

                                //==================================================
                                // Database
                                //==================================================

                                $stmt =
                                    $pdo->prepare(
                                        "
                                        INSERT INTO contents
                                        (
                                            label,
                                            lottery_type,
                                            image,
                                            status,
                                            is_enabled
                                        )
                                        VALUES
                                        (
                                            :label,
                                            :lottery_type,
                                            :image,
                                            1,
                                            0
                                        )
                                        "
                                    );


                                $stmt->execute(
                                    [
                                        ":label" =>
                                            $label,

                                        ":lottery_type" =>
                                            $lotteryType,

                                        ":image" =>
                                            $relativePath
                                    ]
                                );


                                //==================================================
                                // 新增成功
                                //==================================================

                                $message =
                                    "新增成功。";


                                //==================================================
                                // 彩票名稱
                                //==================================================

                                $lotteryName =
                                    $lotteryTypes[
                                        $lotteryType
                                    ]["name"];


                                //==================================================
                                // 發送 Push
                                //==================================================

                                $pushResult =
                                    sendPushNotification(
                                        $pdo,
                                        $lotteryName,
                                        $label,
                                        $lotteryType,
                                        $lotteryTypes,
                                        $pushIcon
                                    );


                                //==================================================
                                // Push 結果
                                //==================================================

                                if (
                                    $pushResult["total"]
                                    ===
                                    0
                                )
                                {
                                    $pushError =
                                        "新增成功，但目前沒有 Push 訂閱。";
                                }
                                else
                                {

                                    $pushMessage =
                                        "通知處理完成："
                                        .
                                        "成功 "
                                        .
                                        $pushResult["success"]
                                        .
                                        " 筆";


                                    //==================================================
                                    // 清除失效訂閱
                                    //==================================================

                                    if (
                                        $pushResult["expired"]
                                        >
                                        0
                                    )
                                    {
                                        $pushMessage .=
                                            "，清除失效訂閱 "
                                            .
                                            $pushResult["expired"]
                                            .
                                            " 筆";
                                    }


                                    //==================================================
                                    // 失敗
                                    //==================================================

                                    if (
                                        $pushResult["failed"]
                                        >
                                        0
                                    )
                                    {
                                        $pushMessage .=
                                            "，失敗 "
                                            .
                                            $pushResult["failed"]
                                            .
                                            " 筆";
                                    }


                                    //==================================================
                                    // 錯誤
                                    //==================================================

                                    if (
                                        count(
                                            $pushResult["errors"]
                                        )
                                        >
                                        0
                                    )
                                    {
                                        $pushError =
                                            implode(
                                                " | ",
                                                $pushResult["errors"]
                                            );
                                    }
                                }
                            }
                            catch (
                                Throwable $e
                            )
                            {

                                //==================================================
                                // Database 錯誤
                                //==================================================

                                $error =
                                    "資料庫處理失敗："
                                    .
                                    $e->getMessage();


                                //==================================================
                                // 刪除已上傳圖片
                                //==================================================

                                if (
                                    is_file(
                                        $target
                                    )
                                )
                                {
                                    unlink(
                                        $target
                                    );
                                }
                            }
                        }
                        else
                        {
                            $error =
                                "圖片上傳失敗。";
                        }
                    }
                }
            }
        }
    }


    //==================================================
    // 顯示 / 隱藏
    //==================================================

    elseif (
        $action === "toggle"
    )
    {

        $id =
            intval(
                $_POST["id"] ?? 0
            );


        if (
            $id > 0
        )
        {

            $stmt =
                $pdo->prepare(
                    "
                    UPDATE contents

                    SET
                        status =
                            IF(
                                status = 1,
                                0,
                                1
                            )

                    WHERE id = :id
                    "
                );


            $stmt->execute(
                [
                    ":id" =>
                        $id
                ]
            );


            $message =
                "顯示狀態更新成功。";
        }
    }


    //==================================================
    // 0 / 1 / 2
    //
    // 0 = 等待
    // 1 = 順開
    // 2 = 未開
    //
    // 0 → 1 → 2 → 0
    //==================================================

    elseif (
        $action === "toggle_enabled"
    )
    {

        $id =
            intval(
                $_POST["id"] ?? 0
            );


        if (
            $id > 0
        )
        {

            $stmt =
                $pdo->prepare(
                    "
                    UPDATE contents

                    SET
                        is_enabled =
                            CASE

                                WHEN is_enabled = 0
                                THEN 1

                                WHEN is_enabled = 1
                                THEN 2

                                WHEN is_enabled = 2
                                THEN 0

                                ELSE 0

                            END

                    WHERE id = :id
                    "
                );


            $stmt->execute(
                [
                    ":id" =>
                        $id
                ]
            );


            $message =
                "0 / 1 / 2 狀態更新成功。";
        }
    }


    //==================================================
    // 刪除
    //==================================================

    elseif (
        $action === "delete"
    )
    {

        $id =
            intval(
                $_POST["id"] ?? 0
            );


        if (
            $id > 0
        )
        {

            //==================================================
            // 取得圖片
            //==================================================

            $stmt =
                $pdo->prepare(
                    "
                    SELECT
                        image

                    FROM contents

                    WHERE id = :id
                    "
                );


            $stmt->execute(
                [
                    ":id" =>
                        $id
                ]
            );


            $row =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                $row
            )
            {

                //==================================================
                // 刪除 Database
                //==================================================

                $stmt =
                    $pdo->prepare(
                        "
                        DELETE FROM contents

                        WHERE id = :id
                        "
                    );


                $stmt->execute(
                    [
                        ":id" =>
                            $id
                    ]
                );


                //==================================================
                // 刪除圖片
                //==================================================

                $imageFile =
                    __DIR__
                    .
                    DIRECTORY_SEPARATOR
                    .
                    str_replace(
                        "/",
                        DIRECTORY_SEPARATOR,
                        $row["image"]
                    );


                if (
                    is_file(
                        $imageFile
                    )
                )
                {
                    unlink(
                        $imageFile
                    );
                }


                $message =
                    "刪除成功。";
            }
        }
    }
}


//==================================================
// 讀取全部資料
//==================================================

$stmt =
    $pdo->query(
        "
        SELECT
            id,
            label,
            lottery_type,
            image,
            status,
            is_enabled,
            created_at

        FROM contents

        ORDER BY id DESC
        "
    );


$items =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

?>

<!DOCTYPE html>

<html lang="zh-Hant">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="
        width=device-width,
        initial-scale=1.0
    "
>

<title>
管理後台
</title>

<link
    rel="stylesheet"
    href="style.css"
>

<style>

/*==================================================
  基本文字
==================================================*/

body
{
    color: #222222;
}

.page
{
    color: #222222;
}

.admin-card
{
    color: #222222;
}

.admin-card h2
{
    color: #222222;
}

.admin-card label
{
    color: #222222;
}

.admin-info
{
    color: #222222;
}

.admin-info strong
{
    color: #222222;
}

.admin-info span
{
    color: #555555;
}

.site-title
{
    color: #222222;
}


/*==================================================
  狀態
==================================================*/

.enabled-status
{
    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-top: 4px;

    color: #222222;
}

.enabled-status span
{
    color: #555555;
}

.enabled-status strong
{
    color: #222222;
}


/*==================================================
  彩票種類
==================================================*/

.lottery-type
{
    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 4px;

    font-size: 14px;

    color: #222222;
}

.lottery-type span
{
    color: #555555;
}

.lottery-type-name
{
    font-weight: bold;

    color: #007bff !important;
}


/*==================================================
  彩票選單
==================================================*/

.lottery-type-select
{
    width: 100%;

    padding:
        10px
        12px;

    margin-bottom: 15px;

    border:
        1px solid
        #d1d5db;

    border-radius: 6px;

    background: #ffffff;

    color: #222222 !important;

    font-family:
        Arial,
        "Microsoft JhengHei",
        sans-serif;

    font-size: 15px;

    box-sizing: border-box;

    cursor: pointer;
}

.lottery-type-select option
{
    background: #ffffff;

    color: #222222;
}

.lottery-type-select:focus
{
    outline: none;

    border-color: #007bff;

    box-shadow:
        0
        0
        0
        2px
        rgba(
            0,
            123,
            255,
            0.15
        );
}


/*==================================================
  0 / 1 / 2 按鈕
==================================================*/

.enabled-button
{
    border: none;

    border-radius: 6px;

    padding:
        5px
        14px;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

    color: #ffffff !important;

    transition:
        opacity 0.2s ease,
        transform 0.1s ease;
}

.enabled-button.waiting
{
    background: #6c757d;
}

.enabled-button.enabled
{
    background: #28a745;
}

.enabled-button.disabled
{
    background: #dc3545;
}

.enabled-button:hover
{
    opacity: 0.85;
}

.enabled-button:active
{
    transform: scale(0.96);
}


/*==================================================
  操作
==================================================*/

.admin-actions
{
    display: flex;

    flex-wrap: wrap;

    gap: 8px;
}

.admin-actions form
{
    margin: 0;
}


/*==================================================
  Push 成功
==================================================*/

.push-message
{
    margin:
        10px
        0;

    padding:
        12px
        15px;

    border-radius: 8px;

    background: #e8f7ed;

    color: #176b32 !important;

    font-weight: bold;
}


/*==================================================
  Push 錯誤
==================================================*/

.push-error
{
    margin:
        10px
        0;

    padding:
        12px
        15px;

    border-radius: 8px;

    background: #fff0f0;

    color: #b00020 !important;

    font-weight: bold;

    word-break: break-word;
}


/*==================================================
  Message
==================================================*/

.message
{
    color: #176b32 !important;
}


/*==================================================
  Error
==================================================*/

.error-message
{
    color: #b00020 !important;
}


/*==================================================
  輸入框
==================================================*/

input[type="text"]
{
    color: #222222 !important;

    background: #ffffff !important;
}


/*==================================================
  手機
==================================================*/

@media (max-width: 600px)
{

    .admin-actions
    {
        width: 100%;

        display: flex;

        flex-wrap: wrap;

        gap: 8px;
    }

    .admin-actions form
    {
        flex:
            0 0 auto;
    }

    .enabled-button
    {
        min-height: 38px;

        padding:
            7px
            13px;

        font-size: 14px;
    }

}

</style>

</head>


<body>

<div class="page">


<!--==================================================
     Header
==================================================-->

<header class="site-header">

<div class="site-title">

管理後台

</div>


<a
    href="https://all-decrypt.com/index.php"
    class="admin-button"
>

返回首頁

</a>

</header>


<!--==================================================
     Message
==================================================-->

<?php if (
    $message !== ""
): ?>

<div class="message">

<?= htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php endif; ?>


<?php if (
    $error !== ""
): ?>

<div class="error-message">

<?= htmlspecialchars(
    $error,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php endif; ?>


<!--==================================================
     Push 結果
==================================================-->

<?php if (
    $pushMessage !== ""
): ?>

<div class="push-message">

<?= htmlspecialchars(
    $pushMessage,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php endif; ?>


<?php if (
    $pushError !== ""
): ?>

<div class="push-error">

Push 發送失敗：

<?= htmlspecialchars(
    $pushError,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php endif; ?>


<!--==================================================
     新增內容
==================================================-->

<section class="admin-card">

<h2>
    新增內容
</h2>


<form
    method="post"
    enctype="multipart/form-data"
>

<input
    type="hidden"
    name="action"
    value="add"
>


<!--==================================================
     標題
==================================================-->

<label>
    標題
</label>


<input
    type="text"
    name="label"
    maxlength="255"
    required
>


<!--==================================================
     彩票種類
==================================================-->

<label>
    彩票種類
</label>


<select
    name="lottery_type"
    class="lottery-type-select"
    required
>

<option
    value=""
    selected
    disabled
>
    請選擇彩票種類
</option>


<?php foreach (
    $lotteryTypes
    as $typeValue =>
    $typeData
): ?>

<option
    value="<?= htmlspecialchars(
        $typeValue,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<?= htmlspecialchars(
    $typeData["name"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</option>

<?php endforeach; ?>

</select>


<!--==================================================
     圖片
==================================================-->

<label>
    圖片
</label>


<input
    type="file"
    name="image"
    accept="
        .jpg,
        .jpeg,
        .png,
        .gif,
        .webp
    "
    required
>


<button
    type="submit"
    class="primary-button"
>

新增

</button>


</form>

</section>


<!--==================================================
     內容管理
==================================================-->

<section class="admin-card">

<h2>
    內容管理
</h2>


<?php if (
    count($items) > 0
): ?>


<div class="admin-list">


<?php foreach (
    $items
    as $row
): ?>


<div class="admin-row">


<!--==================================================
     圖片
==================================================-->

<div class="admin-preview">

<img
    src="<?= htmlspecialchars(
        $row["image"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    alt=""
>

</div>


<!--==================================================
     資訊
==================================================-->

<div class="admin-info">


<strong>

<?= htmlspecialchars(
    $row["label"],
    ENT_QUOTES,
    "UTF-8"
) ?>

</strong>


<span>

ID：

<?= intval(
    $row["id"]
) ?>

</span>


<!--==================================================
     彩票種類
==================================================-->

<div class="lottery-type">

<span>
    彩票種類：
</span>


<strong class="lottery-type-name">

<?php

$currentLotteryType =
    trim(
        $row["lottery_type"] ?? ""
    );


if (
    $currentLotteryType !== ""
    &&
    isset(
        $lotteryTypes[
            $currentLotteryType
        ]
    )
)
{
    echo htmlspecialchars(
        $lotteryTypes[
            $currentLotteryType
        ]["name"],
        ENT_QUOTES,
        "UTF-8"
    );
}
elseif (
    $currentLotteryType !== ""
)
{
    echo htmlspecialchars(
        $currentLotteryType,
        ENT_QUOTES,
        "UTF-8"
    );
}
else
{
    echo "未設定";
}

?>

</strong>

</div>


<!--==================================================
     顯示狀態
==================================================-->

<span>

顯示狀態：

<?= $row["status"]
    ? "顯示"
    : "隱藏"
?>

</span>


<!--==================================================
     0 / 1 / 2
==================================================-->

<div class="enabled-status">

<span>
    狀態：
</span>


<strong>

<?php

$currentEnabled =
    intval(
        $row["is_enabled"]
    );


if (
    $currentEnabled === 0
)
{
    echo "0 - 等待";
}
elseif (
    $currentEnabled === 1
)
{
    echo "1 - 順開";
}
elseif (
    $currentEnabled === 2
)
{
    echo "2 - 未開";
}
else
{
    echo "0 - 等待";
}

?>

</strong>

</div>


</div>


<!--==================================================
     操作
==================================================-->

<div class="admin-actions">


<!--==================================================
     顯示 / 隱藏
==================================================-->

<form
    method="post"
>

<input
    type="hidden"
    name="action"
    value="toggle"
>


<input
    type="hidden"
    name="id"
    value="<?= intval(
        $row["id"]
    ) ?>"
>


<button
    type="submit"
>

<?= $row["status"]
    ? "隱藏"
    : "顯示"
?>

</button>

</form>


<!--==================================================
     0 / 1 / 2
==================================================-->

<form
    method="post"
>

<input
    type="hidden"
    name="action"
    value="toggle_enabled"
>


<input
    type="hidden"
    name="id"
    value="<?= intval(
        $row["id"]
    ) ?>"
>


<button
    type="submit"
    class="enabled-button

<?php

if (
    intval(
        $row["is_enabled"]
    ) === 0
)
{
    echo " waiting";
}
elseif (
    intval(
        $row["is_enabled"]
    ) === 1
)
{
    echo " enabled";
}
elseif (
    intval(
        $row["is_enabled"]
    ) === 2
)
{
    echo " disabled";
}
else
{
    echo " waiting";
}

?>

"
>

<?php

if (
    intval(
        $row["is_enabled"]
    ) === 0
)
{
    echo "0 等待";
}
elseif (
    intval(
        $row["is_enabled"]
    ) === 1
)
{
    echo "1 順開";
}
elseif (
    intval(
        $row["is_enabled"]
    ) === 2
)
{
    echo "2 未開";
}
else
{
    echo "0 等待";
}

?>

</button>

</form>


<!--==================================================
     刪除
==================================================-->

<form
    method="post"
    onsubmit="
        return confirm(
            '確定要刪除這筆內容嗎？'
        );
    "
>

<input
    type="hidden"
    name="action"
    value="delete"
>


<input
    type="hidden"
    name="id"
    value="<?= intval(
        $row["id"]
    ) ?>"
>


<button
    type="submit"
    class="danger-button"
>

刪除

</button>

</form>


</div>


</div>


<?php endforeach; ?>


</div>


<?php else: ?>


<div class="empty-message">

目前沒有內容。

</div>


<?php endif; ?>


</section>


</div>


</body>

</html>
