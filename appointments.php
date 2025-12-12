<?php
include 'db_connect.php'; 

$message = ''; // ใช้สำหรับแสดงข้อความแจ้งเตือน

//สำหรับการยกเลิกนัดหมาย
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cancel') {
    $id_apm_to_cancel = $_POST['id_apm_to_cancel'];
    
    try {
        //ลบรายการนัดหมายออกจากตาราง Appointments
        // Trigger CheckDentistAvailability และ UpdateDentistStatus จะทำงานเมื่อมีการลบ
        $sql_delete = "DELETE FROM Appointments WHERE id_apm = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if ($stmt_delete->execute([$id_apm_to_cancel])) {
            $message = "<div class='alert alert-success'> ยกเลิกนัดหมาย ID " . htmlspecialchars($id_apm_to_cancel) . " สำเร็จแล้ว! สถานะทันตแพทย์ถูกอัปเดตแล้ว</div>";
        } else {
            $message = "<div class='alert alert-warning'> ไม่สามารถยกเลิกนัดหมาย ID " . htmlspecialchars($id_apm_to_cancel) . "** ได้</div>";
        }
        
    } catch (PDOException $e) {
        $error_message = $e->getMessage();
        
        //ดักข้อความจาก Trigger และ Foreign Key
        if (strpos($error_message, 'existing history records') !== false) {
    
            $message = "<div class='alert alert-danger'>การยกเลิกล้มเหลว: นัดหมาย ID " . htmlspecialchars($id_apm_to_cancel) . "นี้มีการบันทึกประวัติการรักษาแล้ว ไม่สามารถลบทิ้งได้!</div>";
        } elseif (strpos($error_message, 'FOREIGN KEY constraint') !== false) {
             
             $message = "<div class='alert alert-danger'> การยกเลิกล้มเหลว: นัดหมายนี้ถูกอ้างอิงโดยข้อมูลอื่นในระบบ (FK Constraint)</div>";
        } else {
            // ข้อผิดพลาดอื่นๆ ที่อาจเกิดขึ้นจากฐานข้อมูล
            $message = "<div class='alert alert-danger'>การยกเลิกล้มเหลว: " . $error_message . "</div>";
        }
    }
}

//ดึงข้อมูลนัดหมายที่มีสถานะกำลังรอ เท่านั้น
$sql = "
    SELECT 
        A.id_apm, A.date_time, A.reason, A.status,
        P.first_name AS patient_fname, P.last_name AS patient_lname,
        D.first_name AS dentist_fname, D.specialty
    FROM Appointments A 
    JOIN Patients P ON A.id_patient = P.id_patient
    JOIN Dentists D ON A.id_dentist = D.id_dentist
    WHERE A.status = 'กำลังรอ' 
    ORDER BY A.date_time ASC
";
$stmt = $conn->prepare($sql);

try {
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    // หากQueryหลักล้มเหลว
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการนัดหมาย (กำลังรอ)</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        body { font-family: 'Tahoma', sans-serif; background-color: #f8f9fa; }
        .table { border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .container-fluid { padding: 20px; }
        .header-custom { color: #6a0dad; border-bottom: 3px solid #6a0dad; padding-bottom: 10px; }
        .table-custom-header { background-color: #8a2be2; color: white; }
        .table-striped > tbody > tr:nth-of-type(odd) { background-color: #f3f0ff; }
        .btn-group-vertical { width: 100%; } 
    </style>
</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <div class="container-fluid"> 
        
        <h1 class="text-center my-4 header-custom"> รายการนัดหมายที่กำลังรอ (<?= count($appointments) ?> รายการ)</h1>
        
        <?php echo $message; ?> <div class="mb-3">
             <a href="create_appointment.php" class="btn btn-success">+ สร้างนัดหมายใหม่</a>
        </div>

        <table class="table table-striped table-hover table-bordered">
            <thead class="table-custom-header">
                <tr>
                    <th>ID นัดหมาย</th>
                    <th>คนไข้</th>
                    <th>ทันตแพทย์ (ความเชี่ยวชาญ)</th>
                    <th>วันที่/เวลา</th>
                    <th>เหตุผลการนัด</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (count($appointments) > 0) {
                    foreach ($appointments as $a) {
                        $date_time = new DateTime($a['date_time']);
                        $formatted_dt = $date_time->format('Y-m-d H:i');
                        
                        echo "<tr>"; 
                        echo "<td>" . htmlspecialchars($a['id_apm']) . "</td>";
                        echo "<td>" . htmlspecialchars($a['patient_fname'] . " " . $a['patient_lname']) . "</td>";
                        echo "<td>" . htmlspecialchars($a['dentist_fname'] . " (" . $a['specialty'] . ")") . "</td>";
                        echo "<td>" . $formatted_dt . "</td>";
                        echo "<td>" . htmlspecialchars($a['reason']) . "</td>";
                        echo "<td><span class='badge bg-warning text-dark'>" . htmlspecialchars($a['status']) . "</span></td>";
                        
                        echo "<td>"; 
                            //ใช้ปุ่มกลุ่มเพื่อจัดเรียง
                            echo "<div class='btn-group-vertical' role='group'>"; 
                                //ปุ่มยืนยันการรักษา/บันทึกประวัติ
                                echo "<a href='record_history.php?id_apm=" . htmlspecialchars($a['id_apm']) . "' class='btn btn-primary btn-sm mb-1'>ยืนยัน/บันทึกประวัติ</a>";

                                //ปุ่มยกเลิกนัดหมาย (ใช้ฟอร์มPOST และJavaScript Confirm)
                                echo "<form method='POST' style='display: block;' onsubmit='return confirm(\"คุณแน่ใจหรือไม่ที่จะยกเลิกนัดหมาย ID " . htmlspecialchars($a['id_apm']) . " นี้? การดำเนินการนี้จะลบรายการนัดหมาย\");'>";
                                    echo "<input type='hidden' name='action' value='cancel'>";
                                    echo "<input type='hidden' name='id_apm_to_cancel' value='" . htmlspecialchars($a['id_apm']) . "'>";
                                    echo "<button type='submit' class='btn btn-danger btn-sm'> ยกเลิก</button>";
                                echo "</form>";
                               
                            echo "</div>";
                        echo "</td>";
                        
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>ไม่พบรายการนัดหมายที่กำลังรอในขณะนี้</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>