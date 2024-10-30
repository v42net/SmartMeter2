<?php require '/appl/Login/www/check.php'; require 'class.php';
    
    $app = new SmartMeter2();

?><!DOCTYPE html>
<html><head>
    <title>SmartMeter2</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>
</head><body>
    <table><tbody>
<?php for ($i = 52; $i > 0; $i--) { ?> 
        <tr><td class="period">Week <?php print $i; ?></td>
            <td>0.000</td><td>0.000</td><td>0.000</td><td>0.000</td>
            <td>0.000</td><td>0.000</td><td>0.000</td><td>0.000</td>
            <td>0.000</td><td>0.000</td><td>0.000</td><td>0.000</td>
            <td>0.00</td><td>0.00</td><td>0.00</td><td>0.00</td>
            <td>0.00</td><td>0.00</td><td>0.00</td><td>0.00</td><td>0.00</td>
        </tr>
<?php } ?>
    </tbody><thead>
        <tr>
            <th class="title" colspan=22><?php print $app->language["title"]; ?></th>
        </tr><tr>
            <th class="tabs" colspan=22><div class="tabs"><?php $app->ShowTabs(); ?></div></th>
        </tr><tr>
        <tr>
            <th class="period" id="cg1" rowspan=2><b><?php print $app->language["period"]; ?></b></th>
            <th class="column" id="cg2" colspan=4><b><?php print $app->language["first"]; ?></b></th>
            <th class="column" id="cg3" colspan=4><b><?php print $app->language["last"]; ?></b></th>
            <th class="column" id="cg4" colspan=4><b><?php print $app->language["usage"]; ?></b></th>
            <th class="column" id="cg5" colspan=4><b><?php print $app->language["costs"]; ?></b></th>
            <th class="column" id="cg5" colspan=5><b><?php print $app->language["history"]; ?></b></th>
        </tr><tr>
            <th class="last" id="cg2">E1 (kWh)</th>
            <th class="last" id="cg2">E2 (kWh)</th>
            <th class="last" id="cg2">E (kWh)</th>
            <th class="last" id="cg2">G (m3)</th>
            <th class="last" id="cg3">E1 (kWh)</th>
            <th class="last" id="cg3">E2 (kWh)</th>
            <th class="last" id="cg3">E (kWh)</th>
            <th class="last" id="cg3">G (m3)</th>
            <th class="last" id="cg4">E1 (kWh)</th>
            <th class="last" id="cg4">E2 (kWh)</th>
            <th class="last" id="cg4">E (kWh)</th>
            <th class="last" id="cg4">G (m3)</th>
            <th class="last" id="cg5"><?php print $app->language["period"]; ?></th>
            <th class="last" id="cg5"><?php print $app->language["total"]; ?></th>
            <th class="last" id="cg5"><?php print $app->language["target"]; ?></th>
            <th class="last" id="cg5"><?php print $app->language["margin"]; ?></th>
            <th class="last" id="cg6"><?php print $app->year; ?></th>
            <th class="last" id="cg6"><?php print $app->year-1; ?></th>
            <th class="last" id="cg6"><?php print $app->year-2; ?></th>
            <th class="last" id="cg6"><?php print $app->year-3; ?></th>
            <th class="last" id="cg6"><?php print $app->year-4; ?></th>
        </tr>
    </thead></table>

</body></html>
