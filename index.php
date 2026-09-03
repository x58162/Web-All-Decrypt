<?php

//==================================================
// All-Decrypt｜首頁
//==================================================


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

<link
    rel="icon"
    type="image/x-icon"
    href="/favicon.ico"
>

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


<!--==================================================
     Google AdSense
==================================================-->

<script
    async
    src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8646014509722763"
    crossorigin="anonymous">
</script>


<style>

/*==================================================
  All-Decrypt 首頁 UI
==================================================*/

:root
{
    --ad-bg:
        #0d0b10;

    --ad-bg-soft:
        #141118;

    --ad-card:
        rgba(
            255,
            255,
            255,
            0.045
        );

    --ad-card-hover:
        rgba(
            255,
            255,
            255,
            0.075
        );

    --ad-border:
        rgba(
            255,
            255,
            255,
            0.085
        );

    --ad-text:
        #f4f1f8;

    --ad-text-soft:
        #aaa4b4;

    --ad-text-muted:
        #777180;

    --ad-purple:
        #8b68ff;

    --ad-purple-soft:
        #6f4ee8;

    --ad-green:
        #25d979;

    --ad-red:
        #ff566e;

    --ad-gray:
        #8b8792;
}


/*==================================================
  Body
==================================================*/

html
{
    scroll-behavior: smooth;
}


body
{
    margin: 0;

    padding: 0;

    background:
        radial-gradient(
            circle at 50% -10%,
            rgba(
                105,
                76,
                185,
                0.18
            ),
            transparent 35%
        ),
        linear-gradient(
            180deg,
            #0c0a0f 0%,
            #100d12 45%,
            #0a090d 100%
        );

    color:
        var(--ad-text);

    box-sizing: border-box;

    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        "Noto Sans TC",
        "Microsoft JhengHei",
        Arial,
        sans-serif;
}


*
{
    box-sizing: border-box;
}


/*==================================================
  Page
==================================================*/

.page
{
    position: relative;

    min-height: 100vh;

    overflow: hidden;
}


.page::before
{
    content: "";

    position: fixed;

    top: -180px;

    left: 50%;

    width: 600px;

    height: 400px;

    transform:
        translateX(-50%);

    border-radius: 50%;

    background:
        rgba(
            112,
            78,
            255,
            0.07
        );

    filter:
        blur(90px);

    pointer-events: none;

    z-index: 0;
}


/*==================================================
  Header
==================================================*/

.site-header
{
    position: relative;

    z-index: 100;

    width: 100%;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            0.055
        );

    background:
        rgba(
            10,
            8,
            13,
            0.82
        );

    backdrop-filter:
        blur(18px);

    -webkit-backdrop-filter:
        blur(18px);
}


/*==================================================
  管理按鈕
==================================================*/

.admin-button
{
    position: fixed;

    top: 18px;

    right: 20px;

    z-index: 9999;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 64px;

    padding:
        8px
        15px;

    border:
        1px solid
        rgba(
            139,
            104,
            255,
            0.35
        );

    border-radius: 10px;

    background:
        rgba(
            75,
            52,
            130,
            0.28
        );

    color:
        #d9ceff;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

    box-shadow:
        0 8px 25px
        rgba(
            0,
            0,
            0,
            0.25
        );

    transition:
        all 0.2s ease;
}


.admin-button:hover
{
    transform:
        translateY(-2px);

    background:
        rgba(
            110,
            78,
            190,
            0.42
        );

    border-color:
        rgba(
            150,
            120,
            255,
            0.55
        );

    color: #ffffff;
}


/*==================================================
  Main Content
==================================================*/

.content-list
{
    position: relative;

    z-index: 2;

    width:
        min(
            calc(
                100% - 30px
            ),
            1000px
        );

    margin:
        0
        auto;

    padding:
        35px
        0
        70px;
}


/*==================================================
  頂部資訊
==================================================*/

.content-list::before
{
    content:
        "ALL-DECRYPT  /  " attr(data-lottery);

    display: none;
}


/*==================================================
  Content Item
==================================================*/

.content-item
{
    position: relative;

    width: 100%;

    margin-bottom: 14px;

    border:
        1px solid
        var(--ad-border);

    border-radius: 16px;

    background:
        linear-gradient(
            135deg,
            rgba(
                255,
                255,
                255,
                0.055
            ),
            rgba(
                255,
                255,
                255,
                0.018
            )
        );

    box-shadow:
        0
        10px
        35px
        rgba(
            0,
            0,
            0,
            0.22
        );

    overflow: visible;

    transition:
        border-color 0.25s ease,
        background 0.25s ease,
        box-shadow 0.25s ease,
        transform 0.25s ease;
}


