<?php

include 'db_connect.php'; 

$message = '';
$success_id = null; //ตัวแปรสำหรับเก็บ ID ประวัติที่บันทึกสำเร็จ 

$prefill_id_patient = '';
$prefill_id_dentist = '';
$prefill_id_apm = '';

if (isset($_GET['id_apm']) && !empty($_GET['id_apm'])) {
    $prefill_id_apm = $_GET['id_apm'];
    
    try {
        //ดึงข้อมูล ID คนไข้, ID ทันตแพทย์จากนัดหมายที่สถานะเป็น 'กำลังรอ' เท่านั้น
        $sql_prefill = "
            SELECT id_patient, id_dentist
            FROM Appointments
            WHERE id_apm = ? AND status = 'กำลังรอ'"; 
        
        $stmt_prefill = $conn->prepare($sql_prefill);
        $stmt_prefill->execute([$prefill_id_apm]);
        $appointment_data = $stmt_prefill->fetch(PDO::FETCH_ASSOC);

        if ($appointment_data) {
            $prefill_id_patient = $appointment_data['id_patient'];
            $prefill_id_dentist = $appointment_data['id_dentist'];
            $message = "<div class='alert alert-info'> กำลังบันทึกประวัติการรักษาจากนัดหมาย ID: " . htmlspecialchars($prefill_id_apm) . " (ข้อมูลคนไข้และทันตแพทย์ถูกกรอกอัตโนมัติ)</div>";
        } else {
            //ถ้านัดหมาย ID นี้ไม่พบหรือสถานะไม่ถูกต้อง
            $message = "<div class='alert alert-warning'>ไม่พบข้อมูลนัดหมาย ID: " . htmlspecialchars($prefill_id_apm) . " ที่อยู่ในสถานะ 'กำลังรอ' จะถูกบันทึกเป็น Walk-in แทน</div>";
            $prefill_id_apm = ''; 
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>ข้อผิดพลาดในการโหลดข้อมูลนัดหมาย: " . $e->getMessage() . "</div>";
    }
}



try {
    $patients = $conn->query("SELECT id_patient, first_name, last_name FROM Patients")->fetchAll(PDO::FETCH_ASSOC);
    $dentists = $conn->query("SELECT id_dentist, first_name, last_name, specialty FROM Dentists")->fetchAll(PDO::FETCH_ASSOC);
    $treatments = $conn->query("SELECT id_tm, Name, Price FROM Treatments")->fetchAll(PDO::FETCH_ASSOC);
    //ดึงเฉพาะนัดหมายที่ยังไม่ถูกใช้ในหน้านี้ (ถ้าไม่ใช่การ prefill)
    $sql_appts = "SELECT id_apm, date_time FROM Appointments WHERE status = 'กำลังรอ'";
    $appointments = $conn->query($sql_appts)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (empty($message)) {
        $message = "Error fetching initial data: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    //ดึงค่า idจาก Form/Hidden Fields
    $id_patient = !empty($prefill_id_patient) ? $prefill_id_patient : $_POST['id_patient'];
    $id_dentist = !empty($prefill_id_dentist) ? $prefill_id_dentist : $_POST['id_dentist'];
    //ใช้ id นัดหมายจาก Hidden Field หรือจาก dropdown 
    $id_apm = !empty($prefill_id_apm) ? $prefill_id_apm : 
              (isset($_POST['id_apm']) && $_POST['id_apm'] !== '' ? $_POST['id_apm'] : null);
              
    $treatment_items = $_POST['treatment_items']; // Arrayของรายการรักษาที่เลือกจาก JS


    if (empty($id_patient) || empty($id_dentist) || empty($treatment_items) || count($treatment_items) === 0) {
        $message = "<div class='alert alert-danger'>การบันทึกประวัติล้มเหลว: กรุณาเลือกคนไข้ ทันตแพทย์ และอย่างน้อยหนึ่งรายการรักษา</div>";
    } else {
        $new_id_record = null; //ตัวแปรสำหรับรับ idประวัติใหม่ 

        try {
            //Transaction เพื่อให้แน่ใจว่าทั้ง History และ HistoryDetails ถูกบันทึกหรือยกเลิกพร้อมกัน
            $conn->beginTransaction();

            //เรียกใช้Stored Procedure RecordTreatment เพื่อสร้าง id และบันทึก History
            $stmt_sp = $conn->prepare("{CALL RecordTreatment(?, ?, ?, ?)}");
            
            $stmt_sp->bindParam(1, $id_patient);
            $stmt_sp->bindParam(2, $id_dentist);
            $stmt_sp->bindParam(3, $id_apm);
            //ผูกตัวแปร Output เพื่อรับ id ประวัติใหม่
            $stmt_sp->bindParam(4, $new_id_record, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 50);

            $stmt_sp->execute();
            $stmt_sp->closeCursor(); 

            if (empty($new_id_record)) {
                 throw new Exception("ไม่สามารถสร้าง ID ประวัติการรักษา (Hxxx) ได้");
            }


            //เรียก Stored Procedure RecordTreatmentDetails
            foreach ($treatment_items as $item) {
                //ตรวจสอบความสมบูรณ์ของข้อมูลรายการรักษา
                if (isset($item['id_tm']) && !empty($item['id_tm']) && isset($item['quantity']) && $item['quantity'] > 0) {
                    $id_tm = $item['id_tm'];
                    $quantity = $item['quantity'];
                    
                    //เรียกใช้ SP RecordTreatmentDetails
                    $stmt_details_sp = $conn->prepare("{CALL RecordTreatmentDetails(?, ?, ?)}");
                    
                    $stmt_details_sp->bindParam(1, $new_id_record); //ID ประวัติ
                    $stmt_details_sp->bindParam(2, $id_tm);         //รหัสการรักษา
                    $stmt_details_sp->bindParam(3, $quantity);      //จำนวน

                    $stmt_details_sp->execute();
                    $stmt_details_sp->closeCursor();
                }
            }
            
          
            $conn->commit();
            
            $message = "<div class='alert alert-success'>การบันทึกประวัติการรักษาสำเร็จ ID: " . htmlspecialchars($new_id_record) . "</div>";
            $success_id = $new_id_record; 

        } catch (Exception $e) {
             // Rollback ยกเลิกการบันทึกทั้งหมด หากเกิดข้อผิดพลาด
            $conn->rollBack();
            $message = "<div class='alert alert-danger'>การบันทึกประวัติล้มเหลว: " . $e->getMessage() . "</div>";
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "<div class='alert alert-danger'>การบันทึกประวัติล้มเหลว (DB Error): " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกประวัติการรักษา</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
       
        :root { --primary-color: #6a0dad; --secondary-color: #e6e6fa; }
        body { font-family: 'Tahoma', 'Sans-serif', Arial; background-color: var(--secondary-color); }
        .card-custom { border-radius: 15px; border: none; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15); background-color: white; margin-top: 20px; }
        .card-header-custom { background-color: var(--primary-color); color: white; border-top-left-radius: 15px; border-top-right-radius: 15px; }
        .btn-purple { background-color: var(--primary-color); border-color: var(--primary-color); border-radius: 8px; transition: background-color 0.3s; }
        .btn-purple:hover { background-color: #8a2be2; border-color: #8a2be2; color: white; }
        .form-control, .form-select { border-radius: 8px; }
    </style>
</head>
<body>
    
    <?php include 'navbar.php';  ?>

    <div class="container">
        <div class="card card-custom mx-auto" style="max-width: 800px;">
            
            <div class="card-header card-header-custom">
                <h1 class="h4 my-2 text-center">บันทึกประวัติการรักษา</h1>
            </div>

            <div class="card-body">
                
                <?php echo $message; ?>
                
                <?php 
                //แสดงปุ่มดูใบเสร็จ เมื่อ $success_id มีค่า
                if (isset($success_id) && strpos($message, 'สำเร็จ') !== false): 
                ?>
                    <div class="alert alert-success mt-3 text-center">
                        <p class="h5 mb-3">การบันทึกประวัติการรักษาสำเร็จ</p>
                        <a href="receipt.php?id=<?php echo urlencode($success_id); ?>" 
                           class="btn btn-success btn-lg" target="_blank">
                           ดูใบเสร็จ (<?php echo htmlspecialchars($success_id); ?>)
                        </a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    
                    <?php if (!empty($prefill_id_apm)): ?>
                        <input type="hidden" name="id_apm" value="<?php echo htmlspecialchars($prefill_id_apm); ?>">
                        <input type="hidden" name="id_patient" value="<?php echo htmlspecialchars($prefill_id_patient); ?>">
                        <input type="hidden" name="id_dentist" value="<?php echo htmlspecialchars($prefill_id_dentist); ?>">
                        
                        <p class="mb-4 text-primary">อ้างอิงจากนัดหมาย: 
                            <strong><?php echo htmlspecialchars($prefill_id_apm); ?></strong> 
                            (คนไข้/ทันตแพทย์ถูกกำหนดอัตโนมัติ)
                        </p>
                    <?php endif; ?>


                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="id_patient" class="form-label">คนไข้:</label>
                            <select id="id_patient" name="id_patient" class="form-select" required 
                                <?php echo !empty($prefill_id_patient) ? 'disabled' : ''; ?>>
                                <option value="">เลือกคนไข้</option>
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo $p['id_patient']; ?>" 
                                        <?php echo ($prefill_id_patient == $p['id_patient']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['id_patient'] . " - " . $p['first_name'] . " " . $p['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                             <?php if (!empty($prefill_id_patient)): ?>
                                <input type="hidden" name="id_patient" value="<?php echo htmlspecialchars($prefill_id_patient); ?>">
                            <?php endif; ?>
                        </div>

                        <div class="mb-3 col-md-6">
                            <label for="id_dentist" class="form-label">ทันตแพทย์:</label>
                            <select id="id_dentist" name="id_dentist" class="form-select" required
                                <?php echo !empty($prefill_id_dentist) ? 'disabled' : ''; ?>>
                                <option value="">เลือกทันตแพทย์</option>
                                <?php foreach ($dentists as $d): ?>
                                    <option value="<?php echo $d['id_dentist']; ?>"
                                        <?php echo ($prefill_id_dentist == $d['id_dentist']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['id_dentist'] . " - " . $d['first_name'] . " (" . $d['specialty'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                             <?php if (!empty($prefill_id_dentist)): ?>
                                <input type="hidden" name="id_dentist" value="<?php echo htmlspecialchars($prefill_id_dentist); ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (empty($prefill_id_apm)): ?>
                    <div class="mb-4">
                        <label for="id_apm" class="form-label">อ้างอิงนัดหมาย (ถ้ามี - เพื่ออัปเดตสถานะ):</label>
                        <select id="id_apm" name="id_apm" class="form-select">
                            <option value="">walk-in</option>
                            <?php foreach ($appointments as $a): ?>
                                <option value="<?php echo $a['id_apm']; ?>">
                                    <?php 
                                        $dateTime = new DateTime($a['date_time']);
                                        echo htmlspecialchars($a['id_apm'] . " - " . $dateTime->format('Y-m-d H:i')); 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>


                    <h5 class="mt-4 mb-3">รายการรักษาที่ดำเนินการ</h5>
                    
                    <div id="treatment-list">
                        </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="add-treatment">
                        + เพิ่มรายการรักษา
                    </button>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-purple btn-lg">บันทึกประวัติการรักษา</button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            let treatmentCounter = 0;
            //ใช้ JSON.parse() เพื่อให้แน่ใจว่าได้ Arrayที่ถูกต้อง
            const treatments = <?php echo json_encode($treatments); ?>; 

            function addTreatmentItem() {
                treatmentCounter++;
                const itemHtml = `
                    <div class="row mb-3 treatment-item" data-id="${treatmentCounter}">
                        <div class="col-md-7">
                            <select name="treatment_items[${treatmentCounter}][id_tm]" class="form-select treatment-select" required>
                                <option value="">-- เลือกรายการรักษา --</option>
                                ${treatments.map(t => {
                                    //เนื่องจาก Price อาจเป็น string ใน PDO เราใช้ parseFloat และ toLocaleString
                                    const price = parseFloat(t.Price).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    return `
                                        <option value="${t.id_tm}">
                                            ${t.id_tm} - ${t.Name} (฿${price})
                                        </option>
                                    `;
                                }).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="treatment_items[${treatmentCounter}][quantity]" class="form-control" placeholder="จำนวน" value="1" min="1" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-center">
                            <button type="button" class="btn btn-danger btn-sm remove-treatment" data-id="${treatmentCounter}">ลบ</button>
                        </div>
                    </div>
                `;
                $('#treatment-list').append(itemHtml);
            }

            //เพิ่มรายการเริ่มต้นหนึ่งรายการ
            addTreatmentItem(); 

            $('#add-treatment').on('click', addTreatmentItem);

            $(document).on('click', '.remove-treatment', function() {
                const idToRemove = $(this).data('id');
                //ลบรายการ
                $(`.treatment-item[data-id="${idToRemove}"]`).remove();
                
                //ต้องเหลืออย่างน้อย 1 รายการเพื่อป้องกัน Form ว่าง
                if ($('.treatment-item').length === 0) {
                    addTreatmentItem(); 
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>