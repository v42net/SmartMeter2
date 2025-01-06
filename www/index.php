<?php require '/appl/Login/www/check.php'; require 'class.php';
    
    $app = new SmartMeter2();

?><!DOCTYPE html>
<html><head>
    <title>SmartMeter2</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>
</head><body onload="show_view()">
    <table><tbody>
<?php 
    switch ($app->period) {
        case "month":
            $app->ShowMonths();
            break;
        case "week":
            $app->ShowWeeks();
            break;
        case "day":
            $app->ShowDays();
            break;
    }
?>
    </tbody><thead>
        <tr>
            <th class="title" colspan=22><?php print $app->language["title"]; ?></th>
        </tr><tr>
            <th class="tabs" colspan=22><div class="tabs"><?php $app->ShowTabs(); ?></div></th>
        </tr><tr>
        <tr>
            <th class="period"><b><?php print $app->language["period"]; ?></b></th>
            <th class="cg1">E1 (kWh)</th>
            <th class="cg1">E2 (kWh)</th>
            <th class="cg1">E (kWh)</th>
            <th class="cg1">G (m3)</th>
            <th class="cg2">E1 (kWh)</th>
            <th class="cg2">E2 (kWh)</th>
            <th class="cg2">E (kWh)</th>
            <th class="cg2">G (m3)</th>
            <th class="cg3">E1 (kWh)</th>
            <th class="cg3">E2 (kWh)</th>
            <th class="cg3">E (kWh)</th>
            <th class="cg3">G (m3)</th>
            <th class="cg4"><?php print $app->language["period"]; ?></th>
            <th class="cg4"><?php print $app->language["total"]; ?></th>
            <th class="cg4"><?php print $app->language["target"]; ?></th>
            <th class="cg4"><?php print $app->language["margin"]; ?></th>
            <th class="cg5"><?php print $app->year; ?></th>
            <th class="cg5"><?php print $app->year-1; ?></th>
            <th class="cg5"><?php print $app->year-2; ?></th>
            <th class="cg5"><?php print $app->year-3; ?></th>
            <th class="cg5"><?php print $app->year-4; ?></th>
        </tr>
    </thead></table>

</body></html>
