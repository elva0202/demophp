<?php

// 用函數從指定的 URL 獲取網站內容
function fetchWebPage($url)
{
    // 初始化 cURL 會話
    $ch = curl_init();

    // 設定需要請求的 URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // 執行 cURL 會話並將結果存儲在 $htmlContent 中
    $htmlContent = curl_exec($ch);

    // 檢查 cURL 錯誤
    if (curl_errno($ch)) {
        echo "CURL 錯誤: " . curl_error($ch);
        curl_close($ch); // 關閉 cURL 會話
        return false;
    }

    // 關閉 cURL 會話
    curl_close($ch);

    return $htmlContent;
}

// 提取 <table> 元素
function extractTables($html)
{
    $tables = [];

    // 使用正則表達式匹配 <table> 標籤及其內容
    preg_match_all('/<table\b[^>]*>(.*?)<\/table>/is', $html, $matches);

    // 將匹配到的 <table> 元素存儲到 $tables 數組中
    foreach ($matches[0] as $table) {
        $tables[] = $table;
    }

    return $tables;
}

// 提取 <tbody> 元素
function extractTbodysFromTable($tableHtml)
{
    $tbodys = [];

    // 使用正則表達式匹配 <tbody> 標籤及其內容
    preg_match_all('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $tableHtml, $matches);

    // 將匹配到的 <tbody> 元素存儲到 $tbodys 數組中
    foreach ($matches[0] as $tbody) {
        $tbodys[] = $tbody;
    }

    return $tbodys;
}

// 提取特定賽事信息（編號，賽事，客隊title，主隊title，賠率區域）
function parseMatchDetails($tbodyHtml)
{
    $matchDetailsList = [];

    // 使用正則表達式提取每一行 <tr> 標籤
    preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $tbodyHtml, $rows);

    foreach ($rows[0] as $row) {
        $matchDetails = [
            'number' => '',
            'event' => '',
            'away_team' => '',
            'home_team' => '',
            'negative_odds' => '',
            'winning_odds' => '',
            'data_Sources' => ''
        ];

        // 使用正則表達式提取每一行的 <td> 內容
        preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $row, $cells);

        // 確定索引位置並提取所需數據
        if (isset($cells[1][0])) {
            preg_match('/<i\b[^>]*>(.*?)<\/i>/is', $cells[1][0], $numberMatch);
            $matchDetails['number'] = isset($numberMatch[1]) ? strip_tags($numberMatch[1]) : 'N/A';
        }

        if (isset($cells[1][1])) {
            $matchDetails['event'] = strip_tags($cells[1][1]); // 假設第1列是賽事
        }

        if (isset($cells[1][3])) {
            $matchDetails['away_team'] = strip_tags($cells[1][3]); // 假設第3列是客隊title
        }

        if (isset($cells[1][5])) {
            $matchDetails['home_team'] = strip_tags($cells[1][5]); // 假設第5列是主隊title
        }

        if (isset($cells[1][6])) {
            preg_match_all('/<a\b[^>]*>(.*?)<\/a>/is', $cells[1][6], $bets_areaMatch);
            $matchDetails['negative_odds'] = isset($bets_areaMatch[1][0]) ? strip_tags($bets_areaMatch[1][0]) : 'N/A'; // 假設第6列是賠率區域
            $matchDetails['winning_odds'] = isset($bets_areaMatch[1][1]) ? strip_tags($bets_areaMatch[1][1]) : 'N/A';
        }


        if (isset($cells[1][7])) {
            preg_match('/<a\b[^>]*>(.*?)<\/a>/is', $cells[1][7], $numberMatch);
            $matchDetails['data_Sources'] = isset($numberMatch[1]) ? strip_tags($numberMatch[1]) : 'N/A';
        }//$matchDetails['data_Sources'] = strip_tags($cells[1][7]); // 假設第7列是資料來源區域


        $matchDetailsList[] = $matchDetails;
    }

    return $matchDetailsList;
}



// 目標網頁的 URL
$url = "https://cp.zgzcw.com/lottery/jcplayvsForJsp.action?lotteryId=26&issue=2024-08-15";

$htmlContent = fetchWebPage($url);
if ($htmlContent) {
    // 建立資料庫連接
    require_once 'db1.php';


    // $sql = "INSERT INTO new_table (number,event, away_team, home_team, negative_odds, winning_odds, data_sources) VALUES 
    //         (111, '奥运男篮', '塞尔维亚','德国','1.47','2.01','亚'),
    //         (112, '奥运男篮', '美國', '法国', '1.01', '5.30', '亚')";

    // if ($conn->query($sql) === TRUE) {
    //     echo "New records created successfully";
    // } else {
    //     echo "Error: " . $conn->error;
    // }
    $tables = extractTables($htmlContent); // 提取 <table> 元素
    foreach ($tables as $tableHtml) {
        $tbodys = extractTbodysFromTable($tableHtml);

        foreach ($tbodys as $tbodyHtml) {
            $matchDetailsList = parseMatchDetails($tbodyHtml);

            foreach ($matchDetailsList as $details) {
                $stmt = $conn->prepare("INSERT INTO new_table (number, event, away_team, home_team, negative_odds, winning_odds, data_sources) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $details['number'], $details['event'], $details['away_team'], $details['home_team'], $details['negative_odds'], $details['winning_odds'], $details['data_Sources']);

                if (!$stmt->execute()) {
                    echo "資料插入失敗: " . $stmt->error;
                }
            }
        }
    }
    $conn->close();
    // 調用 fetchWebPage 函數來獲取指定 URL 的 HTML 內容
    $htmlContent = fetchWebPage($url);
}
if ($htmlContent) {
    $tables = extractTables($htmlContent); // 提取 <table> 元素

    echo '<table border="1" cellpadding="10"    cellspacing="0">';
    echo '<tr>';
    echo '<th>編號</th>';
    echo '<th>賽事</th>';
    echo '<th>客隊</th>';
    echo '<th>主隊</th>';
    echo '<th>負</th>';
    echo '<th>勝</th>';
    echo '<th>來源</th>';
    echo '</tr>';


    // 提取表格數據
    foreach ($tables as $index => $tableHtml) {
        // 提取當前表格中的 <tbody> 元素
        $tbodys = extractTbodysFromTable($tableHtml);


        // 解析並輸出賽事詳細信息
        foreach ($tbodys as $tbodyHtml) {
            $matchDetailsList = parseMatchDetails($tbodyHtml);

            foreach ($matchDetailsList as $details) {
                echo '<tr>';
                echo '<td>' . $details['number'] . '</td>';
                echo '<td>' . $details['event'] . '</td>';
                echo '<td>' . $details['away_team'] . '</td>';
                echo '<td>' . $details['home_team'] . '</td>';
                echo '<td>' . $details['negative_odds'] . '</td>';
                echo '<td>' . $details['winning_odds'] . '</td>';
                echo '<td>' . $details['data_Sources'] . '</td>';
                echo '</tr>';
            }
        }
    }
} else {
    echo "無法獲取網頁內容。\n";
}

