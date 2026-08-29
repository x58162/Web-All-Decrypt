<?php

//==================================================
// Database
//==================================================

require_once __DIR__ . "/config.php";


//==================================================
// 開獎資料設定
//
// 透過 URL 選擇資料表：
//
// dailycash.php?lottery=dailycash
// dailycash.php?lottery=biglottery
// dailycash.php?lottery=fantasy5
// dailycash.php?lottery=marksix
//
// Database：
// lottery.dailycash
// lottery.biglottery
// lottery.fantasy5
// lottery.marksix
//
//==================================================

$lotteryConfigs =
[

    "dailycash" =>
    [
        "table" =>
            "dailycash",

        "name" =>
            "今彩539",

        "title" =>
            "今彩539開獎結果",

        "description" =>
            "查看今彩539開獎結果，提供最新歷史開獎資料、落球與順球排列。"
    ],


    "biglottery" =>
    [
        "table" =>
            "biglottery",

        "name" =>
            "大樂透",

        "title" =>
            "大樂透開獎結果",

        "description" =>
            "查看大樂透開獎結果，提供最新歷史開獎資料、落球與順球排列。"
    ],


    "fantasy5" =>
    [
        "table" =>
            "fantasy5",

        "name" =>
            "天天樂",

        "title" =>
            "天天樂開獎結果",

        "description" =>
            "查看天天樂開獎結果，提供最新歷史開獎資料、落球與順球排列。"
    ],


    "marksix" =>
    [
        "table" =>
            "marksix",

        "name" =>
            "六合彩",

        "title" =>
            "六合彩開獎結果",

        "description" =>
            "查看六合彩開獎結果，提供最新歷史開獎資料、落球與順球排列。"
    ]

];


//==================================================
// 目前彩種
//==================================================

$lotteryType =
    "dailycash";

if (
    isset($_GET["lottery"])
    && is_string($_GET["lottery"])
)
{
    $lotteryType =
        trim($_GET["lottery"]);
}


//==================================================
// 不存在時回到今彩539
//==================================================

if (
    !isset(
        $lotteryConfigs[$lotteryType]
    )
    || !is_array(
        $lotteryConfigs[$lotteryType]
    )
)
{
    $lotteryType =
        "dailycash";
}


//==================================================
// 目前彩種設定
//==================================================

$currentLottery =
    $lotteryConfigs[
        $lotteryType
    ];


//==================================================
// 資料表名稱
//
// 注意：
// 這裡只能使用上面白名單內的值
// 不直接使用使用者輸入的 table 名稱
//==================================================

$tableName =
    $currentLottery["table"];


//==================================================
// 每頁 30 筆
//==================================================

$itemsPerPage = 30;


//==================================================
// 目前頁數
//==================================================

$page =
    isset($_GET["page"])
    ?
    intval($_GET["page"])
    :
    1;


if (
    $page < 1
)
{
    $page = 1;
}


//==================================================
// 取得總筆數
//==================================================

$totalItems = 0;

try
{

    $countSql =
        "
        SELECT
            COUNT(*)

        FROM lottery.`$tableName`
        ";


    $countStatement =
        $pdo->query(
            $countSql
        );


    $totalItems =
        intval(
            $countStatement->fetchColumn()
        );

}
catch (
    PDOException $e
)
{

    $totalItems = 0;

}


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
// 取得資料
//
// 最新資料在最上面
//
// Id DESC
//
// 每頁 30 筆
//==================================================

$results = [];


