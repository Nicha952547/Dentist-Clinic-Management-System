<?php
include 'db_connect.php'; 

$id_record = isset($_GET['id']) ? $_GET['id'] : die("Error: ต้องระบุ ID ประวัติการรักษา");

//ดึงข้อมูลใบเสร็จ
$sql = "
    SELECT
        H.id_record, H.date_time, 
        P.first_name AS patient_fname, P.last_name AS patient_lname,
        D.first_name AS dentist_fname, D.last_name AS dentist_lname,
        T.Name AS TreatmentName,
        T.Price,
        HD.quantity
    FROM History H
    JOIN Patients P ON H.id_patient = P.id_patient
    JOIN Dentists D ON H.id_dentist = D.id_dentist
    JOIN HistoryDetails HD ON H.id_record = HD.id_record 
    JOIN Treatments T ON HD.id_tm = T.id_tm             
    WHERE H.id_record = ?
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_record]);
    $receipt_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($receipt_data)) {
        die("Error: ไม่พบข้อมูลใบเสร็จสำหรับ ID: " . htmlspecialchars($id_record));
    }
    
    //ข้อมูลหลัก (ดึงมาจากรายการแรก)
    $record_info = [
        'id_record' => $receipt_data[0]['id_record'],
        'date_time' => $receipt_data[0]['date_time'],
        'patient_full_name' => $receipt_data[0]['patient_fname'] . ' ' . $receipt_data[0]['patient_lname'],
        'dentist_full_name' => $receipt_data[0]['dentist_fname'] . ' ' . $receipt_data[0]['dentist_lname'],
    ];

    $total_cost = 0;

} catch (PDOException $e) {
    die(" Error fetching receipt data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จการรักษา ID: <?php echo htmlspecialchars($id_record); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', 'Sans-serif', Arial; background-color: #f8f9fa; }
        .receipt-box { width: 600px; margin: 50px auto; padding: 30px; border: 1px solid #ccc; background-color: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .receipt-header { border-bottom: 2px solid #343a40; padding-bottom: 10px; margin-bottom: 20px; }
        .receipt-total { border-top: 2px dashed #343a40; padding-top: 10px; margin-top: 20px; font-size: 1.25rem; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="receipt-box">
        
        <div class="receipt-header text-center">
            <h2 class="mb-0">คลินิกทันตกรรม [ชื่อคลินิก]</h2>
            <p class="text-muted">ใบเสร็จการรักษา (Treatment Receipt)</p>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <p class="mb-1"><strong>เลขที่ประวัติ:</strong> <code><?php echo htmlspecialchars($record_info['id_record']); ?></code></p>
                <p class="mb-1"><strong>คนไข้:</strong> <?php echo htmlspecialchars($record_info['patient_full_name']); ?></p>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>วันที่:</strong> <?php echo (new DateTime($record_info['date_time']))->format('d/m/Y H:i'); ?></p>
                <p class="mb-1"><strong>ทันตแพทย์:</strong> <?php echo htmlspecialchars($record_info['dentist_full_name']); ?></p>
            </div>
        </div>

        <table class="table table-sm">
            <thead>
                <tr>
                    <th>รายการรักษา</th>
                    <th class="text-center">จำนวน</th>
                    <th class="text-end">ราคาต่อหน่วย (฿)</th>
                    <th class="text-end">ราคารวม (฿)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receipt_data as $item): 
                    $subtotal = $item['Price'] * $item['quantity'];
                    $total_cost += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['TreatmentName']); ?></td>
                    <td class="text-center"><?php echo number_format($item['quantity']); ?></td>
                    <td class="text-end"><?php echo number_format($item['Price'], 2); ?></td>
                    <td class="text-end"><?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-total row">
            <div class="col-6"></div>
            <div class="col-6 text-end">
                <p class="mb-0"><strong>รวมทั้งหมด:</strong></p>
                <h3><?php echo number_format($total_cost, 2); ?> ฿</h3>
            </div>
        </div>

        <p class="text-center mt-4">ขอบคุณที่ไว้วางใจคลินิกของเรา</p>
        <div class="text-center mt-3">
            <button class="btn btn-secondary" onclick="window.print()">พิมพ์ใบเสร็จ</button>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>