.content-item:hover
{
    border-color:
        rgba(
            139,
            104,
            255,
            0.28
        );

    background:
        linear-gradient(
            135deg,
            rgba(
                255,
                255,
                255,
                0.075
            ),
            rgba(
                255,
                255,
                255,
                0.025
            )
        );

    box-shadow:
        0
        15px
        45px
        rgba(
            0,
            0,
            0,
            0.3
        );
}


.content-item.open
{
    border-color:
        rgba(
            139,
            104,
            255,
            0.4
        );

    box-shadow:
        0
        15px
        55px
        rgba(
            50,
            25,
            120,
            0.18
        );
}


/*==================================================
  Label Wrapper
==================================================*/

.label-wrapper
{
    position: relative;

    width: 100%;

    min-height: 72px;

    display: flex;

    align-items: stretch;
}


/*==================================================
  Status Badge
==================================================*/

.label-enabled-status
{
    position: absolute;

    left: 14px;

    top: 50%;

    z-index: 20;

    transform:
        translateY(-50%);

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 54px;

    height: 30px;

    padding:
        0
        11px;

    border-radius: 8px;

    color: #ffffff;

    font-size: 12px;

    font-weight: 800;

    line-height: 1;

    letter-spacing: 1px;

    pointer-events: none;

    white-space: nowrap;

    box-shadow:
        0
        5px
        15px
        rgba(
            0,
            0,
            0,
            0.18
        );
}


.label-enabled-status::before
{
    content: "";

    width: 6px;

    height: 6px;

    margin-right: 6px;

    border-radius: 50%;

    background:
        currentColor;

    box-shadow:
        0 0 8px
        currentColor;
}


.label-enabled-status.waiting
{
    background:
        rgba(
            110,
            105,
            120,
            0.28
        );

    border:
        1px solid
        rgba(
            160,
            155,
            170,
            0.2
        );

    color:
        #c5c0cc;
}


.label-enabled-status.enabled
{
    background:
        rgba(
            20,
            170,
            90,
            0.16
        );

    border:
        1px solid
        rgba(
            37,
            217,
            121,
            0.28
        );

    color:
        #4cf39a;
}


.label-enabled-status.disabled
{
    background:
        rgba(
            220,
            45,
            75,
            0.14
        );

    border:
        1px solid
        rgba(
            255,
            86,
            110,
            0.24
        );

    color:
        #ff7184;
}


/*==================================================
  Content Label Button
==================================================*/

.content-label
{
    position: relative;

    display: flex;

    align-items: center;

    width: 100%;

    min-height: 72px;

    margin: 0;

    padding:
        17px
        48px
        17px
        94px;

    border: 0;

    outline: 0;

    border-radius: 15px;

    background:
        transparent;

    color:
        var(--ad-text);

    text-align: left;

    cursor: pointer;

    font-family: inherit;

    font-size: 16px;

    font-weight: 650;

    line-height: 1.5;

    transition:
        color 0.2s ease,
        background 0.2s ease;
}


.content-label:hover
{
    background:
        rgba(
            139,
            104,
            255,
            0.035
        );
}


.content-label:focus-visible
{
    box-shadow:
        inset
        0
        0
        0
        2px
        rgba(
            139,
            104,
            255,
            0.55
        );
}


.content-label .label-text
{
    display:
        -webkit-box;

    width: 100%;

    overflow: hidden;

    color:
        #eeeaf2;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow-wrap: anywhere;
}


.content-item.open
.content-label .label-text
{
    color:
        #ffffff;
}


/*==================================================
  Arrow
==================================================*/

.label-arrow
{
    position: absolute;

    right: 21px;

    top: 50%;

    transform:
        translateY(-50%);

    display: flex;

    align-items: center;

    justify-content: center;

    width: 30px;

    height: 30px;

    border-radius: 9px;

    background:
        rgba(
            255,
            255,
            255,
            0.055
        );

    color:
        #938d9e;

    font-size: 10px;

    transition:
        transform 0.3s ease,
        background 0.25s ease,
        color 0.25s ease;
}


.content-item.open
.label-arrow
{
    transform:
        translateY(-50%)
        rotate(90deg);

    background:
        rgba(
            139,
            104,
            255,
            0.16
        );

    color:
        #b6a4ff;
}


/*==================================================
  Image Panel
==================================================*/

.image-panel
{
    max-height: 0;

    overflow: hidden;

    opacity: 0;

    transition:
        max-height 0.45s
        cubic-bezier(
            0.22,
            1,
            0.36,
            1
        ),
        opacity 0.25s ease;
}


