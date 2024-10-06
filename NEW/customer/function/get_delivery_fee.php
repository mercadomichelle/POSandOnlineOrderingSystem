<!-- <?php
// if (isset($_GET['address'])) {
//     $address = $_GET['address'];

//     $host = "localhost";
//     $user = "root";
//     $password = "";
//     $db = "system_db";

//     $mysqli = new mysqli($host, $user, $password, $db);

//     if ($mysqli->connect_error) {
//         die("Connection failed: " . $mysqli->connect_error);
//     }

//     // Query the delivery fee based on the address
//     $sql = "SELECT fee FROM delivery_fees WHERE city = ?";
//     $stmt = $mysqli->prepare($sql);
//     $stmt->bind_param("s", $address);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows === 1) {
//         $feeData = $result->fetch_assoc();
//         $deliveryFee = $feeData['fee'];
//     } else {
//         $deliveryFee = 150.00; // Default fee if address not found
//     }

//     echo json_encode(['fee' => $deliveryFee]);

//     $stmt->close();
//     $mysqli->close();
// }
?> -->
