<?php
session_start();

include('../../connection.php');

if (isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    $sql = "SELECT oi.quantity, p.prod_name, p.prod_brand, p.prod_price_wholesale
            FROM order_items oi
            JOIN products p ON oi.prod_id = p.prod_id
            WHERE oi.order_id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<div class='order-details-header'>
        <h4>Order Items</h4>
      </div>
      <ul>";
              while ($row = $result->fetch_assoc()) {
            echo "<li>
                    <span class='item-brand'>{$row['prod_brand']}</span>
                    <span class='item-name'>{$row['prod_name']}</span>
                    <span class='item-quantity'>{$row['quantity']} x</span>
                    <span class='item-price'>₱ " . number_format($row['prod_price_wholesale'], 2) . "</span>
                  </li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No items found for this order.</p>";
    }
    
    $stmt->close();
}

$mysqli->close();
?>
