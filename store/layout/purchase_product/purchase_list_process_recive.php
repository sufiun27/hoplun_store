<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// print_r($_POST);
// exit();

include '../database.php';

try {
    // --- Input Values ---
    $p_id = $_POST['p_id'] ?? 0;
    $P_receive_qty = $_POST['receive_qty'] ?? 0;
    $p_cash = $_POST['cash'] ?? 1;
    $user = $_SESSION['username'] ?? '';

    // --- Date Handling ---
    date_default_timezone_set('Asia/Dhaka');
    $defaultDateTime = date('Y-m-d H:i:s'); // now
    $P_expaired_datetime = !empty($_POST['expaired_datetime'])
        ? $_POST['expaired_datetime']
        : date('Y-m-d H:i:s', strtotime('+365 days')); // default 1 year from today

    // --- Transaction Start ---
    $conn->beginTransaction();

    // --- Insert into tem_purchase_recive ---
    $sql = "INSERT INTO tem_purchase_recive
            (p_id, p_recive_by, p_recive_datetime, p_expaired_datetime, p_recive_qty, p_stock, cash1_creadit0)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $p_id,
        $user,
        $defaultDateTime,
        $P_expaired_datetime,
        $P_receive_qty,
        $P_receive_qty,
        $p_cash
    ]);

    // --- Update item_purchase ---
    $sql1 = "UPDATE item_purchase SET p_recive = 1 WHERE p_id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$p_id]);

    // --- Commit Transaction ---
    $conn->commit();

    // --- Redirect URL Construction ---
    $extra_url = 'store/layout/purchase_product/purchase_list.php';
    $base_url = $_SESSION['base_url'] ?? $_SERVER['HTTP_HOST'];

    $params = [];
    if (!empty($_POST['page'])) $params[] = 'page=' . (int)$_POST['page'];
    if (!empty($_POST['section'])) $params[] = 'section=' . urlencode($_POST['section']);
    if (!empty($_POST['startDate'])) $params[] = 'startDate=' . urlencode($_POST['startDate']);
    if (!empty($_POST['endDate'])) $params[] = 'endDate=' . urlencode($_POST['endDate']);

    $redirect_url = "http://$base_url/$extra_url";
    if ($params) {
        $redirect_url .= '?' . implode('&', $params);
    }

    //echo $redirect_url;

    header("Location: $redirect_url");
    exit();

} catch (PDOException $e) {
    // --- Rollback if transaction active ---
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo "Database Error: " . $e->getMessage();
}

unset($conn);
exit;