.content-item.open
.image-panel
{
    opacity: 1;
}


.image-inner
{
    margin:
        0
        14px
        14px;

    padding: 8px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.07
        );

    border-radius: 13px;

    background:
        rgba(
            0,
            0,
            0,
            0.28
        );

    box-shadow:
        inset
        0
        0
        25px
        rgba(
            0,
            0,
            0,
            0.2
        );
}


.image-inner img
{
    display: block;

    width: 100%;

    height: auto;

    max-width: 100%;

    border-radius: 8px;

    background:
        #111;

    object-fit: contain;
}


/*==================================================
  Empty Message
==================================================*/

.empty-message
{
    display: flex;

    align-items: center;

    justify-content: center;

    min-height: 220px;

    padding: 30px;

    border:
        1px dashed
        rgba(
            255,
            255,
            255,
            0.12
        );

    border-radius: 18px;

    background:
        rgba(
            255,
            255,
            255,
            0.025
        );

    color:
        var(--ad-text-muted);

    font-size: 15px;

    text-align: center;
}


/*==================================================
  Pagination
==================================================*/

.pagination
{
    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-wrap: wrap;

    gap: 7px;

    width:
        min(
            calc(
                100% - 30px
            ),
            1000px
        );

    margin:
        0
        auto
        65px;
}


.page-button
{
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 40px;

    height: 40px;

    padding:
        0
        10px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

    border-radius: 10px;

    background:
        rgba(
            255,
            255,
            255,
            0.035
        );

    color:
        #aaa4b2;

    text-decoration: none;

    font-size: 13px;

    font-weight: 650;

    transition:
        all 0.2s ease;
}


.page-button:hover
{
    transform:
        translateY(-2px);

    border-color:
        rgba(
            139,
            104,
            255,
            0.35
        );

    background:
        rgba(
            139,
            104,
            255,
            0.10
        );

    color:
        #d7cdff;
}


.page-button.active
{
    border-color:
        rgba(
            139,
            104,
            255,
            0.6
        );

    background:
        linear-gradient(
            135deg,
            #7652e8,
            #9a76ff
        );

    color:
        #ffffff;

    box-shadow:
        0
        7px
        22px
        rgba(
            110,
            75,
            230,
            0.28
        );
}


.page-dots
{
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 30px;

    height: 40px;

    color:
        #625d68;

    font-size: 14px;
}


/*==================================================
  Push Modal Overlay
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
            3,
            2,
            5,
            0.78
        );

    backdrop-filter:
        blur(8px);

    -webkit-backdrop-filter:
        blur(8px);

    opacity: 0;

    visibility: hidden;

    transition:
        opacity 0.25s ease,
        visibility 0.25s ease;
}


.push-modal-overlay.show
{
    opacity: 1;

    visibility: visible;
}


/*==================================================
  Push Modal
==================================================*/

.push-modal
{
    position: relative;

    width: 100%;

    max-width: 430px;

    padding:
        32px
        28px
        27px;

    border:
        1px solid
        rgba(
            139,
            104,
            255,
            0.20
        );

    border-radius: 22px;

    background:
        linear-gradient(
            145deg,
            #1b1722,
            #110f15
        );

    box-shadow:
        0
        30px
        90px
        rgba(
            0,
            0,
            0,
            0.65
        ),
        0
        0
        50px
        rgba(
            95,
            55,
            190,
            0.08
        );

    text-align: center;

    box-sizing: border-box;

    transform:
        translateY(18px)
        scale(0.95);

    transition:
        transform 0.28s
        cubic-bezier(
            0.22,
            1,
            0.36,
            1
        );
}


.push-modal-overlay.show
.push-modal
{
    transform:
        translateY(0)
        scale(1);
}


.push-modal::before
{
    content: "";

    position: absolute;

    top: -100px;

    left: 50%;

    width: 200px;

    height: 200px;

    transform:
        translateX(-50%);

    border-radius: 50%;

    background:
        rgba(
            117,
            82,
            255,
            0.11
        );

    filter:
        blur(50px);

    pointer-events: none;
}


/*==================================================
  Push Icon
==================================================*/

.push-modal-icon
{
    position: relative;

    width: 72px;

    height: 72px;

    margin:
        0
        auto
        18px;

    display: flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid
        rgba(
            139,
            104,
            255,
            0.30
        );

    border-radius: 21px;

    background:
        linear-gradient(
            135deg,
            rgba(
                139,
                104,
                255,
                0.20
            ),
            rgba(
                78,
                52,
                150,
                0.10
            )
        );

    color:
        #bcaaff;

    font-size: 32px;

    box-shadow:
        0
        12px
        35px
        rgba(
            80,
            50,
            180,
            0.18
        );
}


