<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>編號</th>
            <th>賽事</th>
            <th>客隊</th>
            <th>主隊</th>
            <th>負</th>
            <th>勝</th>
            <th>來源</th>
        </tr>
        <?php
        require_once 'db1.php';

        $sql = "SELECT id,number, event, away_team, home_team, negative_odds, winning_odds, data_Sources FROM new_table";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

            foreach ($rows as $row) {
                echo "<tr>";
                echo "<td>" . ($row['id']) . "</td>";
                echo "<td>" . ($row['number']) . "</td>";
                echo "<td>" . ($row['event']) . "</td>";
                echo "<td>" . ($row['away_team']) . "</td>";
                echo "<td>" . ($row['home_team']) . "</td>";
                echo "<td>" . ($row['negative_odds']) . "</td>";
                echo "<td>" . ($row['winning_odds']) . "</td>";
                echo "<td>" . ($row['data_Sources']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo " <tr><td colspan='7'>沒有數據</td></tr> ";
        }
        mysqli_close($conn);
        ?>
    </table>
</body>

</html>