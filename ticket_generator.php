<?php
// ==================================================
// ticket_generator.php
// 開獎單產生器
// ==================================================

require_once __DIR__ . "/config.php";
date_default_timezone_set("Asia/Taipei");

// --------------------------------------------------
// 彩種設定
// futureMode:
//   skip_sunday = 539，星期日不產生未來日期
//   daily       = Fantasy 5，每天產生
//   blank       = 大樂透 / 六合，未來預留區只留格子
// --------------------------------------------------
$games = [
    "539" => [
        "name" => "今彩539",
        "table" => "lottery.dailycash",
        "ballCount" => 5,
        "futureMode" => "skip_sunday",
        "columns" => [
            "NumberOne",
            "NumberTwo",
            "NumberThree",
            "NumberFour",
            "NumberFive"
        ]
    ],

    "fantasy5" => [
        "name" => "美國天天樂",
        "table" => "lottery.fantasy5",
        "ballCount" => 5,
        "futureMode" => "daily",
        "columns" => [
            "NumberOne",
            "NumberTwo",
            "NumberThree",
            "NumberFour",
            "NumberFive"
        ]
    ],

    "biglottery" => [
        "name" => "大樂透",
        "table" => "lottery.biglottery",
        "ballCount" => 7,
        "futureMode" => "blank",
        "columns" => [
            "NumberOne",
            "NumberTwo",
            "NumberThree",
            "NumberFour",
            "NumberFive",
            "NumberSix",
            "NumberSeven"
        ]
    ],

    "marksix" => [
        "name" => "香港六合彩",
        "table" => "lottery.marksix",
        "ballCount" => 7,
        "futureMode" => "blank",
        "columns" => [
            "NumberOne",
            "NumberTwo",
            "NumberThree",
            "NumberFour",
            "NumberFive",
            "NumberSix",
            "NumberSeven"
        ]
    ]
];

// --------------------------------------------------
// GET 參數
// --------------------------------------------------
$game = isset($_GET["game"])
    ? (string)$_GET["game"]
    : "539";

$order = isset($_GET["order"])
    ? (string)$_GET["order"]
    : "sorted";

$layout = isset($_GET["layout"])
    ? (string)$_GET["layout"]
    : "long";

if (!isset($games[$game])) {
    $game = "539";
}

if ($order !== "sorted" && $order !== "drop") {
    $order = "sorted";
}

if ($layout !== "long" && $layout !== "four") {
    $layout = "long";
}

$currentGame = $games[$game];

$ballCount = (int)$currentGame["ballCount"];
$tableName = $currentGame["table"];
$numberColumns = $currentGame["columns"];

// --------------------------------------------------
// 取得資料
// SQL：最新在前
// --------------------------------------------------
$selectColumns = array_merge(
    ["Id", "Dates"],
    $numberColumns
);

$sql =
    "SELECT " .
    implode(", ", $selectColumns) .
    " FROM " .
    $tableName .
    " ORDER BY Id DESC LIMIT 500";

