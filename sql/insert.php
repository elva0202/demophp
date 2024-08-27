<?php
// 連接資料庫
require_once 'db1.php';

// 檢查是否有接收到 POST 資料
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 接收POST過來的資料
    $eventid = $conn->real_escape_string($_POST['eventid']);
    $number = $conn->real_escape_string($_POST['number']);
    $event = $conn->real_escape_string($_POST['event']);
    $gametime = $conn->real_escape_string($_POST['gametime']);
    $away_team = $conn->real_escape_string($_POST['away_team']);
    $home_team = $conn->real_escape_string($_POST['home_team']);
    $negative_odds = $conn->real_escape_string($_POST['negative_odds']);
    $winning_odds = $conn->real_escape_string($_POST['winning_odds']);
    $data_sources = $conn->real_escape_string($_POST['data_sources']);

    // 檢查是否已存在該 eventid
    $check_sql = "SELECT eventid FROM new_table WHERE eventid = '$eventid'";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        // 如果已存在，返回重複的訊息
        echo json_encode(["status" => "error", "message" => "eventid 重複，未插入"]);
    } else {
        // 如果不存在，插入新數據
        $sql = "INSERT INTO new_table (eventid, number, event, gametime, away_team, home_team, negative_odds, winning_odds, data_sources)
                VALUES ('$eventid', '$number', '$event', '$gametime', '$away_team', '$home_team', '$negative_odds', '$winning_odds', '$data_sources')";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "新紀錄插入成功"]);
        } else {
            echo json_encode(["status" => "error", "message" => "插入錯誤: " . $conn->error]);
        }
    }

    // 關閉資料庫連接
    $conn->close();
}
?>