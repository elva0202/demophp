<?php
header('Content-Type: application/json'); // 設定回應格式為 JSON
require_once '../sql/db1.php';  // 引入資料庫連接設定

// 檢查連接是否成功
if (!$conn) {
    echo json_encode(["error" => "連接失敗"]);
    exit(); // 如果連接失敗，終止後續代碼執行
}

// 查詢 events_table 表的資料
$sql = "SELECT id, eventid, number, event, gametime, away_team, home_team, negative_odds, winning_odds, data_sources FROM new_table";
$result = mysqli_query($conn, $sql);

// 檢查 SQL 查詢是否有錯誤
if (!$result) {
    echo json_encode(["error" => mysqli_error($conn)]);
    mysqli_close($conn);
    exit(); // 停止後續執行
}

// 如果查詢有結果，回傳數據
if (mysqli_num_rows($result) > 0) {
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);  // 回傳查詢結果，並確保中文不被轉碼
} else {
    echo json_encode(["message" => "無資料"], JSON_UNESCAPED_UNICODE);
}

// 關閉資料庫連接
mysqli_close($conn);