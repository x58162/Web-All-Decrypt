<?php

require_once __DIR__ . "/config.php";


//==================================================
// 取得 ID
//==================================================

$id =
    intval(
        $_GET["id"] ?? 0
    );


if ($id <= 0)
{
    die("無效的 ID。");
}


//==================================================
// 取得資料
//==================================================

$stmt =
    $pdo->prepare(
        "
        SELECT
            id,
            label,
            image,
            sort_order
        FROM contents
        WHERE id = :id
        "
    );


$stmt->execute(
    [
        ":id" => $id
    ]
);


$data =
    $stmt->fetch();


if (!$data)
{
    die("找不到資料。");
}


$message = "";

$error = "";


//==================================================
// 儲存修改
//==================================================

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
)
{

    $label =
        trim(
            $_POST["label"] ?? ""
        );


    $sortOrder =
        intval(
            $_POST["sort_order"] ?? 0
        );


    if ($label === "")
    {

        $error =
            "請輸入標題。";

    }
    else
    {

        $newImage =
            $data["image"];


        //==================================================
        // 更換圖片
        //==================================================

        if (
            isset(
                $_FILES["image"]
            )
            &&
            $_FILES["image"]["error"]
            === UPLOAD_ERR_OK
        )
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

                $originalName =
                    $file["name"];


                $extension =
                    strtolower(
                        pathinfo(
                            $originalName,
                            PATHINFO_EXTENSION
                        )
                    );


                //==================================================
                // 允許的圖片格式
                //==================================================

                $allowedExtensions = [

                    "jpg" => "jpg",

                    "jpeg" => "jpg",

                    "png" => "png",

                    "gif" => "gif",

                    "webp" => "webp"

                ];


                if (
                    !isset(
                        $allowedExtensions[
                            $extension
                        ]
                    )
                )
                {

                    $error =
                        "只允許 JPG、JPEG、PNG、GIF、WEBP。";

                }
                else
                {

                    $extension =
                        $allowedExtensions[
                            $extension
                        ];


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
                    // 新檔名
                    //==================================================

                    $filename =
                        bin2hex(
                            random_bytes(16)
                        )
                        . "."
                        . $extension;


                    $target =
                        $uploadDirectory
                        . DIRECTORY_SEPARATOR
                        . $filename;


                    //==================================================
                    // 移動圖片
                    //==================================================

                    if (
                        move_uploaded_file(
                            $file["tmp_name"],
                            $target
                        )
                    )
                    {

                        $newImage =
                            "uploads/"
                            . $filename;


                        //==================================================
                        // 刪除舊圖片
                        //==================================================

                        $oldFile =
                            __DIR__
                            . DIRECTORY_SEPARATOR
                            . str_replace(
                                "/",
                                DIRECTORY_SEPARATOR,
                                $data["image"]
                            );


                        if (
                            is_file(
                                $oldFile
                            )
                        )
                        {
                            unlink(
                                $oldFile
                            );
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


        //==================================================
        // 更新資料
        //==================================================

        if ($error === "")
        {

            $stmt =
                $pdo->prepare(
                    "
                    UPDATE contents
                    SET
                        label = :label,
                        image = :image,
                        sort_order = :sort_order
                    WHERE id = :id
                    "
                );


            $stmt->execute(
                [

                    ":label" =>
                        $label,

                    ":image" =>
                        $newImage,

                    ":sort_order" =>
                        $sortOrder,

                    ":id" =>
                        $id

                ]
            );


            $message =
                "修改成功。";


            $data["label"] =
                $label;


            $data["image"] =
                $newImage;


            $data["sort_order"] =
                $sortOrder;

        }

    }

}

?>

<!DOCTYPE html>

<html lang="zh-Hant">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>修改內容</title>

<link
    rel="stylesheet"
    href="style.css"
>

</head>


<body>


<div class="page">


<header class="site-header">

<h1>
    修改內容
</h1>


<a
    class="admin-button"
    href="admin.php"
>
    返回管理
</a>


</header>


<?php

if ($message !== "")
{

?>

<div class="message">

<?= htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php

}


if ($error !== "")
{

?>

<div class="error-message">

<?= htmlspecialchars(
    $error,
    ENT_QUOTES,
    "UTF-8"
) ?>

</div>

<?php

}

?>


<section class="admin-card">


<form
    method="post"
    enctype="multipart/form-data"
>


<label>
    標題 Label
</label>


<input
    type="text"
    name="label"
    maxlength="255"
    value="<?= htmlspecialchars(
        $data["label"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    required
>


<label>
    顯示順序
</label>


<input
    type="number"
    name="sort_order"
    value="<?= intval(
        $data["sort_order"]
    ) ?>"
>


<label>
    目前圖片
</label>


<div class="edit-image">


<img
    src="<?= htmlspecialchars(
        $data["image"],
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    alt=""
>


</div>


<label>
    更換圖片
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
>


<button
    class="primary-button"
    type="submit"
>

儲存修改

</button>


</form>


</section>


</div>


</body>

</html>
