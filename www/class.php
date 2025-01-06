<?php class SmartMeter2 {
    
    public $appname;
    public $inifile;
    
    function __construct() {
        date_default_timezone_set('UTC');
        $this->appname = basename(dirname(__FILE__, 2));
        $this->inifile = dirname(__FILE__, 2)."/".$this->appname.".ini";
        $this->config = @$this->array_key_tolower(@parse_ini_file($this->inifile, true, INI_SCANNER_RAW));
        if (! array_key_exists(strtolower($this->appname), $this->config)) {
            print "Missing [".$this->appname."] section in ".$this->inifile."<br/>\n";
            exit(0);
        }
        $this->tariff = [0, 0, 0]; // E1, E2, G
        if (array_key_exists("tariff", $this->config)) {
            $tariff = $this->config["tariff"];
            if (array_key_exists("e1", $tariff)) $this->tariff[0] = floatval($tariff["e1"]);
            if (array_key_exists("e2", $tariff)) $this->tariff[1] = floatval($tariff["e2"]);
            if (array_key_exists("g" , $tariff)) $this->tariff[2] = floatval($tariff["g" ]);
        }
        $this->first = []; // first readings for the selected period for each year
        $this->target = []; // target costs for the selected period for each year
        $this->LoadLanguage();
        $this->LoadYears();
        $this->LoadQuery();
        $this->LoadData();
    }
    function LoadData() {
        for ($d = 0; $d < 5; $d++) {
            $y = $this->year - $d;
            $file = dirname(__FILE__, 2)."/Processor/$y.json";
            $data = @json_decode(@file_get_contents($file), true);
            if (! is_array($data)) $data = [];
            if (! array_key_exists("day", $data)) $data["day"] = [];
            if (! array_key_exists("week", $data)) $data["week"] = [];
            if (! array_key_exists("month", $data)) $data["month"] = [];
            $this->data[$d] = $data;
            $this->first[$d] = [0,0,0]; // first readings for that year
            $this->target[$d] = 0; // target costs for that year
            if (count($data[$this->period]) == 0) continue;
            $f = $data[$this->period]["0"][0]; // first reading for that year
            $l = $data[$this->period]["0"][1]; // last reading for that year
            $u = [ $l[0]-$f[0], $l[1]-$f[1], $l[2]-$f[2] ];
            $t = $this->tariff;
            $this->first[$d] = $f;
            $this->target[$d] = $u[0]*$t[0] + $u[1]*$t[1] + $u[2]*$t[2];
        }
    }
    function LoadHistory($period, $id, $y, $t) {
        $result = [ 0, 0 ];
        $data = $this->data[$y]["$period"];
        if (! array_key_exists("$id", $data)) return $result;
        
        // not yet handling day 0229 and week 53 ...
        
        $f  = $data[$id][0]; // first readings (E1, E2, G)
        $l  = $data[$id][1]; // last readings (E1, E2, G)
        for ($i = 0; $i < 3; $i++) {
            if (($f[$i] == 0)||($l[$i] == 0)) return $result;
        }
        $u  = [ $l[0]-$f[0], $l[1]-$f[1], $l[2]-$f[2] ]; // used this period
        $c  = $u[0]*$t[0] + $u[1]*$t[1] + $u[2]*$t[2]; // costs this period
        $result[0] = $c;

        $fy = $data["0"][0]; // first reading for that year (E1, E2, G)
        $ly = $data["0"][1]; // last reading for that year (E1, E2, G)
        for ($i = 0; $i < 3; $i++) {
            if (($fy[$i] == 0)||($ly[$i] == 0)) return $result;
        }
        $uy = [ $l[0]-$fy[0], $l[1]-$fy[1], $l[2]-$fy[2] ]; // used until now
        $cy = $uy[0]*$t[0] + $uy[1]*$t[1] + $uy[2]*$t[2]; // costs until now
        $ut = [ $ly[0]-$fy[0], $ly[1]-$fy[1], $ly[2]-$fy[2] ]; // used this year
        $ct = $ut[0]*$t[0] + $ut[1]*$t[1] + $ut[2]*$t[2]; // costs this year
        $p = $cy / $ct; // fraction of total costs used until now
        $result[1] = $p;

        return $result; 
    }
    function LoadLanguage() {
        $language = false;
        foreach (array_keys($this->config) as $key => $section) {
            if (substr($section, 0, 9) == "language.") {
                $language = $this->config[$section];
                $language["language"] = substr($section, 9);
                break;
            }
        }
        if (! $language) {
            print "Missing [language.*] section in ".$this->inifile."<br/>\n";
            exit(0);
        }
        if (array_key_exists("language", $this->config[strtolower($this->appname)])) {
            $section = "language.".$this->config[strtolower($this->appname)]["language"];
            if (! array_key_exists($section, $this->config)) {
                print "Missing [".$section."] section in ".$this->inifile."<br/>\n";
                exit(0);
            }
            $language = $this->config[$section];
            $language["language"] = substr($section, 9);
        }
        $missing = 0;
        foreach (["title","month","week","day","readings","usage","details",
            "period","first","last","costs"] as $key => $entry) {
            if (! array_key_exists($entry, $language)) {
                print "Missing '$entry' in [language.*] section in ".$this->inifile."<br/>\n";
                $missing++;
            }
        }
        if ($missing > 0) {
            exit(0);
        }
        $this->monthnames = false;
        if (array_key_exists("monthnames", $language)) {
            $this->monthnames = preg_split("/[\s,]+/", $language["monthnames"]);
        }
        if (! is_array($this->monthnames)) $this->monthnames = [];
        while (count($this->monthnames) < 12) {
            $nextmonth = count($this->monthnames) + 1;
            array_push($this->monthnames, $nextmonth);
        }
        $this->language = $language;
    }
    function LoadQuery() {
        $query = explode("/",ltrim($_SERVER["QUERY_STRING"],"/")."//");
        $this->year = $query[0];
        if (! in_array($this->year, $this->years)) {
            $this->year = $this->years[0];
        }
        $this->period = $query[1];
        if (! in_array($this->period, ["month","week","day"])) {
            $this->period = "month";
        }
        $this->view = $query[2];
        if (! in_array($this->view, ["first","last","usage","costs","history"])) {
            $this->view = "last";
        }
    }
    function LoadValues($period, $id, $data) {
        $fy = $this->first[0]; // first readings for this year (E1, E2, G)
        $f  = $data[0]; // first readings (E1, E2, G)
        $l  = $data[1]; // last readings (E1, E2, G)
        $u  = [ $l[0]-$f[0], $l[1]-$f[1], $l[2]-$f[2] ]; // used this period
        $uy = [ $l[0]-$fy[0], $l[1]-$fy[1], $l[2]-$fy[2] ]; // used till now
        $t  = $this->tariff; // tariffs (E1, E2, G)
        $c  = $u[0]*$t[0] + $u[1]*$t[1] + $u[2]*$t[2]; // costs this period
        $cy = $uy[0]*$t[0] + $uy[1]*$t[1] + $uy[2]*$t[2]; // costs till now
        $p  = [ 0, 0 ]; // historical fraction, number of fractions
        $h  = [ $c ]; // load the historical usage costs for this period
        for ($y = 1; $y < 5; $y++) {
            $hy = $this->LoadHistory($period, $id, $y, $t);
            $h[$y] = $hy[0]; // previous usage costs for this period
            if ($hy[1] > 0) { // $hy[1] contains fraction
                $p[0] += $hy[1];
                $p[1]++;
            }   
        }
        if ($p[1] > 0) $p[0] = $p[0] / $p[1];   // $p[0] is average fraction
        $p[1] = $this->target[0] * $p[0];       // $p[1] is schema 

        return [
            [ $f[0], $f[1], $f[0]+$f[1], $f[2] ], 
            [ $l[0], $l[1], $l[0]+$l[1], $l[2] ],
            [ $u[0], $u[1], $u[0]+$u[1], $u[2] ],
            [ $c, $cy, 0, 0 ], 
            [ $h[0], $h[1], $h[2], $h[3], $h[4] ]
        ];
    }
    function LoadYears() {
        $folder = dirname(__FILE__, 2)."/Processor/";
        $years = scandir(dirname(__FILE__, 2)."/Processor/", SCANDIR_SORT_DESCENDING);
        $this->years = [];
        foreach ($years as $key => $value) {
            $year = intval($value);
            if ($value >= 2000) array_push($this->years, $year);
        }
        if (count($this->years) == 0) {
            print "Missing data in $folder<br/>\n";
            exit(0);
        }
    }
    function ShowData($period, $id, $data) {
        $f = $data[0]; $l = $data[1]; // first and last readings (E1, E2, G)
        $values = $this->LoadValues($period, $id, $data);
        for ($vg = 0; $vg < count($values); $vg++) {
            $cg = $vg + 1;
            print "            ";
            for ($v = 0; $v < count($values[$vg]); $v++) {
                if ($vg<3) $value = sprintf("%.3f", $values[$vg][$v]);
                    else $value = sprintf("%.2f", $values[$vg][$v]);
                print "<td class=\"cg$cg\">$value</td>";
            }
            print "\n";
        }
    }
    function ShowDay($day, $data) {
        $moy = intval($day/100); // month of year
        $dom = intval($day%100); // day of month
        $period = "$dom ".$this->monthnames[$moy-1];
        print "        <tr><td class=\"period\">$period</td>\n";
        $this->ShowData("day", $day, $data);
        print "        </tr>\n";
    }
    function ShowDays() {
        $data = $this->data[0]["day"];
        $days = array_keys($data);
        sort($days);
        foreach ($days as $d => $day) {
            if ($day > 0) $this->ShowDay($day, $data["$day"]);
        }
    }
    function ShowMonth($month, $data) {
        $period = $this->monthnames[intval($month)-1];
        print "        <tr><td class=\"period\">$period</td>\n";
        $this->ShowData("month", $month, $data);
        print "        </tr>\n";
    }
    function ShowMonths() {
        $data = $this->data[0]["month"];
        $months = array_keys($data);
        sort($months);
        foreach ($months as $d => $month) {
            if ($month > 0) $this->ShowMonth($month, $data["$month"]);
        }
    }
    function ShowTabs() {
        print "\n";
        $this->ShowYearTab();
        $this->ShowPeriodTab();
        $this->ShowViewTab();
    }
    function ShowPeriodTab() {
        $year = $this->year;
        print "                <div class=\"tab\"><select class=\"tab\" id=\"period\" onchange=\"show_period('$year')\">\n";
        foreach (["month","week","day"] as $key => $period) {
            $selected = "";
            if ($period == $this->period) $selected = "selected=\"selected\"";
            $text = $this->language[$period];
            print "                    <option value=\"$period\" $selected>$text</div>\n";
        }
        print "                </select></div>\n";
    }
    function ShowTabSpacer() {
        print "                <div class=\"tabspacer\"></div>\n";
    }
    function ShowViewTab() {
        print "                <div class=\"tab\"><select class=\"tab\" id=\"view\" onchange=\"show_view()\">\n";
        foreach (["first","last","usage","costs","history"] as $key => $view) {
            $selected = "";
            if ($view == $this->view) $selected = "selected=\"selected\"";
            $text = $this->language[$view];
            print "                    <option value=\"$view\" $selected>$text</div>\n";
        }
        print "                </select></div>\n";
    }
    function ShowWeek($week, $data) {
        $period = intval($week);
        print "        <tr><td class=\"period\">Week $period</td>\n";
        $this->ShowData("week", $week, $data);
        print "        </tr>\n";
    }
    function ShowWeeks() {
        $data = $this->data[0]["week"];
        $weeks = array_keys($data);
        sort($weeks);
        foreach ($weeks as $d => $week) {
            if ($week > 0) $this->ShowWeek($week, $data["$week"]);
        }
    }
    function ShowYearTab() {
        $period = $this->period;
        print "                <div class=\"tab\"><select class=\"tab\" id=\"year\" onchange=\"show_year('$period')\">\n";
        foreach ($this->years as $key => $year) {
            $selected = "";
            if ($year == $this->year) $selected = "selected=\"selected\"";
            print "                    <option value=\"$year\" $selected>$year</div>\n";
        }
        print "                </select></div>\n";
    }
    function array_key_tolower($arr) {
        return array_map(function($item) use($case) {
            if(is_array($item))
                $item = $this->array_key_tolower($item, $case);
            return $item;
        },array_change_key_case($arr));
    }
}
