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

$product = null;

if (isset($_GET['prod_id'])) {
    $product_id = intval($_GET['prod_id']);
    $sql = "SELECT * FROM products WHERE prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = intval($_POST['prod_id']);
    $prod_brand = $_POST['prod_brand'];
    $prod_name = $_POST['prod_name'];
    $prod_price_wholesale = isset($_POST['prod_price_wholesale']) ? $_POST['prod_price_wholesale'] : null;
    $prod_price_retail = isset($_POST['prod_price_retail']) ? $_POST['prod_price_retail'] : null;
    $source_page = $_POST['source_page'];

    $target_dir = "../../images/sacks/";
    $uploadOk = 1;
    $prod_image_path = null;

    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
        $target_file = $target_dir . basename($_FILES["prod_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["prod_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        if ($_FILES["prod_image"]["size"] > 500000) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk) {
            // Remove the existing image if there is one
            if (!empty($product['prod_image_path']) && file_exists($product['prod_image_path'])) {
                unlink($product['prod_image_path']);
            }

            if (move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
                $prod_image_path = $target_file;
            } else {
                echo "Sorry, there was an error uploading your file.";
                $uploadOk = 0;
            }
        }
    }

    // Prepare the SQL query
    $sql = "UPDATE products SET prod_brand = ?, prod_name = ?";
    $params = [$prod_brand, $prod_name];
    $types = "ss";

    if ($prod_price_wholesale !== null) {
        $sql .= ", prod_price_wholesale = ?";
        $params[] = $prod_price_wholesale;
        $types .= "d";
    }

    if ($prod_image_path !== null) {
        $sql .= ", prod_image_path = ?";
        $params[] = $prod_image_path;
        $types .= "s";
    }

    if ($prod_price_retail !== null) {
        $sql .= ", prod_price_retail = ?";
        $params[] = $prod_price_retail;
        $types .= "d";
    }

    $sql .= " WHERE prod_id = ?";
    $params[] = $prod_id;
    $types .= "i";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        if ($source_page === 'retail') {
            header("Location: products_retail.php");
        } elseif ($source_page === 'wholesale') {
            header("Location: products.php");
        } else {
            header("Location: products.php");
        }
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $mysqli->close();
}
?>
