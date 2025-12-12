<?php
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>หน้าแรก | Dental คนโก้</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #6a0dad;
      --secondary-color: #f3e8ff;
    }

    body {
      font-family: 'Tahoma', sans-serif;
      background-color: var(--secondary-color);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .hero-section {
      background: linear-gradient(135deg, #6a0dad, #8a2be2);
      color: white;
      text-align: center;
      padding: 80px 20px;
      border-bottom-left-radius: 50px;
      border-bottom-right-radius: 50px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .hero-section h1 {
      font-size: 2.5rem;
      font-weight: bold;
    }

    .hero-section p {
      font-size: 1.2rem;
      margin-top: 10px;
    }

    .menu-section {
      flex: 1;
      margin-top: -40px;
    }

    .card-menu {
      border: none;
      border-radius: 20px;
      transition: all 0.3s ease;
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }

    .card-menu:hover {
      transform: translateY(-8px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.25);
    }

    .card-menu .icon {
      font-size: 2.5rem;
      color: var(--primary-color);
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

  <div class="hero-section">
    <h1> คนโก้ ระบบบริหารคลีนิคทันตกรรมครบวงจร</h1>
    <p>ยกระดับคลินิกด้วยคนโก้ ให้เราดูแลคุณ</p>
  </div>

  <div class="container menu-section">
    <div class="row justify-content-center g-4 mt-5">

      <!-- ปุ่มเมนู 1 -->
      <div class="col-md-4">
        <a href="appointments.php" class="text-decoration-none text-dark">
          <div class="card card-menu text-center p-4">
            <div class="icon"></div>
            <h5 class="fw-bold">รายการนัดหมาย</h5>
            <p class="text-muted mb-0">จัดการการนัดหมายของคนไข้</p>
          </div>
        </a>
      </div>

      <!-- ปุ่มเมนู 2 -->
      <div class="col-md-4">
        <a href="patient_list.php" class="text-decoration-none text-dark">
          <div class="card card-menu text-center p-4">
            <div class="icon"></div>
            <h5 class="fw-bold">รายชื่อคนไข้</h5>
            <p class="text-muted mb-0">ดูและค้นหาข้อมูลผู้ป่วยทั้งหมด</p>
          </div>
        </a>
      </div>

      <!-- ปุ่มเมนู 3 -->
      <div class="col-md-4">
        <a href="record_history.php" class="text-decoration-none text-dark">
          <div class="card card-menu text-center p-4">
            <div class="icon"></div>
            <h5 class="fw-bold">บันทึกการรักษา</h5>
            <p class="text-muted mb-0">บันทึกและดูข้อมูลการรักษา</p>
          </div>
        </a>
      </div>

      <!-- ปุ่มเมนู 4 -->
      <div class="col-md-4">
        <a href="history_list.php" class="text-decoration-none text-dark">
          <div class="card card-menu text-center p-4">
            <div class="icon"></div>
            <h5 class="fw-bold">ประวัติการรักษา</h5>
            <p class="text-muted mb-0">ดูประวัติการรักษาทั้งหมด</p>
          </div>
        </a>
      </div>

      <!-- ปุ่มเมนู 5 -->
      <div class="col-md-4">
        <a href="dentist_revenue_report.php" class="text-decoration-none text-dark">
          <div class="card card-menu text-center p-4">
            <div class="icon"></div>
            <h5 class="fw-bold">รายงานรายได้ทันตแพทย์</h5>
            <p class="text-muted mb-0">สรุปรายได้ของแต่ละทันตแพทย์</p>
          </div>
        </a>
      </div>

    </div>
  </div>

  <footer class="text-center py-4 mt-auto text-muted">
    <small>© 2025 Dental ระบบการจัดการคลินิกทันตกรรม — พัฒนาโดยทีมคนโก้ มหาวิทยาลัยเกษตรศาสตร์ ศรีราชา</small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
