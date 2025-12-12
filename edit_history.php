<?php
include 'db_connect.php'; 

$message = '';
$history_data = null; //ข้อมูลหลักของประวัติ
$id_record = isset($_GET['id']) ? $_GET['id'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : ''; //รับพารามิเตอร์ action

//ดึงรายการรักษาทั้งหมด 
try {
    $treatments_list = $conn->query("SELECT id_tm, Name, Price FROM Treatments")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching treatments list: " . $e->getMessage());
}

//จัดการการลบ (DELETE)
// ส่วนนี้ถูกปรับปรุงให้ลบ HistoryDetails ด้วย
if ($action === 'delete' && !empty($id_record)) {
    $id_apm = null;

    try {
        $conn->beginTransaction();

        //ดึง id_apm ที่เกี่ยวข้องก่อนลบ
        $stmt_select = $conn->prepare("SELECT id_apm FROM History WHERE id_record = ?");
        $stmt_select->execute([$id_record]);
        $result = $stmt_select->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['id_apm'])) {
            $id_apm = $result['id_apm'];
        }

        //ลบข้อมูลจากตาราง HistoryDetails ก่อน (เนื่องจากเป็น FK)
        $stmt_delete_details = $conn->prepare("DELETE FROM HistoryDetails WHERE id_record = ?");
        $stmt_delete_details->execute([$id_record]);

        //ลบข้อมูลจากตาราง History
        $stmt_delete_history = $conn->prepare("DELETE FROM History WHERE id_record = ?");
        $stmt_delete_history->execute([$id_record]);

        //หากมีการเชื่อมโยงกับนัดหมาย ให้เปลี่ยนสถานะกลับเป็น 'กำลังรอ'
        if ($id_apm) {
            $stmt_update_apm = $conn->prepare("UPDATE Appointments SET status = 'กำลังรอ' WHERE id_apm = ?");
            $stmt_update_apm->execute([$id_apm]);
        }
        
        $conn->commit();
        
        echo "<script>window.location.href='history_list.php?status=deleted&id=" . urlencode($id_record) . "';</script>";
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        die(" การลบข้อมูลล้มเหลว: " . $e->getMessage());
    }
}


if (!empty($id_record)) {
    try {
        // Query หลัก
        $sql_main = "
            SELECT 
                H.id_record, H.date_time, H.id_apm,
                P.id_patient, P.first_name AS patient_fname, P.last_name AS patient_lname,
                D.id_dentist, D.first_name AS dentist_fname, D.specialty
            FROM History H
            JOIN Patients P ON H.id_patient = P.id_patient
            JOIN Dentists D ON H.id_dentist = D.id_dentist
            WHERE H.id_record = ?
        ";
        $stmt_main = $conn->prepare($sql_main);
        $stmt_main->execute([$id_record]);
        $history_data = $stmt_main->fetch(PDO::FETCH_ASSOC);

        if (!$history_data) {
            die("Error: ไม่พบข้อมูลประวัติการรักษา ID: " . htmlspecialchars($id_record));
        }

        // Query ดึงรายละเอียดรายการรักษา (HistoryDetails)
        $sql_details = "
            SELECT 
                HD.quantity,
                T.Name, 
                T.Price
            FROM HistoryDetails HD
            JOIN Treatments T ON HD.id_tm = T.id_tm
            WHERE HD.id_record = ?
        ";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->execute([$id_record]);
        $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
        $history_data['details'] = $details;

    } catch (PDOException $e) {
        die("Error fetching current data: " . $e->getMessage());
    }
} else {
     die("Error: ต้องระบุ ID ประวัติการรักษา (Hxxxx)");
}

