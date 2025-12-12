<?php
include 'db_connect.php';

$message = '';
$patients = [];

try {
    $sql = "
        SELECT 
            id_patient,
            first_name,
            last_name,
            TotalVisits,
            LastVisitDate,
            TotalSpending
        FROM PatientSummary 
        ORDER BY first_name ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>ข้อผิดพลาดในการดึงข้อมูลคนไข้: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อผู้ป่วย</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6a0dad;
            --secondary-color: #f3e8ff;
        }
        body {
            font-family: 'Tahoma', 'Sans-serif', Arial;
            background-color: var(--secondary-color);
        }
        .card-custom {
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            background-color: white;
            margin-top: 20px;
        }
        .card-header-custom {
            background-color: var(--primary-color);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card card-custom mx-auto">
            <div class="card-header card-header-custom">
                <h1 class="h4 my-2 text-center">รายชื่อผู้ป่วย</h1>
            </div>

            <div class="card-body">
                <?php echo $message; ?>

                <?php if (!empty($patients)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead>
                            <tr class="text-center">
                                <th>ID ผู้ป่วย</th>
                                <th>ชื่อ - นามสกุล</th>
                                <th>จำนวนครั้งเข้ารักษา</th>
                                <th>วันที่รักษาล่าสุด</th>
                                <th class="text-end">ยอดใช้จ่ายรวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $p): ?>
                            <tr>
                                <td class="text-center"><?php echo htmlspecialchars($p['id_patient']); ?></td>
                                <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                                <td class="text-center"><?php echo number_format($p['TotalVisits'] ?? 0); ?></td>
                                <td class="text-center">
                                    <?php 
                                        echo !empty($p['LastVisitDate']) 
                                            ? date('d/m/Y', strtotime($p['LastVisitDate'])) 
                                            : '-';
                                    ?>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    <?php echo number_format($p['TotalSpending'] ?? 0, 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        ไม่พบข้อมูลผู้ป่วยในระบบขณะนี้
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
