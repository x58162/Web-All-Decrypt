
<?php

//==================================================
// All-Decrypt｜關於我們
//==================================================

//==================================================
// Database
//==================================================

require_once __DIR__ . "/config.php";


//==================================================
// Page SEO
//==================================================

$pageTitle =
    "關於 All-Decrypt";

$pageDescription =
    "All-Decrypt 資訊交流平台，歡迎加入官方 LINE，一起交流、討論、分享與研究。";

$canonicalUrl =
    "https://all-decrypt.com/about.php";


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
        "All-Decrypt",

    "url" =>
        $canonicalUrl,

    "description" =>
        $pageDescription
];

?>

<!DOCTYPE html>

<html lang="zh-Hant">

<head>



  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8646014509722763"
       crossorigin="anonymous"></script>
<meta charset="UTF-8">


<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


<title>
<?= htmlspecialchars(
    $pageTitle,
    ENT_QUOTES,
    "UTF-8"
) ?>
</title>


<meta
    name="description"
    content="<?= htmlspecialchars(
        $pageDescription,
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
    content="關於 All-Decrypt"
>


<meta
    property="og:description"
    content="<?= htmlspecialchars(
        $pageDescription,
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


<link
    rel="icon"
    type="image/x-icon"
    href="/favicon.ico"
>


<link
    rel="stylesheet"
    href="style.css"
>


<script type="application/ld+json">

<?= json_encode(
    $websiteSchema,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_PRETTY_PRINT
) ?>

</script>


<style>

/*==================================================
  All-Decrypt About Page
==================================================*/

.ad-about-page
{
    position: relative;

    width: 100%;

    min-height: calc(100vh - 80px);

    box-sizing: border-box;

    overflow: hidden;

    padding:
        70px
        20px
        80px;

    background:
        radial-gradient(
            circle at 50% 0%,
            rgba(
                90,
                70,
                180,
                0.16
            ),
            transparent 45%
        ),
        linear-gradient(
            180deg,
            #0d0d12 0%,
            #09090d 100%
        );

    color: #ffffff;
}


/*==================================================
  背景光效
==================================================*/

.ad-about-background
{
    position: absolute;

    inset: 0;

    overflow: hidden;

    pointer-events: none;
}


.ad-about-glow
{
    position: absolute;

    width: 380px;

    height: 380px;

    border-radius: 50%;

    filter: blur(110px);

    opacity: 0.12;
}


.ad-about-glow-1
{
    top: -180px;

    left: -120px;

    background: #6846ff;
}


.ad-about-glow-2
{
    right: -130px;

    bottom: -160px;

    background: #008cff;
}


/*==================================================
  小標
==================================================*/

.ad-about-label
{
    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    margin-bottom: 18px;

    color: #9999b5;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: 3px;
}


.ad-about-label-dot
{
    width: 7px;

    height: 7px;

    border-radius: 50%;

    background: #7658ff;

    box-shadow:
        0 0 10px
        rgba(
            118,
            88,
            255,
            0.9
        ),

        0 0 20px
        rgba(
            118,
            88,
            255,
            0.5
        );
}


/*==================================================
  主標題
==================================================*/

.ad-about-title
{
    position: relative;

    z-index: 2;

    margin: 0;

    text-align: center;

    color: #ffffff;

    font-size:
        clamp(
            36px,
            6vw,
            62px
        );

    line-height: 1.15;

    font-weight: 800;

    letter-spacing: -1px;
}


.ad-about-title-highlight
{
    display: inline-block;

    background:
        linear-gradient(
            90deg,
            #8c6cff,
            #5e9dff,
            #8c6cff
        );

    -webkit-background-clip: text;

    background-clip: text;

    color: transparent;

    background-size: 200% auto;

    animation:
        ad-about-gradient 5s
        linear infinite;
}


@keyframes ad-about-gradient
{
    0%
    {
        background-position:
            0% center;
    }

    100%
    {
        background-position:
            200% center;
    }
}


/*==================================================
  副標題
==================================================*/

.ad-about-subtitle
{
    position: relative;

    z-index: 2;

    margin:
        18px
        0
        48px;

    text-align: center;

    color: #9292a8;

    font-size: 17px;

    letter-spacing: 3px;
}


/*==================================================
  主要容器
==================================================*/