// คำนวณราคารวม
$total_cost = 0;
foreach ($history_data['details'] as $detail) {
    $total_cost += $detail['Price'] * $detail['quantity'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดประวัติการรักษา</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 

    <!-- SweetAlert2 (ใช้แทน alert/confirm) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root { --primary-color: #6a0dad; --secondary-color: #e6e6fa; }
        body { font-family: 'Tahoma', 'Sans-serif', Arial; background-color: var(--secondary-color); }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15); background-color: white; margin-top: 20px; }
        .card-header-custom { background-color: var(--primary-color); color: white; border-top-left-radius: 15px; border-top-right-radius: 15px; }
        .btn-purple { background-color: var(--primary-color); border-color: var(--primary-color); border-radius: 8px; transition: background-color 0.3s; }
        .btn-purple:hover { background-color: #8a2be2; border-color: #8a2be2; color: white; }
        .summary-box { background-color: #fffacd; padding: 15px; border-radius: 10px; border: 1px solid #ffd700; }
    </style>
</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card card-custom mx-auto" style="max-width: 700px;">
            
            <div class="card-header card-header-custom">
                <h1 class="h4 my-2 text-center">รายละเอียดประวัติการรักษา (ID: <?php echo htmlspecialchars($id_record); ?>)</h1>
            </div>

            <div class="card-body">
                
                <?php if ($message): ?>
                    <div class="alert alert-danger message">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>รหัสประวัติ:</strong> <code><?php echo htmlspecialchars($history_data['id_record']); ?></code></p>
                        <p class="mb-1"><strong>วัน/เวลา:</strong> <?php echo (new DateTime($history_data['date_time']))->format('Y-m-d H:i:s'); ?></p>
                        <p class="mb-1"><strong>อ้างอิงนัดหมาย:</strong> 
                            <?php if ($history_data['id_apm']): ?>
                                <code><?php echo htmlspecialchars($history_data['id_apm']); ?></code> (สถานะ 'สำเร็จ')
                            <?php else: ?>
                                ไม่มี
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>คนไข้:</strong> <?php echo htmlspecialchars($history_data['patient_fname'] . ' ' . $history_data['patient_lname']); ?> (ID: <code><?php echo htmlspecialchars($history_data['id_patient']); ?></code>)</p>
                        <p class="mb-1"><strong>ทันตแพทย์:</strong> <?php echo htmlspecialchars($history_data['dentist_fname']); ?> (ID: <code><?php echo htmlspecialchars($history_data['id_dentist']); ?></code>)</p>
                        <p class="mb-1"><strong>ความเชี่ยวชาญ:</strong> <?php echo htmlspecialchars($history_data['specialty']); ?></p>
                    </div>
                </div>

                <h5 class="mt-3 mb-3 text-primary">รายการหัตถการที่ดำเนินการ</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end">ราคา/หน่วย (฿)</th>
                                <th class="text-end">รวม (฿)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($history_data['details'])): ?>
                                <?php foreach ($history_data['details'] as $detail): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detail['Name']); ?></td>
                                    <td class="text-center"><?php echo number_format($detail['quantity']); ?></td>
                                    <td class="text-end"><?php echo number_format($detail['Price'], 2); ?></td>
                                    <td class="text-end"><strong><?php echo number_format($detail['Price'] * $detail['quantity'], 2); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-danger">ไม่พบรายการรักษา</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>รวมทั้งหมด:</strong></td>
                                <td class="text-end summary-box"><strong><?php echo number_format($total_cost, 2); ?> ฿</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h5 class="mt-4 text-danger">การดำเนินการที่สำคัญ</h5>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-danger btn-lg" id="delete-btn">
                         ลบประวัติการรักษา
                    </button>
                    <a href="history_list.php" class="btn btn-outline-secondary btn-lg">
                        กลับไปหน้าประวัติ
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ใช้ SweetAlert2 แทน confirm()
        document.getElementById('delete-btn').addEventListener('click', function() {
            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "คุณกำลังจะลบประวัติ ID: <?php echo htmlspecialchars($id_record); ?> การดำเนินการนี้จะย้อนสถานะการนัดหมายที่เกี่ยวข้องกลับเป็น 'กำลังรอ'",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6a0dad',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // ทำการลบโดย Redirect ไปที่ URL ที่มี action=delete
                    window.location.href = 'history_detail.php?id=<?php echo urlencode($id_record); ?>&action=delete';
                }
            })
        });

        // (ไม่ใช้ฟังก์ชัน prepareTreatments() และฟอร์ม POST เพราะยกเลิกการแก้ไข)
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>