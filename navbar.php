<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8f6fb;
    }

    /* Navbar */
    .navbar {
        background: linear-gradient(90deg, #6a0dad, #8a2be2);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .navbar-brand {
        color: white !important;
        font-weight: 600;
        letter-spacing: 0.5px;
        font-size: 1.25rem;
        transition: transform 0.2s;
    }
    .navbar-brand:hover {
        transform: scale(1.05);
    }

    .nav-link {
        color: #f3e8ff !important;
        font-weight: 500;
        transition: all 0.3s ease;
        border-radius: 8px;
        padding: 8px 14px !important;
        font-size: 1rem;
    }
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.15);
    }

    .nav-link.active {
        background-color: white !important;
        color: #6a0dad !important;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(255,255,255,0.3);
    }

    .navbar-toggler {
        border-color: rgba(255,255,255,0.5);
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">💜 Dental คนโก้</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">

        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">หน้าแรก</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'appointments.php') ? 'active' : ''; ?>" href="appointments.php">รายการนัดหมาย</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'patient_list.php') ? 'active' : ''; ?>" href="patient_list.php">รายชื่อผู้ป่วย</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'record_history.php') ? 'active' : ''; ?>" href="record_history.php">บันทึกการรักษา</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'history_list.php') ? 'active' : ''; ?>" href="history_list.php">ประวัติการรักษา</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'dentist_revenue_report.php') ? 'active' : ''; ?>" href="dentist_revenue_report.php">รายงานรายได้</a>
        </li>

      </ul>
    </div>
  </div>
</nav>