.ad-about-container
{
    position: relative;

    z-index: 2;

    width: 100%;

    max-width: 1100px;

    margin: 0 auto;
}


/*==================================================
  關於我們 Card
==================================================*/

.ad-about-intro
{
    display: flex;

    align-items: center;

    gap: 30px;

    padding: 35px;

    margin-bottom: 25px;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );

    border-radius: 22px;

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
        0 25px 70px
        rgba(
            0,
            0,
            0,
            0.35
        );

    backdrop-filter:
        blur(15px);

    box-sizing: border-box;
}


/*==================================================
  Intro Icon
==================================================*/

.ad-about-intro-icon
{
    flex-shrink: 0;

    width: 82px;

    height: 82px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            rgba(
                119,
                88,
                255,
                0.25
            ),
            rgba(
                47,
                143,
                255,
                0.12
            )
        );

    border:
        1px solid
        rgba(
            135,
            110,
            255,
            0.25
        );

    box-shadow:
        0 0 30px
        rgba(
            90,
            70,
            255,
            0.12
        );

    font-size: 38px;
}


/*==================================================
  Intro Content
==================================================*/

.ad-about-intro-content
{
    min-width: 0;
}


.ad-about-intro-content h2
{
    margin:
        0
        0
        12px;

    color: #ffffff;

    font-size: 25px;

    font-weight: 700;
}


.ad-about-intro-content p
{
    margin:
        0
        0
        10px;

    color: #a7a7b8;

    font-size: 15px;

    line-height: 1.85;
}


.ad-about-intro-content p:last-child
{
    margin-bottom: 0;
}


/*==================================================
  LINE 區塊
==================================================*/

.ad-about-line
{
    position: relative;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 30px;

    padding:
        30px
        32px;

    margin-bottom: 25px;

    border:
        1px solid
        rgba(
            48,
            220,
            122,
            0.22
        );

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            rgba(
                41,
                208,
                115,
                0.12
            ),
            rgba(
                255,
                255,
                255,
                0.035
            )
        );

    box-shadow:
        0 20px 60px
        rgba(
            0,
            0,
            0,
            0.3
        );

    overflow: hidden;

    box-sizing: border-box;
}


.ad-about-line::before
{
    content: "";

    position: absolute;

    width: 280px;

    height: 280px;

    right: -120px;

    top: -140px;

    border-radius: 50%;

    background:
        rgba(
            48,
            220,
            122,
            0.12
        );

    filter: blur(45px);

    pointer-events: none;
}


/*==================================================
  LINE 左側
==================================================*/

.ad-about-line-left
{
    position: relative;

    display: flex;

    align-items: flex-start;

    gap: 20px;

    min-width: 0;
}


.ad-about-line-logo
{
    width: 68px;

    height: 68px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 19px;

    background: #06c755;

    color: #ffffff;

    font-size: 14px;

    font-weight: 900;

    letter-spacing: 1px;

    box-shadow:
        0 8px 30px
        rgba(
            6,
            199,
            85,
            0.28
        );
}


.ad-about-line-small
{
    margin-bottom: 5px;

    color: #54d98b;

    font-size: 11px;

    font-weight: 700;

    letter-spacing: 2px;
}


.ad-about-line-text h2
{
    margin:
        0
        0
        7px;

    color: #ffffff;

    font-size: 22px;

    font-weight: 700;
}


.ad-about-line-text p
{
    margin: 0;

    color: #9999a9;

    font-size: 14px;

    line-height: 1.65;
}


/*==================================================
  LINE ID
==================================================*/

.ad-about-line-id
{
    display: inline-flex;

    align-items: center;

    gap: 9px;

    margin-top: 15px;

    padding:
        8px
        9px
        8px
        13px;

    border-radius: 10px;

    background:
        rgba(
            0,
            0,
            0,
            0.25
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.08
        );
}


.ad-about-line-id-label
{
    color: #777789;

    font-size: 11px;

    letter-spacing: 1px;
}


.ad-about-line-id-value
{
    color: #6ee89b;

    font-size: 15px;

    font-weight: 700;

    letter-spacing: 0.5px;

    font-family:
        Arial,
        sans-serif;
}


/*==================================================
  複製按鈕
==================================================*/

.ad-about-copy
{
    border: 0;

    padding:
        5px
        10px;

    border-radius: 7px;

    background:
        rgba(
            6,
            199,
            85,
            0.14
        );

    color: #6ee89b;

    font-size: 11px;

    cursor: pointer;

    transition:
        background 0.2s ease,
        transform 0.2s ease;
}


