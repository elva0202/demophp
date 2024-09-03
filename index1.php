<?php

// 用函數從指定的 URL 獲取網站內容
function fetchWebPage($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $htmlContent = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "CURL 錯誤: " . curl_error($ch);
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return $htmlContent;
}

// 提取 <table> 元素
function extractTables($html)
{
    $tables = [];
    preg_match_all('/<table\b[^>]*>(.*?)<\/table>/is', $html, $matches);

    foreach ($matches[0] as $table) {
        $tables[] = $table;
    }

    return $tables;
}

// 提取 <tbody> 元素
function extractTbodysFromTable($tableHtml)
{
    $tbodys = [];
    preg_match_all('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $tableHtml, $matches);

    foreach ($matches[0] as $tbody) {
        $tbodys[] = $tbody;
    }

    return $tbodys;
}

// 清除方括號[]及內容移除多餘空格
function cleanData($data)
{
    $data = preg_replace('/\[.*?\]/', '', $data);
    return trim(preg_replace('/\s+/', ' ', $data));
}

// 提取特定賽事信息
function parseMatchDetails($tbodyHtml)
{
    $matchDetailsList = [];

    //正則表達式提取每一行<tr>標籤
    preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $tbodyHtml, $rows);

    foreach ($rows[0] as $row) {
        $matchDetails = [
            'eventid' => '',
            'number' => '',
            'event' => '',
            'gametime' => '',
            'away_team' => '',
            'home_team' => '',
            'negative_odds' => '',
            'winning_odds' => '',
            'data_Sources' => ''
        ];
        preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $row, $cells);

        // 提取 eventid
        if (isset($cells[1])) {
            preg_match('/<tr\b[^>]*\bid=["\']?([0-9a-zA-Z_]+)["\']?/i', $row, $eventidMatch);
            $matchDetails['eventid'] = isset($eventidMatch[1]) ? preg_replace('/\D/', '', cleanData(strip_tags($eventidMatch[1]))) : 'N/A';
        }

        // 提取 gametime
        if (isset($cells[1][2])) {
            preg_match('/<span\b[^>]*\btitle=["\']?比赛时间:([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2})["\']?/i', $row, $gametimeMatch);
            $gametime = isset($gametimeMatch[1]) ? cleanData(strip_tags($gametimeMatch[1])) : '0000-00-00 00:00';

            // 檢查並補充秒數 ':00'
            if (strlen($gametime) == 16) {
                $gametime .= ':00';
            }

            // 確保格式符合 'Y-m-d H:i:s'
            $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $gametime);
            if ($datetime === false) {
                $gametime = null; // 如果格式錯誤，設為NULL
            } else {
                $gametime = $datetime->format('Y-m-d H:i:s');
            }

            $matchDetails['gametime'] = $gametime;
        }


        //提取<tr>標籤中的evenid
        if (isset($cells[0][0])) {
            preg_match('/<tr\b[^>]*\bid=["\']?([0-9a-zA-Z_]+)["\']?/i', $row, $eventidMatch);
        }

        //提取<i>標籤中的number
        if (isset($cells[1][0])) {
            preg_match('/<i\b[^>]*>(.*?)<\/i>/is', $cells[1][0], $numberMatch);
            $matchDetails['number'] = isset($numberMatch[1]) ? cleanData(strip_tags($numberMatch[1])) : 'N/A';
        }
        //提取賽事
        if (isset($cells[1][1])) {
            $matchDetails['event'] = cleanData(strip_tags($cells[1][1]));
        }
        //提取客隊
        if (isset($cells[1][3])) {
            $matchDetails['away_team'] = str_replace(['[', ']'], '', cleanData(strip_tags($cells[1][3])));
        }
        //提取主隊
        if (isset($cells[1][5])) {
            $matchDetails['home_team'] = str_replace(['[', ']'], '', cleanData(strip_tags($cells[1][5])));
        }
        //提取負/勝賠率
        if (isset($cells[1][6])) {
            preg_match_all('/<a\b[^>]*>(.*?)<\/a>/is', $cells[1][6], $bets_areaMatch);
            $matchDetails['negative_odds'] = isset($bets_areaMatch[1][0]) ? cleanData(strip_tags($bets_areaMatch[1][0])) : 'N/A';
            $matchDetails['winning_odds'] = isset($bets_areaMatch[1][1]) ? cleanData(strip_tags($bets_areaMatch[1][1])) : 'N/A';
        }
        //提取來源
        if (isset($cells[1][7])) {
            preg_match('/<a\b[^>]*>(.*?)<\/a>/is', $cells[1][7], $numberMatch);
            $matchDetails['data_Sources'] = isset($numberMatch[1]) ? cleanData(strip_tags($numberMatch[1])) : 'N/A';
        }



        $matchDetailsList[] = $matchDetails;
    }

    return $matchDetailsList;
}