try {

    $statement = $pdo->query($sql);

    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    http_response_code(500);

    die(
        "取得開獎資料失敗：" .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}

// --------------------------------------------------
// 整理開獎資料
// --------------------------------------------------
$allRows = [];

foreach ($results as $row) {

    $timestamp = strtotime(
        (string)$row["Dates"]
    );

    if ($timestamp === false) {
        continue;
    }

    $numbers = [];
    $validNumbers = true;

    foreach ($numberColumns as $column) {

        if (
            !isset($row[$column]) ||
            $row[$column] === "" ||
            !is_numeric($row[$column])
        ) {
            $validNumbers = false;
            break;
        }

        $numbers[] = (int)$row[$column];
    }

    if (
        !$validNumbers ||
        count($numbers) !== $ballCount
    ) {
        continue;
    }

    // --------------------------------------------------
    // 順球
    //
    // 5球：
    //   全部排序
    //
    // 7球：
    //   前6球排序
    //   第7球視為特別號，保留原位置
    // --------------------------------------------------
    if ($order === "sorted") {

        if ($ballCount >= 7) {

            $specialNumber =
                $numbers[$ballCount - 1];

            $mainNumbers =
                array_slice(
                    $numbers,
                    0,
                    $ballCount - 1
                );

            sort(
                $mainNumbers,
                SORT_NUMERIC
            );

            $numbers = $mainNumbers;

            $numbers[] = $specialNumber;

        } else {

            sort(
                $numbers,
                SORT_NUMERIC
            );
        }
    }

    $allRows[] = [
        "id" => (int)$row["Id"],
        "timestamp" => $timestamp,
        "numbers" => $numbers,
        "opened" => true
    ];
}

// --------------------------------------------------
// 沒資料
// --------------------------------------------------
if (count($allRows) === 0) {

    http_response_code(404);

    die("目前沒有有效的開獎資料。");
}

// --------------------------------------------------
// SQL 已經是：最新 → 最舊
// --------------------------------------------------
$latestRow = $allRows[0];

$latestTimestamp =
    $latestRow["timestamp"];

// --------------------------------------------------
// 顯示資料改成：最舊 → 最新
// --------------------------------------------------
$allRows = array_reverse(
    $allRows
);

// ==================================================
// 產生未來預留區
// ==================================================
//
// 539：
//   仍然產生實際未來日期
//
// Fantasy 5：
//   仍然產生實際未來日期
//
// 大樂透 / 六合：
//   不產生日期
//   不產生星期
//   不產生球號
//   只建立「blank_future」列
//   renderer 會只畫格子與格線
// ==================================================
function tgBuildFutureRows(
    $latestTimestamp,
    $reservedFutureRows,
    $ballCount,
    $futureMode
) {

    $futureRows = [];

    if ($reservedFutureRows <= 0) {
        return $futureRows;
    }

    // --------------------------------------------------
    // blank：
    // 大樂透 / 六合
    //
    // 不需要計算日期，
    // 直接建立指定數量的純空白格子列。
    // --------------------------------------------------
    if ($futureMode === "blank") {

        for (
            $i = 0;
            $i < $reservedFutureRows;
            $i++
        ) {

            $futureRows[] = [
                "type" => "blank_future",
                "numbers" => array_fill(
                    0,
                    $ballCount,
                    null
                ),
                "opened" => false
            ];
        }

        return $futureRows;
    }

    // --------------------------------------------------
    // 539 / Fantasy 5
    // --------------------------------------------------
    $timestamp = strtotime(
        "+1 day",
        $latestTimestamp
    );

    while (
        count($futureRows) <
        $reservedFutureRows
    ) {

        $weekDay =
            (int)date("N", $timestamp);

        // --------------------------------------------------
        // 539：星期日跳過
        // --------------------------------------------------
        if (
            $futureMode === "skip_sunday" &&
            $weekDay === 7
        ) {

            $timestamp = strtotime(
                "+1 day",
                $timestamp
            );

            continue;
        }

        $futureRows[] = [
            "type" => "future",
            "timestamp" => $timestamp,
            "numbers" => array_fill(
                0,
                $ballCount,
                null
            ),
            "opened" => false
        ];

        $timestamp = strtotime(
            "+1 day",
            $timestamp
        );
    }

    return $futureRows;
}

// ==================================================
// 建立顯示資料
// ==================================================

if ($layout === "long") {

    // --------------------------------------------------
    // 長版本
    // --------------------------------------------------
    $columns = 4;

    $rowsPerColumn = 43;

    // 最下面預留未來 / 空白格子
    $reservedFutureRows = 15;

    $totalRows =
        $columns *
        $rowsPerColumn;

    // --------------------------------------------------
    // 先抓最大可能開獎資料
    // --------------------------------------------------
    $temporaryLimit = max(
        0,
        $totalRows -
        $reservedFutureRows
    );

    $openedRows = array_slice(
        $allRows,
        -$temporaryLimit
    );

    // --------------------------------------------------
    // 計算年份列
    // --------------------------------------------------
    $yearList = [];

    $previousYear = null;

    foreach ($openedRows as $row) {

        $year = (int)date(
            "Y",
            $row["timestamp"]
        );

        if (
            $previousYear === null ||
            $year !== $previousYear
        ) {

            $yearList[] = $year;

            $previousYear = $year;
        }
    }

    // --------------------------------------------------
    // 扣掉年份列
    // --------------------------------------------------
    $openedDataLimit = max(
        0,
        $totalRows -
        $reservedFutureRows -
        count($yearList)
    );

    $openedRows =
        $openedDataLimit > 0
        ? array_slice(
            $allRows,
            -$openedDataLimit
        )
        : [];

    // --------------------------------------------------
    // 重新計算年份
    // --------------------------------------------------
    $yearList = [];

    $previousYear = null;

    foreach ($openedRows as $row) {

        $year = (int)date(
            "Y",
            $row["timestamp"]
        );

        if (
            $previousYear === null ||
            $year !== $previousYear
        ) {

            $yearList[] = $year;

            $previousYear = $year;
        }
    }

    // --------------------------------------------------
    // 最終資料數
    // --------------------------------------------------
    $finalOpenedDataLimit = max(
        0,
        $totalRows -
        $reservedFutureRows -
        count($yearList)
    );

    if (
        count($openedRows) !==
        $finalOpenedDataLimit
    ) {

        $openedRows =
            $finalOpenedDataLimit > 0
            ? array_slice(
                $allRows,
                -$finalOpenedDataLimit
            )
            : [];
    }

    // --------------------------------------------------
    // 建立顯示陣列
    // --------------------------------------------------
    $displayRows = [];

    $lastYear = null;

    foreach ($openedRows as $row) {

        $year = (int)date(
            "Y",
            $row["timestamp"]
        );

        if (
            $lastYear === null ||
            $year !== $lastYear
        ) {

            $displayRows[] = [
                "type" => "year",
                "year" => $year
            ];

            $lastYear = $year;
        }

        $displayRows[] = [
            "type" => "data",
            "data" => $row
        ];
    }

    // --------------------------------------------------
    // 建立最後 15 行
    //
    // 539 / Fantasy 5：
    //   照舊顯示未來日期
    //
    // 大樂透 / 六合：
    //   只留格子
    // --------------------------------------------------
    $futureRows = tgBuildFutureRows(
        $latestTimestamp,
        $reservedFutureRows,
        $ballCount,
        $currentGame["futureMode"]
    );

    foreach ($futureRows as $futureRow) {

        $displayRows[] =
            $futureRow;
    }

    // --------------------------------------------------
    // 補空白
    // --------------------------------------------------
    while (
        count($displayRows) <
        $totalRows
    ) {

        $displayRows[] = [
            "type" => "empty"
        ];
    }

    // --------------------------------------------------
    // 超過就截斷
    // --------------------------------------------------
    if (
        count($displayRows) >
        $totalRows
    ) {

        $displayRows =
            array_slice(
                $displayRows,
                0,
                $totalRows
            );
    }

} else {

    // ==================================================
    // 四排單
    // ==================================================

    $columns = 4;

    $rowsPerColumn = 51;

    // --------------------------------------------------
    // 最下面預留 9 行
    // --------------------------------------------------
    $reservedFutureRows = 9;

    $openedAreaRows =
        $rowsPerColumn -
        $reservedFutureRows;

    $selectedOpenedRows = [];

    $selectedYearCount = 0;

    $previousYear = null;

    // --------------------------------------------------
    // 從最新往最舊挑選
    // --------------------------------------------------
    for (
        $i = count($allRows) - 1;
        $i >= 0;
        $i--
    ) {

        $row = $allRows[$i];

        $year = (int)date(
            "Y",
            $row["timestamp"]
        );

        if (
            $previousYear === null ||
            $year !== $previousYear
        ) {

            $selectedYearCount++;

            $previousYear = $year;
        }

        $currentUsedRows =
            count($selectedOpenedRows) +
            $selectedYearCount;

        if (
            $currentUsedRows >
            $openedAreaRows
        ) {

            break;
        }

        $selectedOpenedRows[] =
            $row;
    }

    // --------------------------------------------------
    // 改回最舊 → 最新
    // --------------------------------------------------
    $selectedOpenedRows =
        array_reverse(
            $selectedOpenedRows
        );

    // --------------------------------------------------
    // 建立顯示資料
    // --------------------------------------------------
    $displayRows = [];

    $lastYear = null;

    foreach (
        $selectedOpenedRows
        as $row
    ) {

        $year = (int)date(
            "Y",
            $row["timestamp"]
        );

        if (
            $lastYear === null ||
            $year !== $lastYear
        ) {

            $displayRows[] = [
                "type" => "year",
                "year" => $year
            ];

            $lastYear = $year;
        }

        $displayRows[] = [
            "type" => "data",
            "data" => $row
        ];
    }

    // --------------------------------------------------
    // 建立最後 9 行
    //
    // 539 / Fantasy 5：
    //   照舊顯示未來日期
    //
    // 大樂透 / 六合：
    //   只留格子
    // --------------------------------------------------
    $futureRows = tgBuildFutureRows(
        $latestTimestamp,
        $reservedFutureRows,
        $ballCount,
        $currentGame["futureMode"]
    );

    foreach ($futureRows as $futureRow) {

        $displayRows[] =
            $futureRow;
    }

    // --------------------------------------------------
    // 補空白
    // --------------------------------------------------
    while (
        count($displayRows) <
        $rowsPerColumn
    ) {

        $displayRows[] = [
            "type" => "empty"
        ];
    }

    // --------------------------------------------------
    // 超過就截斷
    // --------------------------------------------------
    if (
        count($displayRows) >
        $rowsPerColumn
    ) {

        $displayRows =
            array_slice(
                $displayRows,
                0,
                $rowsPerColumn
            );
    }

    $totalRows =
        $rowsPerColumn;
}

// ==================================================
// PNG 網址
// ==================================================
$imageUrl =
    "ticket_generator.php" .
    "?output=png" .
    "&game=" .
    rawurlencode($game) .
    "&order=" .
    rawurlencode($order) .
    "&layout=" .
    rawurlencode($layout);

// ==================================================
// SEO
// ==================================================
$seoOrderName =
    ($order === "sorted")
    ? "順球"
    : "落球";

$seoLayoutName =
    ($layout === "long")
    ? "長版本"
    : "四排單";

$seoTitle =
    $currentGame["name"] .
    "開獎單產生器｜" .
    $seoOrderName .
    "｜" .
    $seoLayoutName .
    "｜All-Decrypt";

$seoDescription =
    $currentGame["name"] .
    "開獎單產生器，提供" .
    $seoOrderName .
    "與" .
    $seoLayoutName .
    "開獎單圖片預覽及 PNG 下載。";

$siteBaseUrl =
    "https://all-decrypt.com";

$seoCanonical =
    $siteBaseUrl .
    "/ticket_generator.php" .
    "?game=" .
    rawurlencode($game) .
    "&order=" .
    rawurlencode($order) .
    "&layout=" .
    rawurlencode($layout);

$seoImageUrl =
    $siteBaseUrl .
    "/" .
    $imageUrl;

// ==================================================
// PNG 產生器
// ==================================================
if (
    isset($_GET["output"]) &&
    $_GET["output"] === "png"
) {

    // --------------------------------------------------
    // GD
    // --------------------------------------------------
    if (
        !function_exists(
            "imagecreatetruecolor"
        )
    ) {

        http_response_code(500);

        die(
            "PHP GD 擴充功能沒有啟用。"
        );
    }

    if (
        !function_exists(
            "imagettftext"
        )
    ) {

        http_response_code(500);

        die(
            "PHP GD TrueType 字型功能沒有啟用。"
        );
    }

    // --------------------------------------------------
    // 圖片尺寸
    // --------------------------------------------------
    $imageWidth = 1600;

    $imageHeight = 2263;

    if ($layout === "long") {

        $rowsPerColumn = 43;

        $reservedFutureRows = 15;

    } else {

        $rowsPerColumn = 51;

        $reservedFutureRows = 9;
    }

    // --------------------------------------------------
    // 版面設定
    // --------------------------------------------------
    $titleHeight = 80;

    $topPadding = 5;

    $footerHeight = 45;

    $outerPadding = 20;

    $columnGap = 0;

    $tableWidth =
        $imageWidth -
        ($outerPadding * 2);

    $columnWidth =
        (int)floor(
            $tableWidth / $columns
        );

    $tableHeight =
        $imageHeight -
        $titleHeight -
        $topPadding -
        $footerHeight;

    $rowHeight =
        (int)floor(
            $tableHeight /
            $rowsPerColumn
        );

    $actualTableHeight =
        $rowHeight *
        $rowsPerColumn;

    // --------------------------------------------------
    // 日期欄寬
    // 7球彩種稍微縮小日期欄
    // --------------------------------------------------
    $dateWidth =
        ($ballCount >= 7)
        ? 105
        : 120;

    // --------------------------------------------------
    // 每顆球欄寬
    // --------------------------------------------------
    $numberWidth =
        (int)floor(
            ($columnWidth - $dateWidth) /
            $ballCount
        );

    // --------------------------------------------------
    // 星期
    // --------------------------------------------------
    $weekNames = [
        1 => "一",
        2 => "二",
        3 => "三",
        4 => "四",
        5 => "五",
        6 => "六",
        7 => "日"
    ];

    // --------------------------------------------------
    // 建立圖片
    // --------------------------------------------------
    $image =
        imagecreatetruecolor(
            $imageWidth,
            $imageHeight
        );

    imageantialias(
        $image,
        true
    );

    // --------------------------------------------------
    // 顏色
    // --------------------------------------------------
    $white =
        imagecolorallocate(
            $image,
            255,
            255,
            255
        );

    $black =
        imagecolorallocate(
            $image,
            20,
            20,
            20
        );

    $dateBackground =
        imagecolorallocate(
            $image,
            245,
            245,
            245
        );

    $yearBackground =
        imagecolorallocate(
            $image,
            235,
            235,
            235
        );

    $lineColor =
        imagecolorallocate(
            $image,
            165,
            165,
            165
        );

    $halfLineColor =
        imagecolorallocatealpha(
            $image,
            150,
            150,
            150,
            65
        );

    $outerLineColor =
        imagecolorallocate(
            $image,
            80,
            80,
            80
        );

    imagefill(
        $image,
        0,
        0,
        $white
    );

    // ==================================================
    // Windows 中文字型
    // ==================================================
    $fontCandidates = [
        "C:/Windows/Fonts/msjhbd.ttf",
        "C:/Windows/Fonts/msjh.ttf",
        "C:/Windows/Fonts/mingliub.ttf",
        "C:/Windows/Fonts/mingliu.ttf",
        "C:/Windows/Fonts/msjhbd.ttc",
        "C:/Windows/Fonts/msjh.ttc",
        "C:/Windows/Fonts/mingliub.ttc",
        "C:/Windows/Fonts/mingliu.ttc"
    ];

    $fontFile = null;

    foreach (
        $fontCandidates
        as $candidate
    ) {

        if (is_file($candidate)) {

            $fontFile =
                $candidate;

            break;
        }
    }

    if ($fontFile === null) {

        imagedestroy(
            $image
        );

        http_response_code(500);

        die(
            "找不到 Windows 中文字型。"
        );
    }

    // ==================================================
    // 文字置中
    // ==================================================
    function tgDrawCenterText(
        $image,
        $text,
        $fontFile,
        $fontSize,
        $color,
        $centerX,
        $centerY
    ) {

        $box =
            imagettfbbox(
                $fontSize,
                0,
                $fontFile,
                $text
            );

        $textWidth =
            $box[2] -
            $box[0];

        $textHeight =
            $box[1] -
            $box[7];

        $x =
            $centerX -
            ($textWidth / 2);

        $y =
            $centerY +
            ($textHeight / 2);

        imagettftext(
            $image,
            $fontSize,
            0,
            (int)$x,
            (int)$y,
            $color,
            $fontFile,
            $text
        );
    }

    // ==================================================
    // 橫線
    // ==================================================
    function tgDrawHorizontalLine(
        $image,
        $x1,
        $y,
        $x2,
        $lineColor,
        $halfLineColor
    ) {

        imageline(
            $image,
            $x1,
            $y,
            $x2,
            $y,
            $lineColor
        );

        imageline(
            $image,
            $x1,
            $y + 1,
            $x2,
            $y + 1,
            $halfLineColor
        );
    }

    // ==================================================
    // 直線
    // ==================================================
    function tgDrawVerticalLine(
        $image,
        $x,
        $y1,
        $y2,
        $lineColor,
        $halfLineColor
    ) {

        imageline(
            $image,
            $x,
            $y1,
            $x,
            $y2,
            $lineColor
        );

        imageline(
            $image,
            $x + 1,
            $y1,
            $x + 1,
            $y2,
            $halfLineColor
        );
    }

    // ==================================================
    // 外框
    // ==================================================
    function tgDrawOuterBorder(
        $image,
        $x1,
        $y1,
        $x2,
        $y2,
        $color
    ) {

        imagerectangle(
            $image,
            $x1,
            $y1,
            $x2,
            $y2,
            $color
        );

        imagerectangle(
            $image,
            $x1 + 1,
            $y1 + 1,
            $x2 - 1,
            $y2 - 1,
            $color
        );

        imagerectangle(
            $image,
            $x1 + 2,
            $y1 + 2,
            $x2 - 2,
            $y2 - 2,
            $color
        );
    }

    // ==================================================
    // 標題
    // ==================================================
    $orderTitle =
        ($order === "sorted")
        ? "順球"
        : "落球";

    tgDrawCenterText(
        $image,
        $currentGame["name"] .
        " " .
        $orderTitle,
        $fontFile,
        36,
        $black,
        $imageWidth / 2,
        $titleHeight / 2
    );

    // ==================================================
    // 表格起始 Y
    // ==================================================
    $tableStartY =
        $titleHeight +
        $topPadding;

    // ==================================================
    // 開始繪製
    // ==================================================
    for (
        $column = 0;
        $column < $columns;
        $column++
    ) {

        $columnX =
            $outerPadding +
            (
                $column *
                ($columnWidth + $columnGap)
            );

        for (
            $rowIndex = 0;
            $rowIndex < $rowsPerColumn;
            $rowIndex++
        ) {

            // --------------------------------------------------
            // 長版本：
            // 每欄放不同資料
            //
            // 四排單：
            // 四欄使用同一份資料
            // --------------------------------------------------
            if ($layout === "long") {

                $globalIndex =
                    $column *
                    $rowsPerColumn +
                    $rowIndex;

            } else {

                $globalIndex =
                    $rowIndex;
            }

            $rowY =
                $tableStartY +
                (
                    $rowIndex *
                    $rowHeight
                );

            $displayRow =
                $displayRows[$globalIndex]
                ??
                [
                    "type" => "empty"
                ];

            // --------------------------------------------------
            // 每一列先填白
            // --------------------------------------------------
            imagefilledrectangle(
                $image,
                $columnX,
                $rowY,
                $columnX +
                $columnWidth - 1,
                $rowY +
                $rowHeight - 1,
                $white
            );

            // --------------------------------------------------
            // 真正完全空白列
            // --------------------------------------------------
            if (
                $displayRow["type"] ===
                "empty"
            ) {

                continue;
            }

            // ==================================================
            // 年份列
            // ==================================================
            if (
                $displayRow["type"] ===
                "year"
            ) {

                if ($layout === "long") {

                    imagefilledrectangle(
                        $image,
                        $columnX,
                        $rowY,
                        $columnX +
                        $columnWidth - 1,
                        $rowY +
                        $rowHeight - 1,
                        $yearBackground
                    );

                    tgDrawHorizontalLine(
                        $image,
                        $columnX,
                        $rowY,
                        $columnX +
                        $columnWidth - 1,
                        $lineColor,
                        $halfLineColor
                    );

                    tgDrawCenterText(
                        $image,
                        (string)$displayRow["year"],
                        $fontFile,
                        20,
                        $black,
                        $columnX +
                        ($columnWidth / 2),
                        $rowY +
                        ($rowHeight / 2)
                    );

                } else {

                    imagefilledrectangle(
                        $image,
                        $columnX,
                        $rowY,
                        $columnX +
                        $columnWidth - 1,
                        $rowY +
                        $rowHeight - 1,
                        $dateBackground
                    );

                    $yearText =
                        (string)$displayRow["year"];

                    $yearBox =
                        imagettfbbox(
                            20,
                            0,
                            $fontFile,
                            $yearText
                        );

                    $yearTextHeight =
                        $yearBox[1] -
                        $yearBox[7];

                    $yearX =
                        $columnX + 10;

                    $yearY =
                        $rowY +
                        (
                            (
                                $rowHeight +
                                $yearTextHeight
                            ) / 2
                        );

                    imagettftext(
                        $image,
                        20,
                        0,
                        (int)$yearX,
                        (int)$yearY,
                        $black,
                        $fontFile,
                        $yearText
                    );
                }

                continue;
            }

            // ==================================================
            // 大樂透 / 六合：
            // 純空白格子列
            //
            // 不畫日期
            // 不畫星期
            // 不畫球號
            //
            // 但仍然畫：
            // 日期欄格子
            // 球號欄格子
            // 垂直線
            // 上下橫線
            // ==================================================
            if (
                $displayRow["type"] ===
                "blank_future"
            ) {

                // --------------------------------------------------
                // 日期欄保持純白
                // --------------------------------------------------
                imagefilledrectangle(
                    $image,
                    $columnX,
                    $rowY,
                    $columnX +
                    $dateWidth - 1,
                    $rowY +
                    $rowHeight - 1,
                    $white
                );

                // --------------------------------------------------
                // 球號欄保持純白
                // --------------------------------------------------
                for (
                    $numberIndex = 0;
                    $numberIndex < $ballCount;
                    $numberIndex++
                ) {

                    $cellX =
                        $columnX +
                        $dateWidth +
                        (
                            $numberIndex *
                            $numberWidth
                        );

                    if (
                        $numberIndex ===
                        $ballCount - 1
                    ) {

                        $currentNumberWidth =
                            (
                                $columnX +
                                $columnWidth
                            ) -
                            $cellX;

                    } else {

                        $currentNumberWidth =
                            $numberWidth;
                    }

                    imagefilledrectangle(
                        $image,
                        $cellX,
                        $rowY,
                        $cellX +
                        $currentNumberWidth - 1,
                        $rowY +
                        $rowHeight - 1,
                        $white
                    );

                    // --------------------------------------------------
                    // 球號欄內部分隔線
                    // --------------------------------------------------
                    if ($numberIndex > 0) {

                        tgDrawVerticalLine(
                            $image,
                            $cellX,
                            $rowY,
                            $rowY +
                            $rowHeight - 1,
                            $lineColor,
                            $halfLineColor
                        );
                    }
                }

                // --------------------------------------------------
                // 日期欄左線
                // --------------------------------------------------
                tgDrawVerticalLine(
                    $image,
                    $columnX,
                    $rowY,
                    $rowY +
                    $rowHeight - 1,
                    $lineColor,
                    $halfLineColor
                );

                // --------------------------------------------------
                // 日期欄右線
                // --------------------------------------------------
                tgDrawVerticalLine(
                    $image,
                    $columnX +
                    $dateWidth,
                    $rowY,
                    $rowY +
                    $rowHeight - 1,
                    $lineColor,
                    $halfLineColor
                );

                // --------------------------------------------------
                // 上線
                // --------------------------------------------------
                tgDrawHorizontalLine(
                    $image,
                    $columnX,
                    $rowY,
                    $columnX +
                    $columnWidth - 1,
                    $lineColor,
                    $halfLineColor
                );

                // --------------------------------------------------
                // 下線
                // --------------------------------------------------
                tgDrawHorizontalLine(
                    $image,
                    $columnX,
                    $rowY +
                    $rowHeight - 1,
                    $columnX +
                    $columnWidth - 1,
                    $lineColor,
                    $halfLineColor
                );

                continue;
            }

            // ==================================================
            // 開獎資料 / 一般未來日期資料
            // ==================================================
            if (
                $displayRow["type"] ===
                "data"
            ) {

                $timestamp =
                    $displayRow["data"]
                    ["timestamp"];

                $opened = true;

                $numbers =
                    $displayRow["data"]
                    ["numbers"];

            } else {

                // --------------------------------------------------
                // 539 / Fantasy 5 future
                // --------------------------------------------------
                $timestamp =
                    $displayRow["timestamp"];

                $opened = false;

                $numbers =
                    $displayRow["numbers"];
            }

            // ==================================================
            // 日期
            // ==================================================
            $weekDayNumber =
                (int)date(
                    "N",
                    $timestamp
                );

            $dateText =
                date(
                    "m/d",
                    $timestamp
                ) .
                " (" .
                (
                    $weekNames[
                        $weekDayNumber
                    ] ?? ""
                ) .
                ")";

            // --------------------------------------------------
            // 日期背景
            // --------------------------------------------------
            imagefilledrectangle(
                $image,
                $columnX,
                $rowY,
                $columnX +
                $dateWidth - 1,
                $rowY +
                $rowHeight - 1,
                $dateBackground
            );

            // --------------------------------------------------
            // 日期文字
            // --------------------------------------------------
            tgDrawCenterText(
                $image,
                $dateText,
                $fontFile,
                14,
                $black,
                $columnX +
                ($dateWidth / 2),
                $rowY +
                ($rowHeight / 2)
            );

            // ==================================================
            // 球號格
            // ==================================================
            for (
                $numberIndex = 0;
                $numberIndex < $ballCount;
                $numberIndex++
            ) {

                $cellX =
                    $columnX +
                    $dateWidth +
                    (
                        $numberIndex *
                        $numberWidth
                    );

                // --------------------------------------------------
                // 最後一格吃掉剩餘寬度
                // --------------------------------------------------
                if (
                    $numberIndex ===
                    $ballCount - 1
                ) {

                    $currentNumberWidth =
                        (
                            $columnX +
                            $columnWidth
                        ) -
                        $cellX;

                } else {

                    $currentNumberWidth =
                        $numberWidth;
                }

                // --------------------------------------------------
                // 球號格背景
                // --------------------------------------------------
                imagefilledrectangle(
                    $image,
                    $cellX,
                    $rowY,
                    $cellX +
                    $currentNumberWidth - 1,
                    $rowY +
                    $rowHeight - 1,
                    $white
                );

                // --------------------------------------------------
                // 球號垂直線
                // --------------------------------------------------
                if ($numberIndex > 0) {

                    tgDrawVerticalLine(
                        $image,
                        $cellX,
                        $rowY,
                        $rowY +
                        $rowHeight - 1,
                        $lineColor,
                        $halfLineColor
                    );
                }

                // ==================================================
                // 有開獎才畫球號
                //
                // 539 / Fantasy 5 future：
                // opened = false，所以不畫數字。
                // ==================================================
                if ($opened) {

                    $number =
                        $numbers[
                            $numberIndex
                        ];

                    $numberFontSize =
                        ($ballCount >= 7)
                        ? 18
                        : 20;

                    tgDrawCenterText(
                        $image,
                        sprintf(
                            "%02d",
                            $number
                        ),
                        $fontFile,
                        $numberFontSize,
                        $black,
                        $cellX +
                        (
                            $currentNumberWidth /
                            2
                        ),
                        $rowY +
                        (
                            $rowHeight /
                            2
                        )
                    );
                }
            }

            // ==================================================
            // 日期欄左線
            // ==================================================
            tgDrawVerticalLine(
                $image,
                $columnX,
                $rowY,
                $rowY +
                $rowHeight - 1,
                $lineColor,
                $halfLineColor
            );

            // ==================================================
            // 日期欄右線
            // ==================================================
            tgDrawVerticalLine(
                $image,
                $columnX +
                $dateWidth,
                $rowY,
                $rowY +
                $rowHeight - 1,
                $lineColor,
                $halfLineColor
            );

            // ==================================================
            // 上線
            // ==================================================
            tgDrawHorizontalLine(
                $image,
                $columnX,
                $rowY,
                $columnX +
                $columnWidth - 1,
                $lineColor,
                $halfLineColor
            );

            // ==================================================
            // 下線
            // ==================================================
            tgDrawHorizontalLine(
                $image,
                $columnX,
                $rowY +
                $rowHeight - 1,
                $columnX +
                $columnWidth - 1,
                $lineColor,
                $halfLineColor
            );
        }
    }

    // ==================================================
    // 表格外框
    // ==================================================
    $tableRightX =
        $outerPadding +
        $tableWidth -
        1;

    $tableBottomY =
        $tableStartY +
        $actualTableHeight -
        1;

    // ==================================================
    // 四排單特殊處理
    // ==================================================
    if ($layout === "four") {

        // --------------------------------------------------
        // 年份列：
        // 不畫日期內部分割線
        // --------------------------------------------------
        for (
            $column = 0;
            $column < $columns;
            $column++
        ) {

            $columnX =
                $outerPadding +
                (
                    $column *
                    (
                        $columnWidth +
                        $columnGap
                    )
                );

            for (
                $rowIndex = 0;
                $rowIndex < $rowsPerColumn;
                $rowIndex++
            ) {

                $displayRow =
                    $displayRows[$rowIndex]
                    ??
                    [
                        "type" => "empty"
                    ];

                if (
                    $displayRow["type"] ===
                    "year" ||
                    $displayRow["type"] ===
                    "empty"
                ) {

                    continue;
                }

                $rowY =
                    $tableStartY +
                    (
                        $rowIndex *
                        $rowHeight
                    );

                // --------------------------------------------------
                // 左線
                // --------------------------------------------------
                tgDrawVerticalLine(
                    $image,
                    $columnX,
                    $rowY,
                    $rowY +
                    $rowHeight - 1,
                    $lineColor,
                    $halfLineColor
                );

                // --------------------------------------------------
                // 日期欄右線
                // --------------------------------------------------
                tgDrawVerticalLine(
                    $image,
                    $columnX +
                    $dateWidth,
                    $rowY,
                    $rowY +
                    $rowHeight - 1,
                    $lineColor,
                    $halfLineColor
                );
            }
        }

        // --------------------------------------------------
        // 四欄各自外框
        // --------------------------------------------------
        for (
            $column = 0;
            $column < $columns;
            $column++
        ) {

            $columnX =
                $outerPadding +
                (
                    $column *
                    (
                        $columnWidth +
                        $columnGap
                    )
                );

            $columnRightX =
                $columnX +
                $columnWidth -
                1;

            tgDrawOuterBorder(
                $image,
                $columnX,
                $tableStartY,
                $columnRightX,
                $tableBottomY,
                $outerLineColor
            );
        }

    } else {

        // --------------------------------------------------
        // 長版本整體外框
        // --------------------------------------------------
        tgDrawOuterBorder(
            $image,
            $outerPadding,
            $tableStartY,
            $tableRightX,
            $tableBottomY,
            $outerLineColor
        );
    }

    // ==================================================
    // Footer
    // ==================================================
    $footerY =
        $tableStartY +
        $actualTableHeight;

    tgDrawCenterText(
        $image,
        "https://all-decrypt.com/　版權所有",
        $fontFile,
        15,
        $black,
        $imageWidth / 2,
        $footerY +
        ($footerHeight / 2)
    );

    // ==================================================
    // PNG 輸出
    // ==================================================
    header(
        "Content-Type: image/png"
    );

    header(
        'Content-Disposition: inline; filename="' .
        $game .
        '_ticket.png"'
    );

    header(
        "Cache-Control: public, max-age=300"
    );

    imagepng(
        $image,
        null,
        6
    );

    imagedestroy(
        $image
    );

    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8646014509722763"
     crossorigin="anonymous"></script>
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
<?= htmlspecialchars(
    $seoTitle,
    ENT_QUOTES,
    "UTF-8"
) ?>
</title>

<meta
    name="description"
    content="<?= htmlspecialchars(
        $seoDescription,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<link
    rel="canonical"
    href="<?= htmlspecialchars(
        $seoCanonical,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<meta
    property="og:type"
    content="website"
>

<meta
    property="og:locale"
    content="zh_TW"
>

<meta
    property="og:site_name"
    content="All-Decrypt"
>

<meta
    property="og:title"
    content="<?= htmlspecialchars(
        $seoTitle,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<meta
    property="og:description"
    content="<?= htmlspecialchars(
        $seoDescription,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<meta
    property="og:url"
    content="<?= htmlspecialchars(
        $seoCanonical,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<meta
    property="og:image"
    content="<?= htmlspecialchars(
        $seoImageUrl,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<meta
    name="twitter:card"
    content="summary_large_image"
>

<meta
    name="twitter:title"
    content="<?= htmlspecialchars(
        $seoTitle,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<meta
    name="twitter:description"
    content="<?= htmlspecialchars(
        $seoDescription,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<meta
    name="twitter:image"
    content="<?= htmlspecialchars(
        $seoImageUrl,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

<script type="application/ld+json">
<?= json_encode(
    [
        "@context" => "https://schema.org",
        "@type" => "WebPage",
        "name" => $seoTitle,
        "description" => $seoDescription,
        "url" => $seoCanonical,
        "inLanguage" => "zh-TW",
        "isPartOf" => [
            "@type" => "WebSite",
            "name" => "All-Decrypt",
            "url" => $siteBaseUrl . "/"
        ]
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>
</script>

<style>

*{
    box-sizing:border-box
}

html,
body{
    margin:0;
    padding:0;
    font-family:
        Arial,
        "Microsoft JhengHei",
        sans-serif;
        background: #1a1717;
        color: #000;
}

.page{
    width:100%;
    max-width:1000px;
    margin:0 auto;
    padding:20px
}

.title{
    text-align:center;
    font-size:28px;
    font-weight:bold;
    margin-bottom:20px

}

.control-panel{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:
        0 2px 10px
        rgba(0,0,0,.08)
}

.control-group{
    margin-bottom:16px
}

.control-group:last-child{
    margin-bottom:0
}

.control-group label{
    display:block;
    font-size:15px;
    font-weight:bold;
    margin-bottom:7px
}

select{
    width:100%;
    height:46px;
    border:1px solid #ccc;
    border-radius:8px;
    background:#fff;
    padding:0 12px;
    font-size:16px;
    outline:none
}

select:focus{
    border-color:#555
}

.preview-panel{
    margin-top:20px;
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:
        0 2px 10px
        rgba(0,0,0,.08)
}

.preview-title{
    text-align:center;
    font-size:20px;
    font-weight:bold;
    margin-bottom:15px
}

.preview-box{
    width:100%;
    overflow:auto;
    text-align:center;
    background:#eee;
    border-radius:8px;
    padding:10px;
    min-height:150px
}

.preview-box img{
    display:block;
    width:100%;
    max-width:700px;
    height:auto;
    margin:0 auto;
    background:#fff
}

.button-row{
    display:flex;
    gap:10px;
    margin-top:20px
}

.button{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:46px;
    border:none;
    border-radius:8px;
    background:#222;
    color:#fff;
    text-decoration:none;
    font-size:16px;
    font-weight:bold;
    cursor:pointer
}

.button:hover{
    opacity:.85
}

.button:disabled{
    opacity:.6;
    cursor:wait
}

.info{
    margin-top:15px;
    text-align:center;
    color:#666;
    font-size:14px;
    line-height:1.6
}

@media(max-width:600px){

    .page{
        padding:10px
    }

    .title{
        font-size:23px
    }

    .control-panel,
    .preview-panel{
        padding:15px
    }

    .button-row{
        flex-direction:column
    }

    .button{
        width:100%
    }

    .preview-box{
        padding:5px
    }

    .preview-box img{
        width:100%
    }

}

</style>

</head>

<body>

<div class="page">

<?php
require_once __DIR__ . "/menu.php";
?>

<h1 class="title" style="color: #FFF;">

<?= htmlspecialchars(
    $currentGame["name"],
    ENT_QUOTES,
    "UTF-8"
) ?>

開獎單產生器

</h1>

<form
    method="get"
    action="ticket_generator.php"
    class="control-panel"
>

<div class="control-group">

<label for="game">
彩種
</label>

<select
    id="game"
    name="game"
    onchange="this.form.submit()"
>

<option
    value="539"
    <?= $game === "539"
        ? "selected"
        : "" ?>
>
今彩539
</option>

<option
    value="fantasy5"
    <?= $game === "fantasy5"
        ? "selected"
        : "" ?>
>
美國天天樂
</option>

<option
    value="biglottery"
    <?= $game === "biglottery"
        ? "selected"
        : "" ?>
>
大樂透
</option>

<option
    value="marksix"
    <?= $game === "marksix"
        ? "selected"
        : "" ?>
>
香港六合彩
</option>

</select>

</div>

<div class="control-group">

<label for="order">
資料方式
</label>

<select
    id="order"
    name="order"
    onchange="this.form.submit()"
>

<option
    value="sorted"
    <?= $order === "sorted"
        ? "selected"
        : "" ?>
>
順球
</option>

<option
    value="drop"
    <?= $order === "drop"
        ? "selected"
        : "" ?>
>
落球
</option>

</select>

</div>

<div class="control-group">

<label for="layout">
版型
</label>

<select
    id="layout"
    name="layout"
    onchange="this.form.submit()"
>

<option
    value="long"
    <?= $layout === "long"
        ? "selected"
        : "" ?>
>
長版本
</option>

<option
    value="four"
    <?= $layout === "four"
        ? "selected"
        : "" ?>
>
四排單
</option>

</select>

</div>

</form>

<div class="preview-panel">

<div class="preview-title">
圖片預覽
</div>

<div class="preview-box">

<img
    id="ticket-preview"
    src="<?= htmlspecialchars(
        $imageUrl,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    alt="<?= htmlspecialchars(
        $currentGame["name"] .
        "開獎單預覽",
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    loading="eager"
>

</div>

<div class="button-row">

<button
    type="button"
    class="button"
    id="download-button"
>
下載 PNG 圖片
</button>

</div>

<div class="info">

目前：

<?= htmlspecialchars(
    $currentGame["name"],
    ENT_QUOTES,
    "UTF-8"
) ?>

／<?= $ballCount ?> 顆球

／<?= $order === "sorted"
    ? "順球"
    : "落球" ?>

／<?= $layout === "long"
    ? "長版本"
    : "四排單" ?>

</div>

</div>

</div>

<script>

(function(){

    "use strict";

    const imageUrl =
        <?= json_encode(
            $imageUrl,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ) ?>;

    const downloadButton =
        document.getElementById(
            "download-button"
        );

    const downloadFileName =
        <?= json_encode(
            $game . "_ticket.png",
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ) ?>;

    downloadButton.addEventListener(
        "click",
        async function(){

            downloadButton.disabled =
                true;

            const originalText =
                downloadButton.textContent;

            downloadButton.textContent =
                "下載中...";

            try {

                const response =
                    await fetch(
                        imageUrl,
                        {
                            method:"GET",
                            cache:"no-store"
                        }
                    );

                if (!response.ok) {

                    throw new Error(
                        "HTTP " +
                        response.status
                    );
                }

                const blob =
                    await response.blob();

                if (
                    !blob ||
                    blob.size === 0
                ) {

                    throw new Error(
                        "PNG 檔案內容為空"
                    );
                }

                const blobUrl =
                    URL.createObjectURL(
                        blob
                    );

                const link =
                    document.createElement(
                        "a"
                    );

                link.href =
                    blobUrl;

                link.download =
                    downloadFileName;

                link.style.display =
                    "none";

                document.body.appendChild(
                    link
                );

                link.click();

                link.remove();

                URL.revokeObjectURL(
                    blobUrl
                );

            } catch (error) {

                console.error(
                    "PNG download failed:",
                    error
                );

                alert(
                    "PNG 下載失敗。\n\n" +
                    "請確認網站可以正常產生圖片，" +
                    "然後再試一次。"
                );

            } finally {

                downloadButton.disabled =
                    false;

                downloadButton.textContent =
                    originalText;
            }
        }
    );

})();

</script>

</body>

</html>