.ad-about-copy:hover
{
    background:
        rgba(
            6,
            199,
            85,
            0.25
        );

    transform:
        translateY(-1px);
}


/*==================================================
  LINE 右側
==================================================*/

.ad-about-line-right
{
    position: relative;

    flex-shrink: 0;

    display: flex;

    flex-direction: column;

    align-items: stretch;

    gap: 10px;

    min-width: 250px;
}


/*==================================================
  LINE 加入按鈕
==================================================*/

.ad-about-line-button
{
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    width: 250px;

    box-sizing: border-box;

    padding:
        14px
        20px;

    border-radius: 12px;

    background: #06c755;

    color: #ffffff;

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

    box-shadow:
        0 8px 25px
        rgba(
            6,
            199,
            85,
            0.25
        );

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease,
        background 0.25s ease;
}


.ad-about-line-button:hover
{
    transform:
        translateY(-3px);

    background: #08d45d;

    box-shadow:
        0 14px 35px
        rgba(
            6,
            199,
            85,
            0.35
        );
}


.ad-about-line-button:active
{
    transform:
        translateY(0);
}


.ad-about-line-button-icon
{
    width: 28px;

    height: 28px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px;

    background:
        rgba(
            255,
            255,
            255,
            0.18
        );

    font-size: 8px;

    font-weight: 900;
}


/*==================================================
  直接搜尋
==================================================*/

.ad-about-line-search
{
    display: flex;

    align-items: center;

    justify-content: center;

    flex-wrap: wrap;

    gap: 6px;

    padding:
        10px
        12px;

    border-radius: 10px;

    background:
        rgba(
            0,
            0,
            0,
            0.22
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.06
        );

    color: #858597;

    font-size: 11px;

    line-height: 1.5;

    text-align: center;
}


.ad-about-line-search strong
{
    color: #65df91;

    font-size: 12px;

    letter-spacing: 0.5px;
}


.ad-about-search-icon
{
    color: #54d98b;

    font-size: 17px;

    line-height: 10px;
}


/*==================================================
  Features
==================================================*/

.ad-about-features
{
    display: grid;

    grid-template-columns:
        repeat(
            4,
            1fr
        );

    gap: 15px;

    margin-bottom: 35px;
}


.ad-about-feature
{
    padding:
        28px
        20px;

    text-align: center;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.07
        );

    border-radius: 18px;

    background:
        rgba(
            255,
            255,
            255,
            0.025
        );

    transition:
        transform 0.25s ease,
        border-color 0.25s ease,
        background 0.25s ease;
}


.ad-about-feature:hover
{
    transform:
        translateY(-5px);

    border-color:
        rgba(
            126,
            101,
            255,
            0.35
        );

    background:
        rgba(
            126,
            101,
            255,
            0.055
        );
}


.ad-about-feature-icon
{
    margin-bottom: 15px;

    font-size: 28px;
}


.ad-about-feature h3
{
    margin:
        0
        0
        9px;

    color: #ffffff;

    font-size: 17px;
}


.ad-about-feature p
{
    margin: 0;

    color: #858597;

    font-size: 13px;

    line-height: 1.7;
}




/*==================================================
  Footer
==================================================*/

.ad-about-footer
{
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 20px;

    margin-top: 35px;

    color: #777789;

    text-align: center;

    font-size: 13px;

    line-height: 1.7;
}


.ad-about-footer strong
{
    color: #aaaabd;
}


.ad-about-footer-line
{
    width: 80px;

    height: 1px;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(
                255,
                255,
                255,
                0.15
            )
        );
}


.ad-about-footer-line:last-child
{
    background:
        linear-gradient(
            90deg,
            rgba(
                255,
                255,
                255,
                0.15
            ),
            transparent
        );
}


/*==================================================
  Tablet
==================================================*/

@media (
    max-width: 800px
)
{

    .ad-about-page
    {
        padding:
            60px
            15px
            70px;
    }


    .ad-about-intro
    {
        flex-direction: column;

        align-items: flex-start;

        padding: 28px;
    }


    .ad-about-line
    {
        flex-direction: column;

        align-items: stretch;

        padding: 26px;
    }


    .ad-about-line-right
    {
        width: 100%;

        min-width: 0;
    }


    .ad-about-line-button
    {
        width: 100%;
    }


    .ad-about-features
    {
        grid-template-columns:
            repeat(
                2,
                1fr
            );
    }

}