/*==================================================
  Push Title
==================================================*/

.push-modal-title
{
    position: relative;

    margin:
        0
        0
        11px;

    color:
        #ffffff;

    font-size: 23px;

    font-weight: 750;

    line-height: 1.4;
}


/*==================================================
  Push Text
==================================================*/

.push-modal-text
{
    position: relative;

    margin:
        0
        0
        25px;

    color:
        #a7a1ad;

    font-size: 14px;

    line-height: 1.8;
}


/*==================================================
  Push Actions
==================================================*/

.push-modal-actions
{
    position: relative;

    display: flex;

    gap: 10px;

    width: 100%;
}


/*==================================================
  Later
==================================================*/

.push-modal-later
{
    flex: 1;

    min-height: 48px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.10
        );

    border-radius: 11px;

    background:
        rgba(
            255,
            255,
            255,
            0.045
        );

    color:
        #aaa5b0;

    font-size: 14px;

    font-weight: 650;

    cursor: pointer;

    transition:
        all 0.2s ease;
}


.push-modal-later:hover
{
    background:
        rgba(
            255,
            255,
            255,
            0.08
        );

    color:
        #ffffff;
}


/*==================================================
  Enable
==================================================*/

.push-modal-enable
{
    flex: 1;

    min-height: 48px;

    border:
        1px solid
        rgba(
            139,
            104,
            255,
            0.55
        );

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #7651e8,
            #9570ff
        );

    color:
        #ffffff;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0
        8px
        25px
        rgba(
            111,
            75,
            225,
            0.25
        );

    transition:
        all 0.2s ease;
}


.push-modal-enable:hover
{
    transform:
        translateY(-2px);

    box-shadow:
        0
        12px
        30px
        rgba(
            111,
            75,
            225,
            0.38
        );
}


.push-modal-enable:disabled,
.push-modal-later:disabled
{
    opacity: 0.55;

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
        12px
        18px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.10
        );

    border-radius: 11px;

    background:
        rgba(
            17,
            14,
            21,
            0.94
        );

    box-shadow:
        0
        12px
        35px
        rgba(
            0,
            0,
            0,
            0.35
        );

    backdrop-filter:
        blur(15px);

    -webkit-backdrop-filter:
        blur(15px);

    color:
        #eeeaf1;

    font-size: 13px;

    line-height: 1.6;

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
  Selection
==================================================*/

::selection
{
    background:
        rgba(
            139,
            104,
            255,
            0.35
        );

    color:
        #ffffff;
}


/*==================================================
  Scrollbar
==================================================*/

::-webkit-scrollbar
{
    width: 8px;

    height: 8px;
}


::-webkit-scrollbar-track
{
    background:
        #0a090d;
}


::-webkit-scrollbar-thumb
{
    border-radius: 20px;

    background:
        rgba(
            139,
            104,
            255,
            0.30
        );
}


::-webkit-scrollbar-thumb:hover
{
    background:
        rgba(
            139,
            104,
            255,
            0.50
        );
}


/*==================================================
  Mobile
==================================================*/

@media (
    max-width: 700px
)
{

    .admin-button
    {
        top: 10px;

        right: 10px;

        min-width: 54px;

        padding:
            7px
            11px;

        font-size: 12px;
    }


    .content-list
    {
        width:
            calc(
                100% - 20px
            );

        padding:
            20px
            0
            45px;
    }


    .content-item
    {
        margin-bottom: 10px;

        border-radius: 13px;
    }


    .label-wrapper
    {
        min-height: 64px;
    }


    .label-enabled-status
    {
        left: 10px;

        min-width: 46px;

        height: 27px;

        padding:
            0
            8px;

        border-radius: 7px;

        font-size: 10px;

        letter-spacing: 0.5px;
    }


    .label-enabled-status::before
    {
        width: 5px;

        height: 5px;

        margin-right: 5px;
    }


    .content-label
    {
        min-height: 64px;

        padding:
            14px
            43px
            14px
            70px;

        font-size: 14px;

        line-height: 1.45;
    }


    .label-arrow
    {
        right: 12px;

        width: 27px;

        height: 27px;

        border-radius: 8px;

        font-size: 9px;
    }


    .image-inner
    {
        margin:
            0
            9px
            9px;

        padding: 5px;

        border-radius: 10px;
    }


    .image-inner img
    {
        border-radius: 6px;
    }


    .pagination
    {
        width:
            calc(
                100% - 20px
            );

        gap: 5px;

        margin-bottom: 45px;
    }


    .page-button
    {
        min-width: 36px;

        height: 36px;

        border-radius: 9px;

        font-size: 12px;
    }


    .page-dots
    {
        height: 36px;

        min-width: 20px;
    }


    .push-modal-overlay
    {
        padding: 15px;
    }


    .push-modal
    {
        max-width: 370px;

        padding:
            27px
            20px
            21px;

        border-radius: 18px;
    }


    .push-modal-icon
    {
        width: 62px;

        height: 62px;

        border-radius: 18px;

        font-size: 27px;
    }


    .push-modal-title
    {
        font-size: 20px;
    }


    .push-modal-text
    {
        font-size: 13px;

        line-height: 1.7;
    }


    .push-modal-actions
    {
        gap: 8px;
    }


    .push-modal-later,
    .push-modal-enable
    {
        min-height: 45px;

        font-size: 13px;
    }


    .push-status
    {
        bottom: 15px;

        max-width:
            calc(
                100vw - 20px
            );

        padding:
            10px
            14px;

        font-size: 12px;
    }

}


