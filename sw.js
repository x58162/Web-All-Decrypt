
//==================================================
// All-Decrypt Service Worker
//==================================================
//
// Site:
// https://all-decrypt.com
//
//==================================================


//==================================================
// Site Settings
//==================================================

const SITE_ORIGIN =
    "https://all-decrypt.com";


const DEFAULT_URL =
    SITE_ORIGIN +
    "/index.php";


//==================================================
// Install
//==================================================

self.addEventListener(
    "install",
    function(event)
    {
        console.log(
            "[SW] Installed"
        );

        self.skipWaiting();
    }
);


//==================================================
// Activate
//==================================================

self.addEventListener(
    "activate",
    function(event)
    {
        console.log(
            "[SW] Activated"
        );

        event.waitUntil(
            self.clients.claim()
        );
    }
);


//==================================================
// Push Notification
//==================================================

self.addEventListener(
    "push",
    function(event)
    {
        console.log(
            "[SW] Push received"
        );


        //==================================================
        // Default Data
        //==================================================

        let data =
        {
            title:
                "539經典版路高機率",

            body:
                "網站有新的更新",

            url:
                DEFAULT_URL,

            lottery_type:
                "",

            icon:
                SITE_ORIGIN +
                "/favicon.ico",

            badge:
                SITE_ORIGIN +
                "/favicon.ico"
        };


        //==================================================
        // Read Push JSON
        //==================================================

        if (
            event.data
        )
        {
            try
            {
                const json =
                    event.data.json();


                console.log(
                    "[SW] Raw Push JSON:",
                    json
                );


                //==================================================
                // 直接合併最外層資料
                //==================================================

                data =
                {
                    ...data,
                    ...json
                };


                console.log(
                    "[SW] Push data:",
                    data
                );
            }
            catch (error)
            {
                console.log(
                    "[SW] JSON parse error:",
                    error
                );
            }
        }


        //==================================================
        // Lottery Type
        //==================================================

        const lotteryType =
            typeof data.lottery_type === "string"
                ? data.lottery_type.trim()
                : "";


        console.log(
            "[SW] Lottery Type:",
            lotteryType
        );


        //==================================================
        // Get URL
        //==================================================

        let notificationUrl =
            typeof data.url === "string"
                ? data.url.trim()
                : "";


        //==================================================
        // 如果 PHP 沒有傳 URL
        //
        // 就使用 lottery_type 自動建立
        //
        //==================================================

        if (
            notificationUrl === ""
            &&
            lotteryType !== ""
        )
        {
            notificationUrl =
                DEFAULT_URL
                +
                "?lottery="
                +
                encodeURIComponent(
                    lotteryType
                );
        }


        //==================================================
        // Convert URL to absolute URL
        //==================================================

        try
        {
            notificationUrl =
                new URL(
                    notificationUrl ||
                    DEFAULT_URL,
                    SITE_ORIGIN
                ).href;
        }
        catch (error)
        {
            console.log(
                "[SW] URL parse error:",
                error
            );


            notificationUrl =
                DEFAULT_URL;
        }


        //==================================================
        // Security Check
        //
        // 只允許 all-decrypt.com
        //==================================================

        try
        {
            const targetUrl =
                new URL(
                    notificationUrl
                );


            if (
                targetUrl.origin
                !==
                SITE_ORIGIN
            )
            {
                console.log(
                    "[SW] Invalid URL origin:",
                    targetUrl.origin
                );


                notificationUrl =
                    DEFAULT_URL;
            }
        }
        catch (error)
        {
            console.log(
                "[SW] URL validation error:",
                error
            );


            notificationUrl =
                DEFAULT_URL;
        }


        //==================================================
        // Notification Options
        //==================================================

        const options =
        {
            body:
                data.body ||
                "網站有新的更新",


            icon:
                data.icon ||
                SITE_ORIGIN +
                "/favicon.ico",


            badge:
                data.badge ||
                SITE_ORIGIN +
                "/favicon.ico",


            //==================================================
            // ★ 這裡才是通知自己的資料
            //
            // 點擊通知時：
            //
            // event.notification.data.url
            //
            //==================================================

            data:
            {
                url:
                    notificationUrl,

                lottery_type:
                    lotteryType,

                title:
                    data.title || "",

                body:
                    data.body || ""
            },


            requireInteraction:
                false,


            tag:
                "all-decrypt-notification-"
                +
                Date.now()
        };


        //==================================================
        // Debug
        //==================================================

        console.log(
            "[SW] Notification Title:",
            data.title
        );


        console.log(
            "[SW] Notification Body:",
            data.body
        );


        console.log(
            "[SW] Notification Lottery:",
            lotteryType
        );


        console.log(
            "[SW] Notification URL:",
            notificationUrl
        );


        //==================================================
        // Show Notification
        //==================================================

        event.waitUntil(
            self.registration.showNotification(
                data.title ||
                "All-Decrypt",
                options
            )
        );
    }
);