if (
    $totalItems > 0
)
{

    try
    {

        $sql =
            "
            SELECT
                Id,
                Dates,
                NumberOne,
                NumberTwo,
                NumberThree,
                NumberFour,
                NumberFive

            FROM lottery.`$tableName`

            ORDER BY Id DESC

            LIMIT :limit

            OFFSET :offset
            ";


        $statement =
            $pdo->prepare(
                $sql
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


        $results =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        //==================================================
        // 每頁先抓最新 30 筆
        //
        // SQL：Id DESC
        //
        // 再將這 30 筆反轉
        //
        // 最終顯示：
        // 舊 → 新
        //
        // 因此：
        // 最新資料仍然在第一頁
        // 但最新資料會顯示在第一頁最下面
        //==================================================

        $results =
            array_reverse(
                $results
            );

    }
    catch (
        PDOException $e
    )
    {

        $results = [];

    }

}


//==================================================
// SEO URL
//==================================================

$scheme =
    (
        isset($_SERVER["HTTPS"])
        &&
        $_SERVER["HTTPS"] !== "off"
    )
    ?
    "https"
    :
    "http";


$host =
    $_SERVER["HTTP_HOST"]
    ??
    "localhost";


$canonicalUrl =
    $scheme .
    "://" .
    $host .
    "/dailycash.php";


//==================================================
// lottery
//==================================================

$canonicalUrl .=
    "?lottery=" .
    rawurlencode(
        $lotteryType
    );


//==================================================
// page
//==================================================

if (
    $page > 1
)
{

    $canonicalUrl .=
        "&page=" .
        $page;

}


//==================================================
// JSON-LD
//==================================================

$schemaItems = [];


foreach (
    $results
    as $row
)
{

    $numbers =
    [
        intval(
            $row["NumberOne"]
        ),

        intval(
            $row["NumberTwo"]
        ),

        intval(
            $row["NumberThree"]
        ),

        intval(
            $row["NumberFour"]
        ),

        intval(
            $row["NumberFive"]
        )
    ];


    $schemaItems[] =
    [
        "@type" =>
            "ListItem",

        "position" =>
            count(
                $schemaItems
            ) + 1,

        "name" =>
            "第 " .
            intval(
                $row["Id"]
            ) .
            " 期｜" .
            $row["Dates"] .
            "｜" .
            implode(
                ", ",
                $numbers
            )
    ];

}


$schema =
[
    "@context" =>
        "https://schema.org",

    "@type" =>
        "ItemList",

    "name" =>
        $currentLottery["name"] .
        "最近開獎結果",

    "description" =>
        $currentLottery["description"],

    "itemListElement" =>
        $schemaItems
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


<!--==================================================
     SEO
==================================================-->

<title>

<?= htmlspecialchars(
    $currentLottery["title"],
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
        $currentLottery["description"],
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


<!--==================================================
     Open Graph
==================================================-->

<meta
    property="og:type"
    content="website"
>


<meta
    property="og:title"
    content="<?= htmlspecialchars(
        $currentLottery["title"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>


<meta
    property="og:description"
    content="<?= htmlspecialchars(
        $currentLottery["description"],
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
    content="All-Decrypt"
>


<!--==================================================
     JSON-LD
==================================================-->

<script type="application/ld+json">

<?= json_encode(
    $schema,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_PRETTY_PRINT
) ?>

</script>


<!--==================================================
     CSS
==================================================-->

<style>

*
{
    box-sizing: border-box;
}


body
{
    margin: 0;

    padding: 20px;

    /* background: #f3f4f6; */
    background: #1a1717;
    font-family:
        Arial,
        "Microsoft JhengHei",
        sans-serif;

    color: #000;
}


.dailycash-page
{
    width: 100%;

    max-width: 1000px;

    margin: 0 auto;
}


/*==================================================
  Header
==================================================*/

.dailycash-header
{
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 15px;
}


.dailycash-title
{
    margin: 0;
    color: #FFF;
    font-size: 26px;

    font-weight: bold;
}


/*==================================================
  表格
==================================================*/

.result-table
{
    width: 100%;

    border-collapse: separate;

    border-spacing: 0;

    overflow: hidden;

    border-radius: 8px;

    background: #ffffff;

    box-shadow:
        0
        2px
        8px
        rgba(
            0,
            0,
            0,
            0.08
        );
}


/*==================================================
  Header
==================================================*/

.result-header
{
    display: grid;

    grid-template-columns:
        80px
        110px
        1fr
        1fr;

    align-items: center;

    min-height: 44px;

    font-weight: bold;

    text-align: center;
}


.result-header > div
{
    padding:
        8px
        5px;
}


.header-drop
{
    background: #d9e2f3;
}


.header-order
{
    background: #e2f0d9;
}


/*==================================================
  每一期
==================================================*/

.result-row
{
    display: grid;

    grid-template-columns:
        80px
        110px
        1fr
        1fr;

    align-items: center;

    min-height: 52px;

    border-top:
        1px
        solid
        #e5e7eb;
}


.result-period
{
    text-align: center;

    font-weight: bold;

    padding: 5px;
}


.result-date
{
    text-align: center;

    padding: 5px;

    font-size: 14px;
}


/*==================================================
  落球
==================================================*/

.drop-cell
{
    display: flex;

    justify-content: center;

    align-items: center;

    gap: 5px;

    min-height: 52px;

    padding: 5px;

    background: #f0f4fa;
}


/*==================================================
  順球
==================================================*/

.order-cell
{
    display: flex;

    justify-content: center;

    align-items: center;

    gap: 5px;

    min-height: 52px;

    padding: 5px;

    background: #f1f7ed;
}


/*==================================================
  球
==================================================*/

.ball
{
    width: 32px;

    height: 32px;

    flex:
        0 0 32px;

    display: flex;

    justify-content: center;

    align-items: center;

    border-radius: 50%;

    font-size: 15px;

    font-weight: bold;

    line-height: 1;

    border:
        1px
        solid
        rgba(
            0,
            0,
            0,
            0.12
        );
}


.drop-ball
{
    background: #ffffff;
}


.order-ball
{
    background: #ffffff;
}


/*==================================================
  空資料
==================================================*/

.empty-message
{
    padding: 40px;

    text-align: center;

    background: #ffffff;

    border-radius: 8px;
}


/*==================================================
  Pagination
==================================================*/

.pagination
{
    display: flex;

    justify-content: center;

    align-items: center;

    flex-wrap: wrap;

    gap: 6px;

    margin-top: 20px;

    padding-bottom: 20px;
}


.page-button
{
    display: inline-flex;

    justify-content: center;

    align-items: center;

    min-width: 38px;

    height: 38px;

    padding:
        0
        10px;

    border:
        1px
        solid
        #d1d5db;

    border-radius: 6px;

    background: #ffffff;

    color: #222222;

    text-decoration: none;

    font-size: 14px;

    font-weight: bold;

    box-sizing: border-box;

    transition:
        background 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease;
}


.page-button:hover
{
    background: #f0f4fa;

    border-color: #007bff;

    color: #007bff;
}


.page-button.active
{
    background: #007bff;

    border-color: #007bff;

    color: #ffffff;
}


.page-button.active:hover
{
    background: #0056b3;

    border-color: #0056b3;

    color: #ffffff;
}


.page-dots
{
    display: inline-flex;

    justify-content: center;

    align-items: center;

    min-width: 30px;

    height: 38px;

    color: #666666;

    font-size: 14px;
}


/*==================================================
  Mobile
==================================================*/

@media (max-width: 600px)
{

    body
    {
        padding: 8px;
    }


    .dailycash-header
    {
        align-items: stretch;

        margin-bottom: 10px;
    }


    .dailycash-title
    {
        font-size: 20px;

        line-height: 1.4;
    }


    .result-header,
    .result-row
    {
        grid-template-columns:
            48px
            72px
            minmax(0, 1fr)
            minmax(0, 1fr);
    }


    .result-header
    {
        min-height: 38px;

        font-size: 13px;
    }


    .result-header > div
    {
        padding:
            6px
            2px;
    }


    .result-row
    {
        min-height: 44px;
    }


    .result-period
    {
        font-size: 13px;

        padding: 3px;
    }


    .result-date
    {
        font-size: 11px;

        padding: 3px;
    }


    .drop-cell,
    .order-cell
    {
        min-height: 44px;

        gap: 2px;

        padding: 3px;
    }


    .ball
    {
        width: 25px;

        height: 25px;

        flex-basis: 25px;

        font-size: 12px;
    }


    .pagination
    {
        gap: 4px;

        margin-top: 15px;
    }


    .page-button
    {
        min-width: 34px;

        height: 34px;

        padding:
            0
            7px;

        font-size: 13px;
    }


    .page-dots
    {
        min-width: 24px;

        height: 34px;

        font-size: 13px;
    }

}

</style>

</head>


<body>


<div class="dailycash-page">


<!--==================================================
     ★ 統一功能選單
==================================================-->

<?php

require_once __DIR__ . "/menu.php";

?>


<!--==================================================
     Header
==================================================-->

<header class="dailycash-header">


<h1 class="dailycash-title">

<?= htmlspecialchars(
    $currentLottery["name"],
    ENT_QUOTES,
    "UTF-8"
) ?>

開獎結果

</h1>


</header>


<!--==================================================
     資料
==================================================-->

<?php if (
    count($results) > 0
): ?>


<div class="result-table">


<!--==================================================
     表頭
==================================================-->

<div class="result-header">

<div>
期數
</div>


<div>
日期
</div>


<div class="header-drop">
落球
</div>


<div class="header-order">
順球
</div>

</div>


<!--==================================================
     資料
==================================================-->

<?php foreach (
    $results
    as $row
): ?>


<?php

$numbers =
[
    intval(
        $row["NumberOne"]
    ),

    intval(
        $row["NumberTwo"]
    ),

    intval(
        $row["NumberThree"]
    ),

    intval(
        $row["NumberFour"]
    ),

    intval(
        $row["NumberFive"]
    )
];


$sortedNumbers =
    $numbers;


sort(
    $sortedNumbers,
    SORT_NUMERIC
);


$period =
    htmlspecialchars(
        (string)$row["Id"],
        ENT_QUOTES,
        "UTF-8"
    );


$date =
    htmlspecialchars(
        (string)$row["Dates"],
        ENT_QUOTES,
        "UTF-8"
    );

?>


<div class="result-row">


<!--==================================================
     期數
==================================================-->

<div class="result-period">

<?= $period ?>

</div>


<!--==================================================
     日期
==================================================-->

<div class="result-date">

<?= $date ?>

</div>


<!--==================================================
     落球
==================================================-->

<div class="drop-cell">


<?php foreach (
    $numbers
    as $number
): ?>

<span class="ball drop-ball">

<?= $number ?>

</span>

<?php endforeach; ?>


</div>


<!--==================================================
     順球
==================================================-->

<div class="order-cell">


<?php foreach (
    $sortedNumbers
    as $number
): ?>

<span class="ball order-ball">

<?= $number ?>

</span>

<?php endforeach; ?>


</div>


</div>


<?php endforeach; ?>


</div>


<?php else: ?>


<div class="empty-message">

目前沒有
<?= htmlspecialchars(
    $currentLottery["name"],
    ENT_QUOTES,
    "UTF-8"
) ?>
的開獎資料

</div>


<?php endif; ?>


<!--==================================================
     Pagination
==================================================-->

<?php if (
    $totalPages > 1
): ?>


<nav
    class="pagination"
    aria-label="開獎結果頁面"
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


</body>

</html>
