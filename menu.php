<?php

//==================================================
// menu.php
//
// 網站統一功能選單
//
// 第一層：
//   高機率版路
//   開獎結果
//   開獎單
//
// 第一層規則：
//   目前所在分類 = 藍色
//   點擊打開 = 藍色
//   三個第一層使用相同藍色
//
// 第二層：
//   依照彩種顯示不同顏色
//
// index.php 使用：
//
// ?lottery=dailycash
// ?lottery=biglottery
// ?lottery=fantasy5
// ?lottery=marksix
//
// dailycash.php 使用：
//
// ?lottery=dailycash
// ?lottery=biglottery
// ?lottery=fantasy5
// ?lottery=marksix
//
//==================================================


//==================================================
// 選單資料
//==================================================

$siteMenus =
[

    //==================================================
    // 高機率版路
    //==================================================

    [
        "title" => "高機率版路",

        "items" =>
        [

            [
                "name" => "今彩539",

                "url" =>
                    "index.php?lottery=dailycash"
            ],


            [
                "name" => "大樂透",

                "url" =>
                    "index.php?lottery=biglottery"
            ],


            [
                "name" => "天天樂",

                "url" =>
                    "index.php?lottery=fantasy5"
            ],


            [
                "name" => "六合彩",

                "url" =>
                    "index.php?lottery=marksix"
            ]

        ]

    ],


    //==================================================
    // 開獎結果
    //==================================================

    [
        "title" => "開獎結果",

        "items" =>
        [

            [
                "name" => "今彩539",

                "url" =>
                    "dailycash.php?lottery=dailycash"
            ],


            [
                "name" => "大樂透",

                "url" =>
                    "dailycash.php?lottery=biglottery"
            ],


            [
                "name" => "天天樂",

                "url" =>
                    "dailycash.php?lottery=fantasy5"
            ],


            [
                "name" => "六合彩",

                "url" =>
                    "dailycash.php?lottery=marksix"
            ]

        ]

    ],


    //==================================================
    // 開獎單
    //==================================================

    [
        "title" => "開獎單",

        "items" =>
        [

            [
                "name" => "開獎單下載",

                "url" =>
                    "ticket_generator.php"
            ]

        ]

    ],

    //==================================================
    // 開獎單
    //==================================================

    [
        "title" => "更多",

        "items" =>
        [

            [
                "name" => "關於我們",
                "url" =>
                    "aboutUs.php"
            ]


        ]

    ]

];


//==================================================
// 取得目前頁面
//==================================================

$currentMenuPage =
    basename(
        parse_url(
            $_SERVER["REQUEST_URI"] ?? "",
            PHP_URL_PATH
        )
    );


if (
    $currentMenuPage === ""
)
{

    $currentMenuPage =
        "index.php";

}


//==================================================
// 取得目前 lottery
//==================================================

$menuLotteryType =
    "dailycash";


if (
    isset($_GET["lottery"])
    &&
    is_string($_GET["lottery"])
)
{

    $menuLotteryType =
        trim(
            $_GET["lottery"]
        );

}


//==================================================
// 判斷選單項目是否為目前頁面
//==================================================

if (
    !function_exists(
        "siteMenuIsCurrentPage"
    )
)
{

    function siteMenuIsCurrentPage(
        string $url,
        string $currentPage,
        string $currentLottery
    ): bool
    {

        //==================================================
        // 取得選單 URL 的 PHP 頁面
        //==================================================

        $menuPage =
            basename(
                parse_url(
                    $url,
                    PHP_URL_PATH
                )
            );


        if (
            $menuPage === ""
        )
        {

            $menuPage =
                "index.php";

        }


        //==================================================
        // PHP 頁面不同
        //==================================================

        if (
            $menuPage !==
            $currentPage
        )
        {

            return false;

        }


        //==================================================
        // 取得 URL Query String
        //==================================================

        $query =
            parse_url(
                $url,
                PHP_URL_QUERY
            );


        $menuLottery =
            "";


        if (
            $query !== null
            &&
            $query !== ""
        )
        {

            $queryData = [];


            parse_str(
                $query,
                $queryData
            );


            if (
                isset(
                    $queryData["lottery"]
                )
                &&
                is_string(
                    $queryData["lottery"]
                )
            )
            {

                $menuLottery =
                    trim(
                        $queryData["lottery"]
                    );

            }

        }


        //==================================================
        // index.php
        //
        // 必須判斷 lottery
        //==================================================

        if (
            $menuPage ===
            "index.php"
        )
        {

            if (
                $menuLottery === ""
            )
            {

                $menuLottery =
                    "dailycash";

            }


            return
                $menuLottery ===
                $currentLottery;

        }


        //==================================================
        // dailycash.php
        //
        // 必須判斷 lottery
        //==================================================

        if (
            $menuPage ===
            "dailycash.php"
        )
        {

            if (
                $menuLottery === ""
            )
            {

                $menuLottery =
                    "dailycash";

            }


            return
                $menuLottery ===
                $currentLottery;

        }


        //==================================================
        // 其他 PHP 頁面
        //
        // 例如：
        // ticket_generator.php
        //
        // 只判斷 PHP 頁面
        //==================================================

        return true;

    }

}


