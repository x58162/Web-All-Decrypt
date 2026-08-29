<?php

//==================================================
// Database
//==================================================

require_once __DIR__ . "/config.php";


//==================================================
// Push Config
//==================================================

require_once __DIR__ . "/push_config.php";


//==================================================
// Web Push Database Check API
//==================================================

if (
    isset($_GET["push_check"])
    &&
    $_GET["push_check"] === "1"
)
{
    header(
        "Content-Type: application/json; charset=utf-8"
    );

    try
    {
        $rawInput =
            file_get_contents("php://input");


        $data =
            json_decode(
                $rawInput,
                true
            );


        $endpoint =
            isset($data["endpoint"])
            ? trim($data["endpoint"])
            : "";


        if (
            $endpoint === ""
        )
        {
            echo json_encode(
                [
                    "success" => false,
                    "exists" => false,
                    "message" => "缺少 endpoint"
                ],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

            exit;
        }


        $pushCheckStatement =
            $pdo->prepare(
                "
                SELECT
                    id

                FROM push_subscriptions

                WHERE endpoint = :endpoint

                LIMIT 1
                "
            );


        $pushCheckStatement->execute(
            [
                ":endpoint" =>
                    $endpoint
            ]
        );


        $pushRecord =
            $pushCheckStatement->fetch(
                PDO::FETCH_ASSOC
            );


        echo json_encode(
            [
                "success" => true,

                "exists" =>
                    $pushRecord
                    ? true
                    : false
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

    }
    catch (
        Throwable $e
    )
    {
        http_response_code(500);


        echo json_encode(
            [
                "success" => false,

                "exists" => false,

                "message" => "資料庫檢查失敗"
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }


    exit;
}


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
// 取得真實 IP
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
// 管理者判斷
//==================================================

$isAdmin =
    in_array(
        $clientIP,
        $adminIPs,
        true
    );


//==================================================
// 記錄訪客瀏覽次數
//==================================================

if (
    $clientIP !== ""
)
{
    try
    {
        $checkStatement =
            $pdo->prepare(
                "
                SELECT
                    id

                FROM visitor_stats

                WHERE ip_address = :ip

                LIMIT 1
                "
            );


        $checkStatement->execute(
            [
                ":ip" =>
                    $clientIP
            ]
        );


        $visitor =
            $checkStatement->fetch();


        if (
            $visitor
        )
        {
            $updateStatement =
                $pdo->prepare(
                    "
                    UPDATE visitor_stats

                    SET
                        visit_count =
                            visit_count + 1,

                        last_visit =
                            CURRENT_TIMESTAMP

                    WHERE id = :id
                    "
                );


            $updateStatement->execute(
                [
                    ":id" =>
                        $visitor["id"]
                ]
            );
        }
        else
        {
            $insertStatement =
                $pdo->prepare(
                    "
                    INSERT INTO visitor_stats
                    (
                        ip_address,
                        visit_count
                    )

                    VALUES
                    (
                        :ip,
                        1
                    )
                    "
                );


            $insertStatement->execute(
                [
                    ":ip" =>
                        $clientIP
                ]
            );
        }
    }
    catch (
        PDOException $e
    )
    {
        // 統計失敗不影響網站
    }
}


//==================================================
// 預設彩種
//==================================================

$lotteryType =
    trim(
        $_GET["lottery"] ?? "dailycash"
    );


//==================================================
// 允許的彩種
//==================================================

$lotteryTypes =
[
    "dailycash" =>
    [
        "name" =>
            "今彩539",

        "short_name" =>
            "539",

        "title" =>
            "539版路高機率",

        "description" =>
            "539版路高機率，提供今彩539開獎結果、近期歷史資料、落球、順球與路數分析，快速查看539開獎資料，開獎單等下載。文姐539 阿立539 超群539 阿珠媽539 大船入港539 阿正539 阿憲539",

        "og_description" =>
            "今彩539開獎結果、落球、順球與路數資料分析。"
    ],

    "biglottery" =>
    [
        "name" =>
            "大樂透",

        "short_name" =>
            "大樂透",

        "title" =>
            "大樂透版路高機率",

        "description" =>
            "大樂透版路高機率，提供大樂透開獎結果、近期歷史資料、落球、順球與路數分析。",

        "og_description" =>
            "大樂透開獎結果、落球、順球與路數資料分析。"
    ],

    "fantasy5" =>
    [
        "name" =>
            "天天樂",

        "short_name" =>
            "天天樂",

        "title" =>
            "天天樂版路高機率",

        "description" =>
            "天天樂版路高機率，提供天天樂開獎結果、近期歷史資料、落球、順球與路數分析。",

        "og_description" =>
            "天天樂開獎結果、落球、順球與路數資料分析。"
    ],

    "marksix" =>
    [
        "name" =>
            "六合彩",

        "short_name" =>
            "六合彩",

        "title" =>
            "六合彩版路高機率",

        "description" =>
            "六合彩版路高機率，提供六合彩開獎結果、近期歷史資料、落球、順球與路數分析。",

        "og_description" =>
            "六合彩開獎結果、落球、順球與路數資料分析。"
    ]
];


//==================================================
// 不存在的彩種回到 dailycash
//==================================================

if (
    !isset(
        $lotteryTypes[$lotteryType]
    )
)
{
    $lotteryType =
        "dailycash";
}


//==================================================
// 目前彩種
//==================================================

$currentLottery =
    $lotteryTypes[
        $lotteryType
    ];


//==================================================
// SEO
//==================================================

$siteTitle =
    $currentLottery["title"];

$siteDescription =
    $currentLottery["description"];

$ogDescription =
    $currentLottery["og_description"];


//==================================================
// 每頁 20 筆
//==================================================

$itemsPerPage = 20;


//==================================================
// 目前頁數
//==================================================

$page =
    isset($_GET["page"])
    ? intval($_GET["page"])
    : 1;


if (
    $page < 1
)
{
    $page = 1;
}


//==================================================
// 總筆數
//==================================================

$countStatement =
    $pdo->prepare(
        "
        SELECT
            COUNT(*)

        FROM contents

        WHERE status = 1

        AND lottery_type = :lottery_type
        "
    );


$countStatement->execute(
    [
        ":lottery_type" =>
            $lotteryType
    ]
);


$totalItems =
    intval(
        $countStatement->fetchColumn()
    );


//==================================================
// 總頁數
//==================================================

$totalPages = 0;


if (
    $totalItems > 0
)
{
    $totalPages =
        (int)ceil(
            $totalItems /
            $itemsPerPage
        );
}


//==================================================
// 修正頁數
//==================================================

if (
    $totalPages > 0
    &&
    $page > $totalPages
)
{
    $page =
        $totalPages;
}


//==================================================
// OFFSET
//==================================================

$offset =
    (
        $page - 1
    )
    *
    $itemsPerPage;


//==================================================
// 取得內容
//==================================================

$sql = "
SELECT
    id,
    label,
    image,
    lottery_type,
    is_enabled,
    created_at

FROM contents

WHERE status = 1

AND lottery_type = :lottery_type

ORDER BY id DESC

LIMIT :limit

OFFSET :offset
";


$statement =
    $pdo->prepare(
        $sql
    );


$statement->bindValue(
    ":lottery_type",
    $lotteryType,
    PDO::PARAM_STR
);


$statement->bindValue(
    ":limit",
    $itemsPerPage,
    PDO::PARAM_INT
);


$statement->bindValue(
    ":offset",
    $offset,
    PDO::PARAM_INT
);


$statement->execute();


$items =
    $statement->fetchAll(
        PDO::FETCH_ASSOC
    );


//==================================================
// SEO Canonical
//==================================================

$canonicalUrl =
    "https://all-decrypt.com/index.php";


if (
    $lotteryType !== "dailycash"
)
{
    $canonicalUrl .=
        "?lottery=" .
        rawurlencode(
            $lotteryType
        );

    if (
        $page > 1
    )
    {
        $canonicalUrl .=
            "&page=" .
            $page;
    }
}
else
{
    if (
        $page > 1
    )
    {
        $canonicalUrl .=
            "?page=" .
            $page;
    }
}


//==================================================
// JSON-LD
//==================================================

$websiteSchema =
[
    "@context" =>
        "https://schema.org",

    "@type" =>
        "WebSite",

    "name" =>
        $siteTitle,

    "url" =>
        $canonicalUrl,

    "description" =>
        $siteDescription
];

?>

<!DOCTYPE html>

<html lang="zh-Hant">

<head>
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>

<?= htmlspecialchars(
    $siteTitle,
    ENT_QUOTES,
    "UTF-8"
) ?>

<?php if (
    $page > 1
): ?>

｜第 <?= $page ?> 頁

<?php endif; ?>

</title>


<meta
    name="description"
    content="<?= htmlspecialchars(
        $siteDescription,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<meta
    name="robots"
    content="index, follow"
>


<link
    rel="canonical"
    href="<?= htmlspecialchars(
        $canonicalUrl,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<meta
    property="og:type"
    content="website"
>


<meta
    property="og:title"
    content="<?= htmlspecialchars(
        $siteTitle,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<meta
    property="og:description"
    content="<?= htmlspecialchars(
        $ogDescription,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<meta
    property="og:url"
    content="<?= htmlspecialchars(
        $canonicalUrl,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<meta
    property="og:site_name"
    content="539版路高機率"
>


<script type="application/ld+json">

<?= json_encode(
    $websiteSchema,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_PRETTY_PRINT
) ?>

</script>


<link
    rel="stylesheet"
    href="style.css"
>


<style>

/*==================================================
  Body
==================================================*/

body
{
    margin: 0;

    background: #1a1717;

    padding: 0;

    box-sizing: border-box;
}


/*==================================================
  Header
==================================================*/

.site-header
{
    position: relative;

    width: 100%;

    box-sizing: border-box;
}


/*==================================================
  Label
==================================================*/

.label-wrapper
{
    position: relative;

    width: 100%;

    display: block;
}


.label-enabled-status
{
    position: absolute;

    left: -30px;

    top: 20px;

    z-index: 20;

    display: block;

    padding:
        4px
        9px;

    border-radius: 4px;

    color: #ffffff;

    font-size: 22px;

    font-weight: bold;

    line-height: 1;

    pointer-events: none;

    box-sizing: border-box;

    white-space: nowrap;
}


.label-enabled-status.waiting
{
    background: #6c757d;
}


.label-enabled-status.enabled
{
    background: #28a745;
}


.label-enabled-status.disabled
{
    background: #dc3545;
}


/*==================================================
  Content Label
==================================================*/

.content-label
{
    position: relative;

    width: 100%;
}


.content-label .label-text
{
    display: block;

    padding-left: 65px;
}


.content-item
{
    overflow: visible !important;
}


/*==================================================
  通知彈出視窗遮罩
==================================================*/

.push-modal-overlay
{
    position: fixed;

    inset: 0;

    z-index: 999999;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    background:
        rgba(
            0,
            0,
            0,
            0.65
        );

    opacity: 0;

    visibility: hidden;

    transition:
        opacity 0.25s ease,
        visibility 0.25s ease;

    box-sizing: border-box;
}


.push-modal-overlay.show
{
    opacity: 1;

    visibility: visible;
}


.push-modal
{
    width: 100%;

    max-width: 420px;

    padding: 30px 25px 25px;

    border-radius: 16px;

    background: #ffffff;

    box-shadow:
        0
        15px
        50px
        rgba(
            0,
            0,
            0,
            0.35
        );

    text-align: center;

    box-sizing: border-box;

    transform:
        translateY(15px)
        scale(0.96);

    transition:
        transform 0.25s ease;
}


.push-modal-overlay.show .push-modal
{
    transform:
        translateY(0)
        scale(1);
}


/*==================================================
  通知圖示
==================================================*/

.push-modal-icon
{
    width: 70px;

    height: 70px;

    margin:
        0
        auto
        15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #007bff;

    color: #ffffff;

    font-size: 34px;
}


/*==================================================
  通知標題
==================================================*/

.push-modal-title
{
    margin:
        0
        0
        10px;

    color: #222222;

    font-size: 23px;

    font-weight: bold;

    line-height: 1.4;
}


/*==================================================
  通知內容
==================================================*/

.push-modal-text
{
    margin:
        0
        0
        22px;

    color: #555555;

    font-size: 15px;

    line-height: 1.7;
}


/*==================================================
  通知按鈕區
==================================================*/

.push-modal-actions
{
    display: flex;

    gap: 10px;

    width: 100%;
}


/*==================================================
  稍後
==================================================*/

.push-modal-later
{
    flex: 1;

    min-height: 46px;

    border:
        1px
        solid
        #d1d5db;

    border-radius: 8px;

    background: #ffffff;

    color: #555555;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;
}


.push-modal-later:hover
{
    background: #f3f4f6;
}


/*==================================================
  開啟通知
==================================================*/

.push-modal-enable
{
    flex: 1;

    min-height: 46px;

    border:
        1px
        solid
        #007bff;

    border-radius: 8px;

    background: #007bff;

    color: #ffffff;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;
}


.push-modal-enable:hover
{
    background: #0056b3;

    border-color: #0056b3;
}


.push-modal-enable:disabled,
.push-modal-later:disabled
{
    opacity: 0.6;

    cursor: not-allowed;
}


/*==================================================
  Push Status
==================================================*/

.push-status
{
    position: fixed;

    left: 50%;

    bottom: 25px;

    z-index: 1000000;

    width: max-content;

    max-width:
        calc(
            100vw - 30px
        );

    padding:
        11px
        16px;

    border-radius: 8px;

    background:
        rgba(
            0,
            0,
            0,
            0.88
        );

    color: #ffffff;

    font-size: 14px;

    line-height: 1.5;

    box-sizing: border-box;

    transform:
        translateX(-50%)
        translateY(20px);

    opacity: 0;

    visibility: hidden;

    transition:
        opacity 0.2s ease,
        transform 0.2s ease,
        visibility 0.2s ease;
}


.push-status.show
{
    opacity: 1;

    visibility: visible;

    transform:
        translateX(-50%)
        translateY(0);
}


/*==================================================
  Mobile
==================================================*/

@media (max-width: 600px)
{

    .label-enabled-status
    {
        left: 0;

        top: 5px;

        font-size: 15px;

        padding:
            4px
            7px;
    }


    .content-label .label-text
    {
        padding-left: 55px;

        font-size: 14px;
    }


    .push-modal-overlay
    {
        padding: 15px;

        align-items: center;
    }


    .push-modal
    {
        max-width: 360px;

        padding:
            25px
            18px
            20px;

        border-radius: 14px;
    }


    .push-modal-icon
    {
        width: 60px;

        height: 60px;

        font-size: 29px;
    }


    .push-modal-title
    {
        font-size: 20px;
    }


    .push-modal-text
    {
        font-size: 14px;

        line-height: 1.6;
    }


    .push-modal-actions
    {
        gap: 8px;
    }


    .push-modal-later,
    .push-modal-enable
    {
        min-height: 44px;

        font-size: 14px;
    }


    .push-status
    {
        bottom: 15px;
    }

}

</style>


<script
    async
    src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8646014509722763"
    crossorigin="anonymous">
</script>


</head>


<body>


<div class="page">


<!--==================================================
     Header
==================================================-->

<header class="site-header">


<?php

require_once __DIR__ . "/menu.php";

?>


<!--==================================================
     管理
==================================================-->

<?php if (
    $isAdmin
): ?>

<a
    href="admin.php"
    class="admin-button"
>

管理

</a>

<?php endif; ?>


</header>


<!--==================================================
     Contents
==================================================-->

<main class="content-list">


<?php if (
    count($items) > 0
): ?>


<?php foreach (
    $items
    as $row
): ?>


<?php

$label =
    htmlspecialchars(
        $row["label"],
        ENT_QUOTES,
        "UTF-8"
    );


$image =
    htmlspecialchars(
        $row["image"],
        ENT_QUOTES,
        "UTF-8"
    );


$isEnabled =
    intval(
        $row["is_enabled"]
    );


if (
    $isEnabled === 0
)
{
    $enabledText =
        "等待";

    $enabledClass =
        "waiting";
}
elseif (
    $isEnabled === 1
)
{
    $enabledText =
        "順開";

    $enabledClass =
        "enabled";
}
elseif (
    $isEnabled === 2
)
{
    $enabledText =
        "未開";

    $enabledClass =
        "disabled";
}
else
{
    $enabledText =
        "未開";

    $enabledClass =
        "disabled";
}

?>


<div class="content-item">


<div class="label-wrapper">


<span
    class="
        label-enabled-status
        <?= $enabledClass ?>
    "
>

<?= $enabledText ?>

</span>


<button
    type="button"
    class="content-label"
    onclick="toggleContent(this)"
    aria-expanded="false"
>

<span class="label-text">

<?= $label ?>

</span>


<span class="label-arrow">

▶

</span>


</button>


</div>


<div class="image-panel">


<div class="image-inner">


<img
    src="<?= $image ?>"
    alt="<?= $label ?>"
    loading="lazy"
    decoding="async"
>


</div>


</div>


</div>


<?php endforeach; ?>


<?php else: ?>


<div class="empty-message">

目前沒有
<?= htmlspecialchars(
    $lotteryTypes[$lotteryType]["name"] ?? "此彩種",
    ENT_QUOTES,
    "UTF-8"
) ?>
的內容

</div>


<?php endif; ?>


</main>


<!--==================================================
     Pagination
==================================================-->

<?php if (
    $totalPages > 1
): ?>


<nav
    class="pagination"
    aria-label="內容頁面"
>


<?php if (
    $page > 1
): ?>

<a
    href="?lottery=<?= rawurlencode(
        $lotteryType
    ) ?>&page=<?= $page - 1 ?>"
    class="page-button"
    aria-label="上一頁"
>

‹

</a>

<?php endif; ?>


<?php

$pages = [];


if (
    $totalPages <= 9
)
{
    for (
        $i = 1;
        $i <= $totalPages;
        $i++
    )
    {
        $pages[] =
            $i;
    }
}
else
{
    $pages[] =
        1;


    if (
        $page > 4
    )
    {
        $pages[] =
            "...";
    }


    $start =
        max(
            2,
            $page - 2
        );


    $end =
        min(
            $totalPages - 1,
            $page + 2
        );


    for (
        $i = $start;
        $i <= $end;
        $i++
    )
    {
        $pages[] =
            $i;
    }


    if (
        $page <
        $totalPages - 3
    )
    {
        $pages[] =
            "...";
    }


    $pages[] =
        $totalPages;
}


foreach (
    $pages
    as $p
):


if (
    $p === "..."
):

?>

<span class="page-dots">

...

</span>

<?php else: ?>

<a
    href="?lottery=<?= rawurlencode(
        $lotteryType
    ) ?>&page=<?= $p ?>"
    class="
        page-button
        <?= ($p == $page)
            ? "active"
            : ""
        ?>
    "
    <?= ($p == $page)
        ? 'aria-current="page"'
        : ""
    ?>
>

<?= $p ?>

</a>

<?php endif; ?>


<?php endforeach; ?>


<?php if (
    $page < $totalPages
): ?>

<a
    href="?lottery=<?= rawurlencode(
        $lotteryType
    ) ?>&page=<?= $page + 1 ?>"
    class="page-button"
    aria-label="下一頁"
>

›

</a>

<?php endif; ?>


</nav>


<?php endif; ?>


</div>


<!--==================================================
     Push Modal
==================================================-->

<div
    id="push-modal-overlay"
    class="push-modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="push-modal-title"
>


<div class="push-modal">


<div class="push-modal-icon">

🔔

</div>


<h2
    id="push-modal-title"
    class="push-modal-title"
>

開啟網站通知

</h2>


<p class="push-modal-text">

有新的開獎資料或網站內容時，
我們可以第一時間通知你。

<br>

開啟通知後，就不用一直回來查看。

</p>


<div class="push-modal-actions">


<button
    type="button"
    id="push-modal-later"
    class="push-modal-later"
>

稍後再說

</button>


<button
    type="button"
    id="push-modal-enable"
    class="push-modal-enable"
>

🔔 開啟通知

</button>


</div>


</div>


</div>


<!--==================================================
     Push Status
==================================================-->

<div
    id="push-status"
    class="push-status"
    role="status"
    aria-live="polite"
>
</div>


<script>


//==================================================
// VAPID Public Key
//==================================================

const VAPID_PUBLIC_KEY =
    <?= json_encode(
        VAPID_PUBLIC_KEY,
        JSON_UNESCAPED_SLASHES
    ) ?>;


//==================================================
// Push Modal
//==================================================

const pushModalOverlay =
    document.getElementById(
        "push-modal-overlay"
    );


const pushModalLater =
    document.getElementById(
        "push-modal-later"
    );


const pushModalEnable =
    document.getElementById(
        "push-modal-enable"
    );


const pushStatus =
    document.getElementById(
        "push-status"
    );


//==================================================
// 顯示 Status
//==================================================

function showPushStatus(
    message,
    timeout = 4000
)
{

    pushStatus.textContent =
        message;


    pushStatus.classList.add(
        "show"
    );


    if (
        timeout > 0
    )
    {

        setTimeout(
            function()
            {

                pushStatus.classList.remove(
                    "show"
                );

            },
            timeout
        );

    }

}


//==================================================
// 開啟彈窗
//==================================================

function showPushModal()
{

    if (
        !pushModalOverlay
    )
    {
        return;
    }


    pushModalOverlay.classList.add(
        "show"
    );

}


//==================================================
// 關閉彈窗
//==================================================

function closePushModal()
{

    if (
        !pushModalOverlay
    )
    {
        return;
    }


    pushModalOverlay.classList.remove(
        "show"
    );

}


//==================================================
// Base64 URL
//==================================================

function urlBase64ToUint8Array(
    base64String
)
{

    const padding =
        "=".repeat(
            (
                4 -
                base64String.length % 4
            ) % 4
        );


    const base64 =
        (
            base64String +
            padding
        )
        .replace(
            /-/g,
            "+"
        )
        .replace(
            /_/g,
            "/"
        );


    const rawData =
        window.atob(
            base64
        );


    return Uint8Array.from(
        [...rawData].map(
            function(char)
            {

                return char.charCodeAt(0);

            }
        )
    );

}


//==================================================
// 儲存 Subscription
//
// 只有使用者按下「開啟通知」
// 且成功取得 Push Subscription 後
// 才會呼叫這裡。
//==================================================

async function savePushSubscription(
    subscription
)
{

    const subscriptionJSON =
        subscription.toJSON();


    const response =
        await fetch(
            "/subscribe.php",
            {
                method:
                    "POST",

                headers:
                {
                    "Content-Type":
                        "application/json"
                },

                body:
                    JSON.stringify(
                        subscriptionJSON
                    )
            }
        );


    if (
        !response.ok
    )
    {
        throw new Error(
            "subscribe.php HTTP " +
            response.status
        );
    }


    const result =
        await response.json();


    if (
        !result.success
    )
    {
        throw new Error(
            result.message ||
            "Subscription 儲存失敗"
        );
    }


    return result;

}


//==================================================
// 查詢資料庫
//==================================================

async function checkSubscriptionInDatabase(
    subscription
)
{

    if (
        !subscription
    )
    {
        return false;
    }


    const subscriptionJSON =
        subscription.toJSON();


    const endpoint =
        subscriptionJSON.endpoint;


    if (
        !endpoint
    )
    {
        return false;
    }


    const response =
        await fetch(
            "/index.php?push_check=1",
            {
                method:
                    "POST",

                headers:
                {
                    "Content-Type":
                        "application/json"
                },

                body:
                    JSON.stringify(
                        {
                            endpoint:
                                endpoint
                        }
                    )
            }
        );


    if (
        !response.ok
    )
    {
        throw new Error(
            "Push 資料庫檢查 HTTP " +
            response.status
        );
    }


    const result =
        await response.json();


    if (
        !result.success
    )
    {
        throw new Error(
            result.message ||
            "Push 資料庫檢查失敗"
        );
    }


    return result.exists === true;

}


//==================================================
// Service Worker
//
// 注意：
// 這裡只負責註冊 Service Worker。
// 不會因此寫入資料庫。
//==================================================

async function registerPushServiceWorker()
{

    if (
        !(
            "serviceWorker"
            in navigator
        )
    )
    {
        throw new Error(
            "此瀏覽器不支援 Service Worker"
        );
    }


    if (
        !(
            "PushManager"
            in window
        )
    )
    {
        throw new Error(
            "此瀏覽器不支援 Push API"
        );
    }


    if (
        !(
            "Notification"
            in window
        )
    )
    {
        throw new Error(
            "此瀏覽器不支援 Notification API"
        );
    }


    const registration =
        await navigator.serviceWorker.register(
            "/sw.js",
            {
                scope: "/"
            }
        );


    await navigator.serviceWorker.ready;


    return registration;

}


//==================================================
// 取得現有 Subscription
//==================================================

async function getExistingPushSubscription(
    registration
)
{

    return await registration
        .pushManager
        .getSubscription();

}


//==================================================
// 建立 Subscription
//==================================================

async function createPushSubscription(
    registration
)
{

    return await registration
        .pushManager
        .subscribe(
        {
            userVisibleOnly:
                true,

            applicationServerKey:
                urlBase64ToUint8Array(
                    VAPID_PUBLIC_KEY
                )
        }
    );

}


//==================================================
// 啟用 Push
//
// 只有使用者按下按鈕才會執行。
//==================================================

async function enablePush()
{

    try
    {

        pushModalEnable.disabled =
            true;

        pushModalLater.disabled =
            true;


        pushModalEnable.textContent =
            "⏳ 啟用中...";


        //==================================================
        // 檢查瀏覽器支援
        //==================================================

        if (
            !(
                "serviceWorker"
                in navigator
            )
        )
        {
            throw new Error(
                "此瀏覽器不支援網站通知"
            );
        }


        if (
            !(
                "PushManager"
                in window
            )
        )
        {
            throw new Error(
                "此瀏覽器不支援 Push 通知"
            );
        }


        if (
            !(
                "Notification"
                in window
            )
        )
        {
            throw new Error(
                "此瀏覽器不支援通知功能"
            );
        }


        //==================================================
        // 先檢查瀏覽器權限
        //==================================================

        let permission =
            Notification.permission;


        //==================================================
        // 已經被瀏覽器封鎖
        //
        // 不會寫 DB
        //==================================================

        if (
            permission === "denied"
        )
        {

            closePushModal();


            showPushStatus(
                "⚠️ 你的瀏覽器目前已封鎖網站通知，請到瀏覽器的網站設定中將通知改為「允許」，之後再回來開啟。",
                7000
            );


            return;

        }


        //==================================================
        // 尚未決定
        //
        // 現在才要求瀏覽器權限
        //==================================================

        if (
            permission === "default"
        )
        {

            permission =
                await Notification.requestPermission();

        }


        //==================================================
        // 使用者沒有允許
        //
        // 不寫 DB
        //==================================================

        if (
            permission !== "granted"
        )
        {

            closePushModal();


            showPushStatus(
                "你目前沒有允許網站通知，下次進入網站時可以再選擇。",
                5000
            );


            return;

        }


        //==================================================
        // 註冊 Service Worker
        //==================================================

        const registration =
            await registerPushServiceWorker();


        //==================================================
        // 取得瀏覽器現有 Subscription
        //==================================================

        let subscription =
            await getExistingPushSubscription(
                registration
            );


        //==================================================
        // 沒有 Subscription
        // 才建立新的
        //==================================================

        if (
            !subscription
        )
        {

            subscription =
                await createPushSubscription(
                    registration
                );

        }


        //==================================================
        // 最後才寫入資料庫
        //
        // 只有使用者主動按「開啟通知」
        // 才會到這裡。
        //==================================================

        await savePushSubscription(
            subscription
        );


        //==================================================
        // 成功
        //==================================================

        closePushModal();


        showPushStatus(
            "✅ 網站通知已開啟！之後有新的內容可以收到通知。",
            5000
        );

    }
    catch (
        error
    )
    {

        console.error(
            "[Push] 啟用失敗:",
            error
        );


        closePushModal();


        showPushStatus(
            "❌ " +
            (
                error.message ||
                "通知啟用失敗"
            ),
            6000
        );

    }
    finally
    {

        pushModalEnable.disabled =
            false;

        pushModalLater.disabled =
            false;


        pushModalEnable.textContent =
            "🔔 開啟通知";

    }

}


//==================================================
// 檢查是否需要顯示通知彈窗
//
// 重要：
// 這裡「絕對不寫入資料庫」
//
// 只做：
// 1. 找瀏覽器 Subscription
// 2. 查 DB
// 3. DB 有 → 不彈
// 4. DB 沒有 → 彈
//==================================================

async function checkPushStatus()
{

    try
    {

        //==================================================
        // 不支援就不顯示
        //==================================================

        if (
            !(
                "serviceWorker"
                in navigator
            )
        )
        {
            return;
        }


        if (
            !(
                "PushManager"
                in window
            )
        )
        {
            return;
        }


        if (
            !(
                "Notification"
                in window
            )
        )
        {
            return;
        }


        //==================================================
        // 注意：
        //
        // 這裡不主動建立 Subscription。
        //
        // 只註冊 Service Worker，
        // 然後看看瀏覽器目前有沒有現成 Subscription。
        //==================================================

        const registration =
            await registerPushServiceWorker();


        const subscription =
            await getExistingPushSubscription(
                registration
            );


        //==================================================
        // 情況一：
        // 瀏覽器有 Subscription
        //==================================================

        if (
            subscription
        )
        {

            const exists =
                await checkSubscriptionInDatabase(
                    subscription
                );


            //==================================================
            // DB 有資料
            //
            // 完全不彈窗
            // 完全不寫 DB
            //==================================================

            if (
                exists
            )
            {

                console.log(
                    "[Push] DB 已存在 Subscription，不顯示通知彈窗"
                );


                return;

            }


            //==================================================
            // DB 沒有資料
            //
            // 重要：
            //
            // 以前這裡會自動 savePushSubscription()
            //
            // 現在完全取消。
            //
            // 改成詢問使用者。
            //==================================================

            console.log(
                "[Push] 瀏覽器已有 Subscription，但 DB 沒有，顯示詢問視窗"
            );

        }


        //==================================================
        // 情況二：
        // 沒有 Subscription
        //
        // 一樣顯示詢問。
        //==================================================

        if (
            !subscription
        )
        {

            console.log(
                "[Push] 尚無 Subscription，顯示通知詢問視窗"
            );

        }


        //==================================================
        // 延遲顯示
        //==================================================

        setTimeout(
            function()
            {

                showPushModal();

            },
            1200
        );

    }
    catch (
        error
    )
    {

        console.error(
            "[Push] 狀態檢查失敗:",
            error
        );


        //==================================================
        // 檢查失敗不影響網站
        //==================================================

    }

}


//==================================================
// 稍後再說
//
// 不寫 DB
// 不寫 Cookie
// 不寫 localStorage
// 不寫任何拒絕狀態
//
// 下次進網站會再詢問
//==================================================

pushModalLater.addEventListener(
    "click",
    function()
    {

        closePushModal();

    }
);


//==================================================
// 開啟通知
//==================================================

pushModalEnable.addEventListener(
    "click",
    function()
    {

        enablePush();

    }
);


//==================================================
// 點擊遮罩關閉
//
// 不記錄拒絕
//==================================================

pushModalOverlay.addEventListener(
    "click",
    function(event)
    {

        if (
            event.target ===
            pushModalOverlay
        )
        {

            closePushModal();

        }

    }
);


//==================================================
// ESC 關閉
//
// 不記錄拒絕
//==================================================

document.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key === "Escape"
        )
        {

            closePushModal();

        }

    }
);


//==================================================
// 網頁載入
//==================================================

window.addEventListener(
    "load",
    async function()
    {

        await checkPushStatus();

    }
);


//==================================================
// Content 展開
//==================================================

function toggleContent(
    button
)
{

    const item =
        button.closest(
            ".content-item"
        );


    const panel =
        item.querySelector(
            ".image-panel"
        );


    const expanded =
        button.getAttribute(
            "aria-expanded"
        ) === "true";


    if (
        expanded
    )
    {

        button.setAttribute(
            "aria-expanded",
            "false"
        );


        item.classList.remove(
            "open"
        );


        panel.style.maxHeight =
            "0px";

    }
    else
    {

        button.setAttribute(
            "aria-expanded",
            "true"
        );


        item.classList.add(
            "open"
        );


        panel.style.maxHeight =
            panel.scrollHeight +
            "px";

    }

}

</script>


</body>

</html>