/*==================================================
  Mobile
==================================================*/

@media (
    max-width: 500px
)
{

    .ad-about-page
    {
        padding:
            50px
            12px
            60px;
    }


    .ad-about-title
    {
        font-size: 36px;
    }


    .ad-about-subtitle
    {
        margin:
            15px
            0
            35px;

        font-size: 13px;

        letter-spacing: 2px;
    }


    .ad-about-intro
    {
        padding: 23px;

        border-radius: 18px;
    }


    .ad-about-intro-icon
    {
        width: 65px;

        height: 65px;

        border-radius: 17px;

        font-size: 30px;
    }


    .ad-about-intro-content h2
    {
        font-size: 21px;
    }


    .ad-about-intro-content p
    {
        font-size: 14px;
    }


    .ad-about-line
    {
        padding: 22px;

        border-radius: 18px;
    }


    .ad-about-line-left
    {
        gap: 14px;
    }


    .ad-about-line-logo
    {
        width: 52px;

        height: 52px;

        border-radius: 15px;

        font-size: 12px;
    }


    .ad-about-line-text h2
    {
        font-size: 18px;
    }


    .ad-about-line-text p
    {
        font-size: 13px;
    }


    .ad-about-line-id
    {
        margin-top: 12px;

        flex-wrap: wrap;
    }


    .ad-about-features
    {
        grid-template-columns:
            1fr;
    }


    .ad-about-feature
    {
        padding: 22px;
    }


    .ad-about-footer
    {
        gap: 10px;
    }


    .ad-about-footer-line
    {
        width: 35px;
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

</header>


<!--==================================================
     About Page
==================================================-->

<main class="ad-about-page">


    <!-- 背景光效 -->

    <div class="ad-about-background">

        <div
            class="ad-about-glow ad-about-glow-1"
        ></div>

        <div
            class="ad-about-glow ad-about-glow-2"
        ></div>

    </div>


    <div class="ad-about-container">


        <!--================================================
             Section Label
        =================================================-->

        <div class="ad-about-label">

            <span
                class="ad-about-label-dot"
            ></span>

            ALL-DECRYPT COMMUNITY

        </div>


        <!--================================================
             Title
        =================================================-->

        <h1 class="ad-about-title">

            歡迎來到

            <span
                class="ad-about-title-highlight"
            >
                All-Decrypt
            </span>

        </h1>


        <p class="ad-about-subtitle">

            一起交流・一起分享・一起研究

        </p>


        <!--================================================
             About
        =================================================-->

        <section class="ad-about-intro">


            <div class="ad-about-intro-icon">

                🔐

            </div>


            <div class="ad-about-intro-content">


                <h2>

                    關於 All-Decrypt

                </h2>


                <p>

                    All-Decrypt 致力於打造一個簡單、
                    實用且方便的資訊交流平台，
                    讓大家可以在這裡瀏覽資料、
                    研究數據、交流想法，
                    並持續探索更多有趣的內容。

                </p>


                <p>

                    我們相信，一個好的平台不只是提供資料，
                    更重要的是讓使用者之間能夠互相交流、
                    分享經驗與想法。

                </p>

            </div>

        </section>


        <!--================================================
             LINE
        =================================================-->

        <section class="ad-about-line">


            <!-- 左側 -->

            <div class="ad-about-line-left">


                <div class="ad-about-line-logo">

                    LINE

                </div>


                <div class="ad-about-line-text">


                    <div class="ad-about-line-small">

                        OFFICIAL LINE

                    </div>


                    <h2>

                        加入 All-Decrypt 官方 LINE

                    </h2>


                    <p>

                        歡迎加入官方 LINE，
                        一起交流、討論與分享。

                    </p>


                    <!-- LINE ID -->

                    <div class="ad-about-line-id">


                        <span
                            class="ad-about-line-id-label"
                        >

                            LINE ID

                        </span>


                        <strong
                            class="ad-about-line-id-value"
                        >

                            @597zygyo

                        </strong>


                        <button
                            type="button"
                            class="ad-about-copy"
                            onclick="adCopyLineId()"
                        >

                            複製 ID

                        </button>


                    </div>

                </div>

            </div>


            <!-- 右側 -->

            <div class="ad-about-line-right">


                <!-- 直接加入 -->

                <a
                    href="https://lin.ee/wla0sPp"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ad-about-line-button"
                >

                    <span
                        class="ad-about-line-button-icon"
                    >

                        LINE

                    </span>


                    <span>

                        直接加入官方 LINE

                    </span>

                </a>


                <!-- 直接搜尋 -->

                <div
                    class="ad-about-line-search"
                >

                    <span
                        class="ad-about-search-icon"
                    >

                        ⌕

                    </span>


                    <span>

                        也可以在 LINE 直接搜尋

                    </span>


                    <strong>

                        @597zygyo

                    </strong>

                </div>

            </div>

        </section>


        <!--================================================
             Features
        =================================================-->

        <section class="ad-about-features">


            <!-- 交流 -->

            <div class="ad-about-feature">

                <div
                    class="ad-about-feature-icon"
                >

                    💬

                </div>


                <h3>

                    交流討論

                </h3>


                <p>

                    和大家一起交流想法，
                    分享使用心得。

                </p>

            </div>


            <!-- 數據 -->

            <div class="ad-about-feature">

                <div
                    class="ad-about-feature-icon"
                >

                    📊

                </div>


                <h3>

                    數據研究

                </h3>


                <p>

                    分享資料與研究結果，
                    一起探索更多可能性。

                </p>

            </div>


            <!-- 建議 -->

            <div class="ad-about-feature">

                <div
                    class="ad-about-feature-icon"
                >

                    💡

                </div>


                <h3>

                    功能建議

                </h3>


                <p>

                    歡迎提出網站建議，
                    讓 All-Decrypt 持續進步。

                </p>

            </div>


            <!-- 成長 -->

            <div class="ad-about-feature">

                <div
                    class="ad-about-feature-icon"
                >

                    🤝

                </div>


                <h3>

                    共同成長

                </h3>


                <p>

                    因為有大家的參與，
                    All-Decrypt 才能越來越好。

                </p>

            </div>

        </section>



        <!--================================================
             Footer
        =================================================-->

        <div class="ad-about-footer">


            <div
                class="ad-about-footer-line"
            ></div>


            <p>

                <strong>
                    All-Decrypt
                </strong>

                <br>

                讓資料更容易理解，
                讓交流更加簡單。

            </p>


            <div
                class="ad-about-footer-line"
            ></div>


        </div>


    </div>

</main>


</div>


<!--==================================================
     JavaScript
==================================================-->

<script>


//==================================================
// 複製 LINE ID
//==================================================

function adCopyLineId()
{

    const lineId =
        "@597zygyo";


    //================================================
    // 現代瀏覽器
    //================================================

    if (
        navigator.clipboard &&
        window.isSecureContext
    )
    {

        navigator.clipboard
            .writeText(
                lineId
            )
            .then(
                function()
                {

                    adCopyLineIdSuccess();

                }
            )
            .catch(
                function()
                {

                    adCopyLineIdFallback(
                        lineId
                    );

                }
            );

    }
    else
    {

        adCopyLineIdFallback(
            lineId
        );

    }

}


//==================================================
// 複製成功
//==================================================

function adCopyLineIdSuccess()
{

    const button =
        document.querySelector(
            ".ad-about-copy"
        );


    if (
        !button
    )
    {
        return;
    }


    const originalText =
        button.textContent;


    button.textContent =
        "已複製 ✓";


    button.style.background =
        "rgba(6,199,85,0.30)";


    setTimeout(
        function()
        {

            button.textContent =
                originalText;


            button.style.background =
                "";

        },
        1500
    );

}


//==================================================
// 備用複製方式
//==================================================

function adCopyLineIdFallback(
    text
)
{

    const textarea =
        document.createElement(
            "textarea"
        );


    textarea.value =
        text;


    textarea.style.position =
        "fixed";


    textarea.style.left =
        "-9999px";


    textarea.style.top =
        "0";


    document.body.appendChild(
        textarea
    );


    textarea.focus();

    textarea.select();


    try
    {

        document.execCommand(
            "copy"
        );


        adCopyLineIdSuccess();

    }
    catch (
        error
    )
    {

        alert(
            "LINE ID：@597zygyo"
        );

    }


    document.body.removeChild(
        textarea
    );

}

</script>


</body>

</html>