//==================================================
// 取得 URL 裡面的 lottery
//==================================================

if (
    !function_exists(
        "siteMenuGetLotteryFromUrl"
    )
)
{

    function siteMenuGetLotteryFromUrl(
        string $url
    ): string
    {

        $query =
            parse_url(
                $url,
                PHP_URL_QUERY
            );


        if (
            $query === null
            ||
            $query === ""
        )
        {

            return "";

        }


        $queryData = [];


        parse_str(
            $query,
            $queryData
        );


        if (
            !isset(
                $queryData["lottery"]
            )
            ||
            !is_string(
                $queryData["lottery"]
            )
        )
        {

            return "";

        }


        return
            trim(
                $queryData["lottery"]
            );

    }

}


//==================================================
// 判斷分類是否包含目前頁面
//
// 這個函式非常重要
//
// 用來決定第一層目前分類是否變藍
//
//==================================================

if (
    !function_exists(
        "siteMenuHasCurrentPage"
    )
)
{

    function siteMenuHasCurrentPage(
        array $menu,
        string $currentPage,
        string $currentLottery
    ): bool
    {

        if (
            !isset(
                $menu["items"]
            )
            ||
            !is_array(
                $menu["items"]
            )
        )
        {

            return false;

        }


        foreach (
            $menu["items"]
            as $item
        )
        {

            if (
                !isset(
                    $item["url"]
                )
                ||
                !is_string(
                    $item["url"]
                )
            )
            {

                continue;

            }


            if (
                siteMenuIsCurrentPage(
                    $item["url"],
                    $currentPage,
                    $currentLottery
                )
            )
            {

                return true;

            }

        }


        return false;

    }

}

?>


<!--==================================================
     統一功能選單
==================================================-->

<nav
    class="site-menu"
    aria-label="網站功能選單"
>


<div class="site-menu-inner">


<?php foreach (
    $siteMenus
    as $menu
): ?>


<?php

//==================================================
// 取得第一層標題
//==================================================

$menuTitle =
    isset(
        $menu["title"]
    )
    ?
    $menu["title"]
    :
    "功能";


//==================================================
// 取得第二層項目
//==================================================

$menuItems =
    isset(
        $menu["items"]
    )
    &&
    is_array(
        $menu["items"]
    )
    ?
    $menu["items"]
    :
    [];


//==================================================
// 判斷目前第一層分類
//
// 例如：
//
// dailycash.php?lottery=dailycash
//
// 就會讓：
//
// 開獎結果
//
// 加上 current-section
//
//==================================================

$hasCurrentPage =
    siteMenuHasCurrentPage(
        $menu,
        $currentMenuPage,
        $menuLotteryType
    );


//==================================================
// 第一層 CSS Class
//==================================================

$menuCurrentClass =
    $hasCurrentPage
    ?
    " current-section"
    :
    "";

?>


<div
    class="menu-dropdown<?= $menuCurrentClass ?>"
>


<button
    type="button"
    class="menu-dropdown-button"
    aria-expanded="false"
    aria-haspopup="true"
>


<span class="menu-dropdown-title">

<?= htmlspecialchars(
    $menuTitle,
    ENT_QUOTES,
    "UTF-8"
) ?>

</span>


<span class="menu-dropdown-arrow">

▼

</span>


</button>


<div class="menu-dropdown-content">


<?php if (
    count(
        $menuItems
    ) > 0
): ?>


<?php foreach (
    $menuItems
    as $item
): ?>


<?php

//==================================================
// 項目名稱
//==================================================

$itemName =
    isset(
        $item["name"]
    )
    ?
    $item["name"]
    :
    "";


//==================================================
// 項目 URL
//==================================================

$itemUrl =
    isset(
        $item["url"]
    )
    ?
    $item["url"]
    :
    "#";


//==================================================
// 判斷第二層目前項目
//==================================================

$itemCurrent =
    siteMenuIsCurrentPage(
        $itemUrl,
        $currentMenuPage,
        $menuLotteryType
    );


//==================================================
// 取得 URL 裡面的 lottery
//==================================================

$itemLottery =
    siteMenuGetLotteryFromUrl(
        $itemUrl
    );


//==================================================
// 彩種 CSS Class
//==================================================

$itemLotteryClass =
    "";


