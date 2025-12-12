<?php
$serverName = " "; 
$uid = "  ";         
$pwd = " ";     

$dbName = "Dental"; 


$dsn = "sqlsrv:Server=$serverName;Database=$dbName";

$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // แสดงข้อผิดพลาดแบบ Exception
);

// สร้างการเชื่อมต่อ
try {
    $conn = new PDO($dsn, $uid, $pwd, $options);
} catch (PDOException $e) {
    // หากเชื่อมต่อล้มเหลว จะแสดงข้อความผิดพลาดนี้
    die("<h1> Connection failed (เชื่อมต่อล้มเหลว):</h1><p>" . $e->getMessage() . "</p>");
}
?>
