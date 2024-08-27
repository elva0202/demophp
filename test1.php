<?php


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

function extractTables($html)
{
    $tables = [];
    preg_match_all('/<table\b[^>]*>(.*?)<\/table>/is', $html, $matches);

    foreach ($matches[0] as $table) {
        $tables[] = $table;
    }

    return $tables;
}

function extractTbodysFromTable($tableHtml)
{
    $tbodys = [];
    preg_match_all('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $tableHtml, $matches);

    foreach ($matches[0] as $tbody) {
        $tbodys[] = $tbody;
    }

    return $tbodys;
}

function parseMatchDetails($tbodyHtml)
{
    $matchDetailsList = [];
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

        preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $row, $cells);

        if (isset($cells[1][0])) {
            preg_match('/<i\b[^>]*>(.*?)<\/i>/is', $cells[1][0], $numberMatch);
            $matchDetails['number'] = isset($numberMatch[1]) ? trim(strip_tags($numberMatch[1])) : 'N/A';
        }

        if (isset($cells[1][1])) {
            $matchDetails['event'] = trim(strip_tags($cells[1][1])); // 假設第1列是賽事
        }

        if (isset($cells[1][3])) {
            $matchDetails['away_team'] = trim(strip_tags($cells[1][3])); // 假設第3列是客隊title
        }

        if (isset($cells[1][5])) {
            $matchDetails['home_team'] = trim(strip_tags($cells[1][5])); // 假設第5列是主隊title
        }

        if (isset($cells[1][6])) {
            preg_match_all('/<a\b[^>]*>(.*?)<\/a>/is', $cells[1][6], $bets_areaMatch);
            $matchDetails['negative_odds'] = isset($bets_areaMatch[1][0]) ? trim(strip_tags($bets_areaMatch[1][0])) : 'N/A'; // 假設第6列是賠率區域
            $matchDetails['winning_odds'] = isset($bets_areaMatch[1][1]) ? trim(strip_tags($bets_areaMatch[1][1])) : 'N/A';
        }


        if (isset($cells[1][7])) {
            preg_match('/<a\b[^>]*>(.*?)<\/a>/is', $cells[1][7], $numberMatch);
            $matchDetails['data_Sources'] = isset($numberMatch[1]) ? trim(strip_tags($numberMatch[1])) : 'N/A';
        }//$matchDetails['data_Sources'] = strip_tags($cells[1][7]); // 假設第7列是資料來源區域


        $matchDetailsList[] = $matchDetails;
    }

    return $matchDetailsList;
}

$url = "https://cp.zgzcw.com/lottery/jcplayvsForJsp.action?lotteryId=26&issue=2024-08-09";
$htmlContent = fetchWebPage($url);

if ($htmlContent) {
    $tables = extractTables($htmlContent);
    $allMatchDetails = [];

    foreach ($tables as $tableHtml) {
        $tbodys = extractTbodysFromTable($tableHtml);

        foreach ($tbodys as $tbodyHtml) {
            $matchDetailsList = parseMatchDetails($tbodyHtml);
            $allMatchDetails = array_merge($allMatchDetails, $matchDetailsList);
        }
    }
    if (empty($allMatchDetails)) {
        echo json_encode(["error" => "No match details extracted."]);
        exit;
    }

    $servername = "127.0.0.1";
    $username = "root";
    $password = "00000000";
    $dbname = "test01";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("連接失敗: " . $conn->connect_error);
    }
    $tables = extractTables($htmlContent);

    $values = [];

    foreach ($tables as $index => $tableHtml) {
        $tbodys = extractTbodysFromTable($tableHtml);

        foreach ($tbodys as $tbodyHtml) {
            $matchDetailsList = parseMatchDetails($tbodyHtml);

            foreach ($allMatchDetails as $details) {
                $values[] = "(
                '" . $conn->real_escape_string($details['number']) . "',
                '" . $conn->real_escape_string($details['event']) . "',
                '" . $conn->real_escape_string($details['away_team']) . "',
                '" . $conn->real_escape_string($details['home_team']) . "',
                '" . $conn->real_escape_string($details['negative_odds']) . "',
                '" . $conn->real_escape_string($details['winning_odds']) . "',
                '" . $conn->real_escape_string($details['data_Sources']) . "'
            )";
            }
        }

    }


} else {
    echo "無法獲取網頁內容。\n";
}

