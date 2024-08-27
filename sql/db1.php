<?php
$servername = "127.0.0.1";  // MySQL 伺服器的地址
$username = "root";         // MySQL 使用者名稱
$password = "00000000";     // MySQL 使用者的密碼
$dbname = "test01";         // 要連接的資料庫名稱

// 與 MySQL 資料庫建立連接
$conn = mysqli_connect($servername, $username, $password, $dbname);

// 檢查連接是否成功
if (!$conn) {
    die("連接失敗: " . mysqli_connect_error());
}
// else
//     echo "成功";



// 設置資料庫連接的字符集
mysqli_set_charset($conn, "utf8");