//==================================================
// Notification Click
//==================================================

self.addEventListener(
    "notificationclick",
    function(event)
    {
        console.log(
            "[SW] Notification clicked"
        );


        //==================================================
        // Close Notification
        //==================================================

        event.notification.close();


        //==================================================
        // Notification Data
        //==================================================

        const notificationData =
            event.notification.data || {};


        //==================================================
        // Get URL
        //==================================================

        let url =
            DEFAULT_URL;


        if (
            typeof notificationData.url
            ===
            "string"
            &&
            notificationData.url.trim()
            !==
            ""
        )
        {
            url =
                notificationData.url.trim();
        }


        //==================================================
        // Get Lottery Type
        //==================================================

        const lotteryType =
            typeof notificationData.lottery_type
            ===
            "string"
                ? notificationData.lottery_type.trim()
                : "";


        //==================================================
        // 如果網址是預設網址
        // 但是有 lottery_type
        //
        // 重新建立：
        //
        // /index.php?lottery=marksix
        //
        //==================================================

        if (
            url === DEFAULT_URL
            &&
            lotteryType !== ""
        )
        {
            url =
                DEFAULT_URL
                +
                "?lottery="
                +
                encodeURIComponent(
                    lotteryType
                );
        }


        //==================================================
        // Convert URL to absolute URL
        //==================================================

        try
        {
            url =
                new URL(
                    url,
                    SITE_ORIGIN
                ).href;
        }
        catch (error)
        {
            console.log(
                "[SW] Click URL parse error:",
                error
            );


            url =
                DEFAULT_URL;
        }


        //==================================================
        // Security Check
        //==================================================

        try
        {
            const targetUrl =
                new URL(
                    url
                );


            if (
                targetUrl.origin
                !==
                SITE_ORIGIN
            )
            {
                console.log(
                    "[SW] Blocked external URL:",
                    targetUrl.href
                );


                url =
                    DEFAULT_URL;
            }
        }
        catch (error)
        {
            console.log(
                "[SW] Target URL validation error:",
                error
            );


            url =
                DEFAULT_URL;
        }


        //==================================================
        // Debug
        //==================================================

        console.log(
            "[SW] Lottery Type:",
            lotteryType
        );


        console.log(
            "[SW] Target URL:",
            url
        );


        //==================================================
        // Find Existing Window
        //==================================================

        event.waitUntil(
            self.clients
                .matchAll(
                {
                    type:
                        "window",

                    includeUncontrolled:
                        true
                })
                .then(
                    function(clientList)
                    {

                        //==================================================
                        // 找現有 All-Decrypt 視窗
                        //==================================================

                        for (
                            const client
                            of clientList
                        )
                        {
                            try
                            {
                                const clientUrl =
                                    new URL(
                                        client.url
                                    );


                                const targetUrl =
                                    new URL(
                                        url
                                    );


                                //==================================================
                                // 同一個網站
                                //==================================================

                                if (
                                    clientUrl.origin
                                    ===
                                    targetUrl.origin
                                    &&
                                    "focus"
                                    in client
                                )
                                {
                                    console.log(
                                        "[SW] Existing window found"
                                    );


                                    console.log(
                                        "[SW] Navigate:",
                                        url
                                    );


                                    return client
                                        .navigate(
                                            url
                                        )
                                        .then(
                                            function()
                                            {
                                                return client.focus();
                                            }
                                        );
                                }
                            }
                            catch (error)
                            {
                                console.log(
                                    "[SW] Client URL error:",
                                    error
                                );
                            }
                        }


                        //==================================================
                        // 沒有視窗
                        //
                        // 開新視窗
                        //==================================================

                        if (
                            self.clients.openWindow
                        )
                        {
                            console.log(
                                "[SW] Open New Window:",
                                url
                            );


                            return self.clients.openWindow(
                                url
                            );
                        }

                    }
                )
        );
    }
);
