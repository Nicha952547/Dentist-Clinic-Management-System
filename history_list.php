<?php
// ไฟล์: history_list.php (หรือไฟล์ที่คุณตั้งชื่อไว้)

include 'db_connect.php'; 

// เตรียมคำสั่ง SQL เพื่อดึงข้อมูลประวัติการรักษา
// ใช้ CROSS APPLY หรือ Subquery/GROUP BY เพื่อรวมข้อมูลหัตถการจาก HistoryDetails
$sql = "
    SELECT 
        H.id_record, H.date_time, H.id_apm,
        P.first_name AS patient_fname, P.last_name AS patient_lname,
        D.first_name AS dentist_fname,
        
        
        STUFF((
            SELECT ', ' + T.Name + ' (' + CAST(HD.quantity AS VARCHAR) + 'x)'
            FROM HistoryDetails HD
            JOIN Treatments T ON HD.id_tm = T.id_tm
            WHERE HD.id_record = H.id_record
            FOR XML PATH('')
        ), 1, 2, '') AS treatments_summary,

       
        (
            SELECT SUM(T.Price * HD.quantity)
            FROM HistoryDetails HD
            JOIN Treatments T ON HD.id_tm = T.id_tm
            WHERE HD.id_record = H.id_record
        ) AS total_price
        
    FROM History H
    JOIN Patients P ON H.id_patient = P.id_patient
    JOIN Dentists D ON H.id_dentist = D.id_dentist
    ORDER BY H.date_time DESC
";
$stmt = $conn->prepare($sql);

$history_records = [];
$message = '';

try {
    $stmt->execute();
    $history_records = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Query failed: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการรักษาที่เสร็จสิ้น</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', sans-serif; background-color: #f8f9fa; }
        .header-custom { color: #6a0dad; border-bottom: 3px solid #6a0dad; padding-bottom: 10px; }
        .table-custom-header { background-color: #8a2be2; color: white; }
        .btn-purple { background-color: #6a0dad; border-color: #6a0dad; color: white; }
        .btn-purple:hover { background-color: #8a2be2; border-color: #8a2be2; color: white; }
    </style>
</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <div class="container-fluid"> 
        
        <h1 class="text-center my-4 header-custom">ประวัติการรักษาที่เสร็จสิ้น</h1>
        
        <?php echo $message; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'recorded'): ?>
            <div class="alert alert-success">บันทึกประวัติ ID **<?php echo htmlspecialchars($_GET['id']); ?>** สำเร็จ ยอดรวม **<?php echo htmlspecialchars($_GET['cost']); ?>** บาท</div>
        <?php endif; ?>

        <table class="table table-striped table-hover table-bordered">
            <thead class="table-custom-header">
                <tr>
                    <th>ID ประวัติ (Hxxxx)</th>
                    <th>ID นัดหมาย (Axxxx)</th>
                    <th>คนไข้</th>
                    <th>ทันตแพทย์</th>
                    <th>รายการรักษาทั้งหมด</th> 
                    <th>ราคารวม (บาท)</th> 
                    <th>วันที่บันทึก</th>
                    <th>จัดการ</th> </tr>
            </thead>
            <tbody>
                <?php 
                if (count($history_records) > 0) {
                    foreach ($history_records as $h) {
                        $date_time = new DateTime($h['date_time']);
                        $formatted_dt = $date_time->format('Y-m-d H:i:s');
                        
                        echo "<tr>"; 
                        echo "<td>" . htmlspecialchars($h['id_record']) . "</td>";
                        echo "<td>" . (empty($h['id_apm']) ? '-' : htmlspecialchars($h['id_apm'])) . "</td>"; // จัดการกรณีเป็น Walk-in
                        echo "<td>" . htmlspecialchars($h['patient_fname'] . " " . $h['patient_lname']) . "</td>";
                        echo "<td>" . htmlspecialchars($h['dentist_fname']) . "</td>";
                        echo "<td>" . htmlspecialchars($h['treatments_summary']) . "</td>"; 
                        echo "<td class='text-end'>" . number_format($h['total_price'], 2) . "</td>"; 
                        echo "<td>" . $formatted_dt . "</td>";
                        
                        
                        echo "<td>";
                        echo "<a href='receipt.php?id=" . urlencode($h['id_record']) . "' class='btn btn-sm btn-purple' target='_blank'>";
                        echo " ดูใบเสร็จ";
                        echo "</a>";
                        echo "</td>";
                       
                        
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>ไม่พบประวัติการรักษาในขณะนี้</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>