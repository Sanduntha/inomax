<?php
include '../scripts/config.php';

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}

$response = ['status' => 'error', 'message' => 'Something went wrong'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentId    = $_POST['cheque_id'];
    $amount       = $_POST['amount'];
    $paymentType  = (int)$_POST['payment_type'];
    $chequeNumber = $_POST['cheque_number'] ?? null;
    $bankId       = $_POST['bank'] ?? null;
    $customerId   = $_POST['customer_id'] ?? null; 

    if (!$paymentId || !$amount || !$paymentType) {
        $response['message'] = 'Missing required fields';
        echo json_encode($response);
        exit;
    }

    $check = $conn->prepare("SELECT is_payment_added FROM tbl_cheque_payments WHERE idtbl_cheque_payments = ?");
    $check->bind_param("i", $paymentId);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();

    if ($result && $result['is_payment_added'] == 1) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'A retry payment has already been added for this cheque.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO tbl_cheque_payment_reentries 
        (tbl_cheque_payments_idtbl_cheque_payments, tbl_customer_idtbl_customer, amount, payment_type, cheque_number, bank_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iidisi", $paymentId, $customerId, $amount, $paymentType, $chequeNumber, $bankId);

    if ($stmt->execute()) {
        $update = $conn->prepare("UPDATE tbl_cheque_payments SET is_payment_added = 1 WHERE idtbl_cheque_payments = ?");
        $update->bind_param("i", $paymentId);
        $update->execute();
        $update->close();

        $response['status']  = 'success';
        $response['message'] = 'Payment reentry added successfully';
    } else {
        $response['message'] = 'Database error: ' . $stmt->error;
    }

    $stmt->close();
}

echo json_encode($response);