// 目標網頁的 URL
$url = "https://cp.zgzcw.com/lottery/jcplayvsForJsp.action?lotteryId=26&issue=2024-09-01";

$htmlContent = fetchWebPage($url);
if ($htmlContent) {
    require_once 'sql/db1.php';

    $tables = extractTables($htmlContent);

    echo '<table border="1" cellpadding="10" cellspacing="0">';
    echo '<tr>';
    echo '<th>比賽編號</th>';
    echo '<th>編號</th>';
    echo '<th>賽事</th>';
    echo '<th>比賽時間</th>';
    echo '<th>客隊</th>';
    echo '<th>主隊</th>';
    echo '<th>負</th>';
    echo '<th>勝</th>';
    echo '<th>來源</th>';
    echo '</tr>';

    //遍歷 $table數組 ，數組中儲存了網頁中提取的table元素HTML
    foreach ($tables as $index => $tableHtml) {
        $tbodys = extractTbodysFromTable($tableHtml);
        //從表格中提取tbody元素
        foreach ($tbodys as $tbodyHtml) {
            $matchDetailsList = parseMatchDetails($tbodyHtml);

            $sql_values = [];

            foreach ($matchDetailsList as $details) {
                //檢查eventid是否為空
                if (empty($details['eventid'])) {
                    echo "Skipping entry with empty eventid.";
                    continue;
                }
                //處理gametime如果是NULL設為NULL
                $gametimeValue = !is_null($details['gametime']) ? "'" . $conn->real_escape_string($details['gametime']) . "'" : "NULL";
                $sql_values[] = "(
                            '" . $conn->real_escape_string($details['eventid']) . "',
                            '" . $conn->real_escape_string($details['number']) . "',
                            '" . $conn->real_escape_string($details['event']) . "',
                            $gametimeValue,
                            '" . $conn->real_escape_string($details['away_team']) . "',
                            '" . $conn->real_escape_string($details['home_team']) . "',
                            '" . $conn->real_escape_string($details['negative_odds']) . "',
                            '" . $conn->real_escape_string($details['winning_odds']) . "',
                            '" . $conn->real_escape_string($details['data_Sources']) . "'
                            )";
                // // 檢查是否已存在該 eventid
                // $eventid = $conn->real_escape_string($details['eventid']);
                // $delete_sql = "DELETE FROM new_table WHERE eventid='$eventid'";
                // $conn->query($delete_sql);  //執行刪除操作
            }
        }
        if (!empty($sql_values)) {
            $sql = "INSERT INTO new_table(
                    eventid, number, event, gametime, away_team, home_team, negative_odds, winning_odds, data_sources
                ) VALUES " . implode(", ", $sql_values) . "
                ON DUPLICATE KEY UPDATE  
                    eventid = VALUeS(eventid),
                    number = VALUES(number),
                    event = VALUES(event),
                    gametime = VALUES(gametime),
                    away_team = VALUES(away_team),
                    home_team = VALUES(home_team),
                    negative_odds = VALUES(negative_odds),
                    winning_odds = VALUES(winning_odds),
                    data_Sources = VALUES(data_Sources)";
            var_dump($sql);
            //eventid不存在進行插入，存在則進行更新

            if ($conn->query($sql) === TRUE) {
                echo "儲存成功";
            } else {
                echo "錯誤" . $conn->error;
            }
            echo '<tr>';
            echo '<td>' . ($details['eventid']) . '</td>';
            echo '<td>' . ($details['number']) . '</td>';
            echo '<td>' . ($details['event']) . '</td>';
            echo '<td>' . ($details['gametime']) . '</td>';
            echo '<td>' . ($details['away_team']) . '</td>';
            echo '<td>' . ($details['home_team']) . '</td>';
            echo '<td>' . ($details['negative_odds']) . '</td>';
            echo '<td>' . ($details['winning_odds']) . '</td>';
            echo '<td>' . ($details['data_Sources']) . '</td>';
            echo '</tr>';
        }
    }
    $conn->close();
} else {
    echo "無法獲取網頁內容。\n";
}
