<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_brand = $_POST['prod_brand'];
    $prod_name = $_POST['prod_name'];
    $prod_price_wholesale = $_POST['prod_price_wholesale'];
    $prod_price_retail = $_POST['prod_price_retail'];

    $target_dir = "../../images/sacks/";
    $prod_image_path = NULL; // Default to NULL if no image is uploaded

    // Check if image file is uploaded
    if (isset($_FILES["prod_image"]) && $_FILES["prod_image"]["error"] == 0) {
        $target_file = $target_dir . basename($_FILES["prod_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a valid image
        $check = getimagesize($_FILES["prod_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $_SESSION['errorMessage'] = "File is not an image.";
            $uploadOk = 0;
        }

        if ($_FILES["prod_image"]["size"] > 500000) {
            $_SESSION['errorMessage'] = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
            $_SESSION['errorMessage'] = "Sorry, only JPG, JPEG, PNG files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
                $prod_image_path = $target_file;
            } else {
                $_SESSION['errorMessage'] = "Sorry, there was an error uploading your file.";
                $uploadOk = 0;
            }
        }
    }

    // Prepare the SQL query
    if ($prod_image_path !== NULL) {
        $sql = "INSERT INTO products (prod_brand, prod_name, prod_price_wholesale, prod_price_retail, prod_image_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssdds", $prod_brand, $prod_name, $prod_price_wholesale, $prod_price_retail, $prod_image_path);
    } else {
        $sql = "INSERT INTO products (prod_brand, prod_name, prod_price_wholesale, prod_price_retail) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssdd", $prod_brand, $prod_name, $prod_price_wholesale, $prod_price_retail);
    }

    if ($stmt->execute()) {
        $_SESSION["successMessage"] = "Product added successfully.";
        header("Location: ../products/products.php");
        exit();
    } else {
        $_SESSION["errorMessage"] = "Error: " . $stmt->error;
    }

    $stmt->close();
    $mysqli->close();
}
?>