if (
    $itemLottery !== ""
)
{

    $safeLotteryClass =
        preg_replace(
            "/[^a-zA-Z0-9_-]/",
            "",
            $itemLottery
        );


    if (
        $safeLotteryClass !== ""
    )
    {

        $itemLotteryClass =
            " lottery-" .
            $safeLotteryClass;

    }

}

?>


<a
    href="<?= htmlspecialchars(
        $itemUrl,
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    class="
        menu-dropdown-item
        <?= $itemCurrent
            ? "current"
            : ""
        ?>
        <?= htmlspecialchars(
            $itemLotteryClass,
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    "
    <?= $itemCurrent
        ? 'aria-current="page"'
        : ""
    ?>
>


<?= htmlspecialchars(
    $itemName,
    ENT_QUOTES,
    "UTF-8"
) ?>


</a>


<?php endforeach; ?>


<?php else: ?>


<div class="menu-dropdown-empty">

目前沒有功能

</div>


<?php endif; ?>


</div>


</div>


<?php endforeach; ?>


</div>


</nav>


<!--==================================================
     選單 CSS
==================================================-->

<style>


/*==================================================
  整體選單
==================================================*/

.site-menu
{
    width: 100%;

    margin:
        0
        0
        15px
        0;

    padding: 0;

    box-sizing: border-box;
}


/*==================================================
  第一層排列
==================================================*/

.site-menu-inner
{
    display: flex;

    align-items: center;

    justify-content: flex-start;

    gap: 10px;

    width: 100%;

    flex-wrap: wrap;

    box-sizing: border-box;
}


/*==================================================
  第一層容器
==================================================*/

.menu-dropdown
{
    position: relative;

    display: inline-block;
}


/*==================================================
  第一層按鈕
==================================================*/

.menu-dropdown-button
{
    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    min-height: 42px;

    padding:
        8px
        18px;

    border:
        1px
        solid
        #d1d5db;

    border-radius: 6px;

    background: #ffffff;

    color: #222222;

    font-family:
        Arial,
        "Microsoft JhengHei",
        sans-serif;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;

    white-space: nowrap;

    box-sizing: border-box;

    transition:
        background 0.2s ease,
        border-color 0.2s ease,
        color 0.2s ease,
        transform 0.1s ease;
}


/*==================================================
  第一層一般 Hover
==================================================*/

.menu-dropdown-button:hover
{
    background: #f3f4f6;

    border-color: #b8bec7;

    color: #222222;
}


/*==================================================
  第一層按下
==================================================*/

.menu-dropdown-button:active
{
    transform:
        scale(0.97);
}


/*==================================================
  第一層目前所在分類
//
//  這就是本次修正的重點
//
//  例如：
//  dailycash.php
//
//  開獎結果
//
//  就會套用這個顏色
//
//==================================================*/

.menu-dropdown.current-section
.menu-dropdown-button
{
    background: #007bff !important;

    border-color: #007bff !important;

    color: #ffffff !important;
}


/*==================================================
  第一層目前所在分類 Hover
==================================================*/

.menu-dropdown.current-section
.menu-dropdown-button:hover
{
    background: #0056b3 !important;

    border-color: #0056b3 !important;

    color: #ffffff !important;
}


/*==================================================
  第一層目前打開
//
//  不管是不是目前所在分類
//
//  只要點開就藍色
//
//==================================================*/

.menu-dropdown.open
.menu-dropdown-button
{
    background: #007bff !important;

    border-color: #007bff !important;

    color: #ffffff !important;
}


/*==================================================
  第一層目前打開 Hover
==================================================*/

.menu-dropdown.open
.menu-dropdown-button:hover
{
    background: #0056b3 !important;

    border-color: #0056b3 !important;

    color: #ffffff !important;
}


/*==================================================
  箭頭
==================================================*/

.menu-dropdown-arrow
{
    font-size: 10px;

    transition:
        transform 0.2s ease;
}


/*==================================================
  打開時箭頭旋轉
==================================================*/

.menu-dropdown.open
.menu-dropdown-arrow
{
    transform:
        rotate(180deg);
}


/*==================================================
  第二層下拉內容
==================================================*/

.menu-dropdown-content
{
    position: absolute;

    top:
        calc(
            100% + 5px
        );

    left: 0;

    min-width: 170px;

    padding: 5px;

    border:
        1px
        solid
        #d1d5db;

    border-radius: 7px;

    background: #ffffff;

    box-shadow:
        0
        5px
        18px
        rgba(
            0,
            0,
            0,
            0.15
        );

    opacity: 0;

    visibility: hidden;

    transform:
        translateY(-5px);

    transition:
        opacity 0.15s ease,
        visibility 0.15s ease,
        transform 0.15s ease;

    z-index: 99999;
}


/*==================================================
  打開時顯示第二層
==================================================*/

.menu-dropdown.open
.menu-dropdown-content
{
    opacity: 1;

    visibility: visible;

    transform:
        translateY(0);
}


/*==================================================
  第二層項目
==================================================*/

.menu-dropdown-item
{
    display: block;

    width: 100%;

    padding:
        10px
        13px;

    border-radius: 5px;

    color: #222222;

    text-decoration: none;

    font-size: 14px;

    line-height: 1.4;

    white-space: nowrap;

    box-sizing: border-box;

    transition:
        background 0.15s ease,
        color 0.15s ease;
}


/*==================================================
  第二層 Hover
==================================================*/

.menu-dropdown-item:hover
{
    background: #f0f4fa;

    color: #007bff;
}


/*==================================================
  第二層目前項目
==================================================*/

.menu-dropdown-item.current
{
    color: #ffffff;

    font-weight: bold;
}


/*==================================================
  今彩539
==================================================*/

.menu-dropdown-item.lottery-dailycash.current
{
    background: #28a745;

    color: #ffffff;
}


.menu-dropdown-item.lottery-dailycash.current:hover
{
    background: #218838;

    color: #ffffff;
}


/*==================================================
  大樂透
==================================================*/

.menu-dropdown-item.lottery-biglottery.current
{
    background: #007bff;

    color: #ffffff;
}


.menu-dropdown-item.lottery-biglottery.current:hover
{
    background: #0056b3;

    color: #ffffff;
}


/*==================================================
  天天樂
==================================================*/

.menu-dropdown-item.lottery-fantasy5.current
{
    background: #fd7e14;

    color: #ffffff;
}


.menu-dropdown-item.lottery-fantasy5.current:hover
{
    background: #e66a00;

    color: #ffffff;
}


/*==================================================
  六合
==================================================*/

.menu-dropdown-item.lottery-marksix.current
{
    background: #dc3545;

    color: #ffffff;
}


.menu-dropdown-item.lottery-marksix.current:hover
{
    background: #c82333;

    color: #ffffff;
}


/*==================================================
  沒有資料
==================================================*/

.menu-dropdown-empty
{
    padding:
        10px
        13px;

    color: #777777;

    font-size: 14px;

    white-space: nowrap;
}


/*==================================================
  Mobile
==================================================*/

@media (max-width: 600px)
{

    .site-menu
    {
        margin-bottom: 10px;
    }


    .site-menu-inner
    {
        gap: 6px;

        flex-wrap: nowrap;
    }


    .menu-dropdown
    {
        flex:
            1 1 0;

        min-width: 0;
    }


    .menu-dropdown-button
    {
        width: 100%;

        min-height: 40px;

        padding:
            7px
            8px;

        gap: 4px;

        font-size: 13px;
    }


    .menu-dropdown-title
    {
        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;
    }


    .menu-dropdown-arrow
    {
        flex:
            0 0 auto;

        font-size: 8px;
    }


    .menu-dropdown-content
    {
        min-width: 100%;

        max-width:
            calc(
                100vw - 16px
            );
    }


    .menu-dropdown-item
    {
        padding:
            9px
            10px;

        font-size: 13px;
    }

}

</style>


<!--==================================================
     選單 JavaScript
==================================================-->

<script>

(function()
{

    //==================================================
    // 取得所有第一層選單
    //==================================================

    const dropdowns =
        document.querySelectorAll(
            ".menu-dropdown"
        );


    //==================================================
    // 關閉全部
    //==================================================

    function closeAllDropdowns()
    {

        dropdowns.forEach(
            function(dropdown)
            {

                dropdown.classList.remove(
                    "open"
                );


                const button =
                    dropdown.querySelector(
                        ".menu-dropdown-button"
                    );


                if (button)
                {

                    button.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            }
        );

    }


    //==================================================
    // 綁定第一層按鈕
    //==================================================

    dropdowns.forEach(
        function(dropdown)
        {

            const button =
                dropdown.querySelector(
                    ".menu-dropdown-button"
                );


            if (!button)
            {
                return;
            }


            button.addEventListener(
                "click",
                function(event)
                {

                    event.stopPropagation();


                    const isOpen =
                        dropdown.classList.contains(
                            "open"
                        );


                    //==================================================
                    // 先關閉全部
                    //==================================================

                    closeAllDropdowns();


                    //==================================================
                    // 原本沒開
                    // 就打開
                    //==================================================

                    if (!isOpen)
                    {

                        dropdown.classList.add(
                            "open"
                        );


                        button.setAttribute(
                            "aria-expanded",
                            "true"
                        );

                    }

                }
            );

        }
    );


    //==================================================
    // 點擊選單外面
    //==================================================

    document.addEventListener(
        "click",
        function()
        {

            closeAllDropdowns();

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

                closeAllDropdowns();

            }

        }
    );

})();

</script>
