<!DOCTYPE html>
<html><head>
    <title>SmartMeter2</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>
</head><body>
    <table><tbody>
<?php for ($i = 52; $i > 0; $i--) { ?> 
        <tr><td class="period">Week <?php print $i; ?></td><td>body2</td><td>body3</td><td>body4</td><td>body5</td><td>body6</td><td>body7</td><td>body8</td><td>body9</td><td>body10</td><td>body11</td></tr>
<?php } ?>
    </tbody><thead>
        <tr>
            <th class="title" colspan=11>... TITLE ...</th>
        </tr><tr>
            <th class="tabs" colspan=11>... tabs ...</th>
        </tr><tr>
        <tr>
            <th class="period" rowspan=2><b>Period</b></th>
            <th class="column" colspan=4><b>First Readings</b></th>
            <th class="column" colspan=4><b>Last Readings</b></th>
            <th class="column" colspan=2><b>Usage</b></th>
        </tr><tr>
            <th class="last">E1 (kWh)</th>
            <th class="last">E2 (kWh)</th>
            <th class="last">E (kWh)</th>
            <th class="last">G (m3)</th>
            <th class="last">E1 (kWh)</th>
            <th class="last">E2 (kWh)</th>
            <th class="last">E (kWh)</th>
            <th class="last">G (m3)</th>
            <th class="last">E (kWh)</th>
            <th class="last">G (m3)</th>
        </tr>
    </thead></table>

</body></html>