/*==================================================
  Small Mobile
==================================================*/

@media (
    max-width: 390px
)
{

    .content-label
    {
        padding-left: 66px;

        padding-right: 39px;

        font-size: 13px;
    }


    .label-enabled-status
    {
        left: 8px;

        min-width: 43px;

        padding:
            0
            6px;

        font-size: 9px;
    }


    .label-arrow
    {
        right: 9px;

        width: 25px;

        height: 25px;
    }


    .push-modal-actions
    {
        flex-direction: column;
    }


    .push-modal-later,
    .push-modal-enable
    {
        width: 100%;
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


        //================================================
        // 檢查瀏覽器支援
        //================================================

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


        //================================================
        // 先檢查瀏覽器權限
        //================================================

        let permission =
            Notification.permission;


        //================================================
        // 已經被瀏覽器封鎖
        //================================================

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


        //================================================
        // 尚未決定
        //================================================

        if (
            permission === "default"
        )
        {

            permission =
                await Notification.requestPermission();

        }


        //================================================
        // 使用者沒有允許
        //================================================

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


        //================================================
        // 註冊 Service Worker
        //================================================

        const registration =
            await registerPushServiceWorker();


        //================================================
        // 取得瀏覽器現有 Subscription
        //================================================

        let subscription =
            await getExistingPushSubscription(
                registration
            );


        //================================================
        // 沒有 Subscription
        // 才建立新的
        //================================================

        if (
            !subscription
        )
        {

            subscription =
                await createPushSubscription(
                    registration
                );

        }


        //================================================
        // 最後才寫入資料庫
        //================================================

        await savePushSubscription(
            subscription
        );


        //================================================
        // 成功
        //================================================

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
// 檢查 Push Status
//==================================================

async function checkPushStatus()
{

    try
    {

        //================================================
        // 不支援就不顯示
        //================================================

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


        //================================================
        // 註冊 Service Worker
        //================================================

        const registration =
            await registerPushServiceWorker();


        //================================================
        // 取得現有 Subscription
        //================================================

        const subscription =
            await getExistingPushSubscription(
                registration
            );


        //================================================
        // 瀏覽器已有 Subscription
        //================================================

        if (
            subscription
        )
        {

            const exists =
                await checkSubscriptionInDatabase(
                    subscription
                );


            //================================================
            // DB 已存在
            //================================================

            if (
                exists
            )
            {

                console.log(
                    "[Push] DB 已存在 Subscription，不顯示通知彈窗"
                );


                return;

            }


            //================================================
            // DB 沒有
            //================================================

            console.log(
                "[Push] 瀏覽器已有 Subscription，但 DB 沒有，顯示詢問視窗"
            );

        }


        //================================================
        // 尚無 Subscription
        //================================================

        if (
            !subscription
        )
        {

            console.log(
                "[Push] 尚無 Subscription，顯示通知詢問視窗"
            );

        }


        //================================================
        // 延遲顯示
        //================================================

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

    }

}


//==================================================
// 稍後再說
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


    if (
        !item
    )
    {
        return;
    }


    const panel =
        item.querySelector(
            ".image-panel"
        );


    if (
        !panel
    )
    {
        return;
    }


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


//==================================================
// 視窗尺寸改變時重新計算已開啟圖片高度
//==================================================

window.addEventListener(
    "resize",
    function()
    {

        document
            .querySelectorAll(
                ".content-item.open .image-panel"
            )
            .forEach(
                function(panel)
                {

                    panel.style.maxHeight =
                        panel.scrollHeight +
                        "px";

                }
            );

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

</script>


</div>


</body>

</html>
