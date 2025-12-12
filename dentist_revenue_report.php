<?php
// ไฟล์: dentist_revenue_report.php

include 'db_connect.php'; 

$message = '';
$report_data = [];

$sql = "
    SELECT 
        D.id_dentist,
        D.first_name,
        D.last_name,
        D.status,
        -- ใช้ Column ที่คำนวณจาก View/Logic ในรูปภาพ image_31ad2c
        COUNT(H.id_record) AS TreatmentsCompleted,
        SUM(HD.quantity * T.Price) AS TotalRevenue
    FROM Dentists D
    INNER JOIN History H ON D.id_dentist = H.id_dentist
    INNER JOIN HistoryDetails HD ON H.id_record = HD.id_record
    INNER JOIN Treatments T ON HD.id_tm = T.id_tm
    GROUP BY D.id_dentist, D.first_name, D.last_name, D.status
    ORDER BY TotalRevenue DESC
";


try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>ข้อผิดพลาดในการดึงข้อมูลรายงาน: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานรายได้ทันตแพทย์</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-color: #6a0dad; --secondary-color: #e6e6fa; }
        body { font-family: 'Tahoma', 'Sans-serif', Arial; background-color: var(--secondary-color); }
        .card-custom { border-radius: 15px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15); background-color: white; margin-top: 20px; }
        .card-header-custom { background-color: var(--primary-color); color: white; border-top-left-radius: 15px; border-top-right-radius: 15px; }
    </style>
</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card card-custom mx-auto">
            
            <div class="card-header card-header-custom">
                <h1 class="h4 my-2 text-center"> รายงานสรุปรายได้ทันตแพทย์</h1>
            </div>

            <div class="card-body">
                
                <?php echo $message; ?>
                
                <?php if (!empty($report_data)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>ID หมอ</th>
                                <th>ชื่อ - นามสกุล</th>
                                <th>สถานะปัจจุบัน</th>
                                <th class="text-end">จำนวน Treatments</th>
                                <th class="text-end">รายได้รวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id_dentist']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td class="text-end"><?php echo number_format($row['TreatmentsCompleted'] ?? 0); ?></td>
                                <td class="text-end fw-bold text-success"><?php echo number_format($row['TotalRevenue'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">ไม่พบข้อมูลรายได้การรักษาในขณะนี้</div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>