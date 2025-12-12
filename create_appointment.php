<?php
include 'db_connect.php'; 

//ดึงข้อมูลคนไข้และทันตแพทย์
$patients = $conn->query("SELECT id_patient, first_name, last_name FROM Patients ORDER BY id_patient")->fetchAll(PDO::FETCH_ASSOC);
$dentists = $conn->query("SELECT id_dentist, first_name, specialty FROM Dentists ORDER BY id_dentist")->fetchAll(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_patient = $_POST['id_patient'];
    $id_dentist = $_POST['id_dentist'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $datetime = $date . ' ' . $time;
    $reason = trim($_POST['reason']);
    
    //จัดการคนไข้ใหม่ 
    if ($id_patient === 'NEW') {
        $new_fname = trim($_POST['new_first_name']);
        $new_lname = trim($_POST['new_last_name']);
        $new_dob = $_POST['new_dob']; 
        $new_phone = trim($_POST['new_phone_number']);

        //ตรวจสอบความสมบูรณ์ของข้อมูลคนไข้ใหม่
        if (empty($new_fname) || empty($new_lname) || empty($new_dob) || empty($new_phone)) {
            $message = "<div class='alert alert-danger'>กรุณากรอกข้อมูลคนไข้ใหม่ให้ครบถ้วน (ชื่อ, นามสกุล, วันเกิด, เบอร์โทร)</div>";
            $id_patient = null; 
        } else {
            try {
                //เรียก Stored Procedure เพื่อสร้างคนไข้ใหม่
                $stmt_new_patient = $conn->prepare("{CALL CreateNewPatient(?, ?, ?, ?, ?)}");
                
                //ใช้ตัวแปร dummy เพื่อให้พารามิเตอร์ที่ 5 ครบถ้วนตาม SP
                $new_patient_id_dummy = ''; 

                $stmt_new_patient->bindParam(1, $new_fname);
                $stmt_new_patient->bindParam(2, $new_lname);
                $stmt_new_patient->bindParam(3, $new_dob); 
                $stmt_new_patient->bindParam(4, $new_phone); 
                $stmt_new_patient->bindParam(5, $new_patient_id_dummy, PDO::PARAM_STR, 50); 
                
                $stmt_new_patient->execute();
                
                
                $result = $stmt_new_patient->fetch(PDO::FETCH_ASSOC);
                
                $stmt_new_patient->closeCursor();

                if ($result && isset($result['NewPatientID'])) {
                    $id_patient = $result['NewPatientID'];
                    $message .= "<div class='alert alert-success'>บันทึกรายละเอียดคนไข้ใหม่ " . htmlspecialchars($id_patient) . "สำเร็จ!</div>";
                } else {
                    $message .= "<div class='alert alert-danger'>ไม่สามารถดึง ID คนไข้ใหม่จากฐานข้อมูลได้ (โปรดตรวจสอบ SP CreateNewPatient)</div>";
                    $id_patient = null; 
                }
                

            } catch (PDOException $e) {
                // ดักข้อผิดพลาดระหว่างการสร้างคนไข้ใหม่
                $message .= "<div class='alert alert-danger'>การบันทึกคนไข้ใหม่ล้มเหลว: " . $e->getMessage() . "</div>";
                $id_patient = null; 
            }
        }
    }

    //สร้างนัดหมาย (ถ้าได้ idคนไข้แล้ว)
    if (!empty($id_patient)) {
        try {
            $new_id_apm = null;
            
            //เรียก Stored Procedure เพื่อสร้าง id นัดหมาย
            $stmt_id = $conn->prepare("{CALL CreateAppointmentID(?)}");
            $stmt_id->bindParam(1, $new_id_apm, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 50);
            $stmt_id->execute();
            
            $stmt_id->closeCursor(); 

            //Insert ข้อมูลนัดหมาย Trigger CheckDentist และ UpdateDentistStatus ทำงานที่นี่
            $sql = "INSERT INTO Appointments (id_apm, id_patient, id_dentist, date_time, reason, status) 
                    VALUES (?, ?, ?, ?, ?, 'กำลังรอ')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_id_apm, $id_patient, $id_dentist, $datetime, $reason]); 
            
            $message .= "<div class='alert alert-success'> สร้างนัดหมาย ID " . htmlspecialchars($new_id_apm) . " สำหรับคนไข้ ID " . htmlspecialchars($id_patient) . "สำเร็จแล้ว!</div>";
        } catch (PDOException $e) {
            $error_message = $e->getMessage();

            //ดักข้อผิดพลาดจาก Trigger CheckDentist
            if (strpos($error_message, 'The selected Dentist is already scheduled at this time') !== false) {
                 $message .= "<div class='alert alert-danger'>การสร้างนัดหมายล้มเหลว: ทันตแพทย์ถูกนัดหมายไว้แล้วในช่วงเวลานั้น </div>";
            } else {
                 $message .= "<div class='alert alert-danger'>การสร้างนัดหมายล้มเหลว: " . $error_message . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สร้างนัดหมายใหม่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <style> 
        body { font-family: 'Tahoma', sans-serif; background-color: #f8f9fa; } 
        .header-custom { color: #6a0dad; border-bottom: 3px solid #6a0dad; padding-bottom: 10px; } 
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h1 class="text-center mb-4 header-custom">สร้างนัดหมายใหม่</h1>
        <?php echo $message; ?>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    
                    <div class="mb-3">
                        <label for="id_patient" class="form-label">คนไข้:</label>
                        <select class="form-select" id="id_patient" name="id_patient" required>
                            <option value="">เลือกคนไข้</option>
                            <option value="NEW" style="font-weight: bold; background-color: #e6e6fa;"> + คนไข้ใหม่ (กรอกข้อมูลด้านล่าง)</option>
                            <option disabled> คนไข้ปัจจุบัน </option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?= htmlspecialchars($p['id_patient']) ?>"><?= htmlspecialchars($p['id_patient'] . ' - ' . $p['first_name'] . ' ' . $p['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="new_patient_form" class="card p-3 mb-3 border-primary" style="display: none;">
                        <h6 class="card-title text-primary">กรอกข้อมูลคนไข้ใหม่ (จำเป็น)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_first_name" class="form-label">ชื่อ:</label>
                                <input type="text" class="form-control" id="new_first_name" name="new_first_name" placeholder="ชื่อ">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_last_name" class="form-label">นามสกุล:</label>
                                <input type="text" class="form-control" id="new_last_name" name="new_last_name" placeholder="นามสกุล">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_dob" class="form-label">วัน/เดือน/ปีเกิด (DOB):</label>
                                <input type="date" class="form-control" id="new_dob" name="new_dob">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_phone_number" class="form-label">เบอร์โทรศัพท์:</label>
                                <input type="text" class="form-control" id="new_phone_number" name="new_phone_number" placeholder="เช่น 08x-xxxxxxx">
                            </div>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label for="id_dentist" class="form-label">ทันตแพทย์:</label>
                        <select class="form-select" id="id_dentist" name="id_dentist" required>
                            <option value="">เลือกทันตแพทย์</option>
                            <?php foreach ($dentists as $d): ?>
                                <option value="<?= htmlspecialchars($d['id_dentist']) ?>"><?= htmlspecialchars($d['id_dentist'] . ' - ' . $d['first_name'] . ' (' . $d['specialty'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">วันที่:</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time" class="form-label">เวลา:</label>
                            <input type="time" class="form-control" id="time" name="time" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">เหตุผลการนัดหมาย:</label>
                        <input type="text" class="form-control" id="reason" name="reason" placeholder="เช่น ตรวจสุขภาพฟันประจำปี" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">บันทึกนัดหมาย</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            //แสดงฟอร์มคนไข้ใหม่
            $('#id_patient').change(function() {
                if ($(this).val() === 'NEW') {
                    $('#new_patient_form').slideDown();
                    //ทำให้ช่องกรอกทั้งหมดเป็น Required 
                    $('#new_first_name').prop('required', true);
                    $('#new_last_name').prop('required', true);
                    $('#new_dob').prop('required', true); 
                    $('#new_phone_number').prop('required', true);
                } else {
                    $('#new_patient_form').slideUp();
                    //ยกเลิก Required ทั้งหมด
                    $('#new_first_name').prop('required', false);
                    $('#new_last_name').prop('required', false);
                    $('#new_dob').prop('required', false);
                    $('#new_phone_number').prop('required', false);
                }
            });
            
            // ตรวจสอบสถานะการเลือกเมื่อโหลดหน้า
            if ($('#id_patient').val() === 'NEW') {
                $('#new_patient_form').show();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>