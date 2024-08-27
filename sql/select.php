<?php
require_once 'db1.php';

// 使用 $_POST 接收來自前端的篩選條件
$minOdds = isset($_POST['minOdds']) ? floatval($_POST['minOdds']) : 0;
$teamkeyword = isset($_POST['teamkeyword']) ? $_POST['teamkeyword'] : "";
$table = isset($_POST['table']) ? $_POST['table'] : "";

// 構建 SQL 查詢語句
$sql = "SELECT eventid, number, event, gametime, away_team, home_team, negative_odds, winning_odds, data_Sources FROM `$table` WHERE 1=1";

// 根据筛选条件追加 SQL 语句
$bindTypes = "";
$bindValues = [];

if ($minOdds > 0) {
    $sql .= " AND (winning_odds >= ? OR negative_odds >= ?)";
    $bindTypes .= "dd";
    $bindValues[] = $minOdds;
    $bindValues[] = $minOdds;
}
if (!empty($teamkeyword)) {
    $sql .= " AND (away_team LIKE ? OR home_team LIKE ?)";
    $bindTypes .= "ss";
    $teamkeyword = "%" . $teamkeyword . "%";
    $bindValues[] = $teamkeyword;
    $bindValues[] = $teamkeyword;
}

// 準備查詢語句
$stmt = $conn->prepare($sql);

// 檢查 prepare 是否成功
if ($stmt === false) {
    die(json_encode(['error' => "SQL prepare failed: " . $conn->error]));
}

// 綁定參數
if (!empty($bindTypes)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

// 執行查詢並檢查是否成功
if (!$stmt->execute()) {
    die(json_encode(['error' => "SQL execute failed: " . $stmt->error]));
}

$result = $stmt->get_result();

// 初始化數據數組
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    $data['message'] = "沒有數據";
}

// 顯示 JSON 格式數據
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();