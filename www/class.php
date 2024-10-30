<?php class SmartMeter2 {
    
    public $appname;
    public $inifile;
    
    function __construct() {
        $this->appname = basename(dirname(__FILE__, 2));
        $this->inifile = dirname(__FILE__, 2)."/".$this->appname.".ini";
        $this->config = @$this->array_key_tolower(@parse_ini_file($this->inifile, true, INI_SCANNER_RAW));
        if (! array_key_exists(strtolower($this->appname), $this->config)) {
            print "Missing [".$this->appname."] section in ".$this->inifile."<br/>\n";
            exit(0);
        }
        $this->LoadLanguage();
        $this->LoadYears();
        $this->LoadQuery();
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
        foreach (["title","months","weeks","days","readings","usage","details",
            "period","first","last","costs"] as $key => $entry) {
            if (! array_key_exists($entry, $language)) {
                print "Missing '$entry' in [language.*] section in ".$this->inifile."<br/>\n";
                $missing++;
            }
        }
        if ($missing > 0) {
            exit(0);
        }
        $this->language = $language;
    }
    function LoadQuery() {
        $query = explode("/",ltrim($_SERVER["QUERY_STRING"],"/")."/");
        $this->year = $query[0];
        if (! in_array($this->year, $this->years)) {
            $this->year = $this->years[0];
        }
        $this->period = $query[1];
        if (! in_array($this->period, ["months","weeks","days"])) {
            $this->period = "months";
        }
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
    function ShowTabs() {
        print "\n";
        $this->ShowYearTab();
        // $this->ShowTabSpacer();
        // $this->ShowPeriodTab("months");
        // $this->ShowPeriodTab("weeks");
        // $this->ShowPeriodTab("days");
        $this->ShowPeriodTab();
        $this->ShowTabSpacer();
        $this->ShowViewTab("readings");
        $this->ShowViewTab("usage");
        $this->ShowViewTab("details");
    }
    // function ShowPeriodTab($label) {
    //    $class = "tab";
    //    if ($label == $this->period) $class = "activetab";
    //    $year = $this->year;
    //    $text = $this->language[$label];
    //    print "                <div class=\"$class\" onclick=\"view_$label($year,event)\">$text</div>\n";
    // }
    function ShowPeriodTab() {
        $year = $this->year;
        print "                <div class=\"activetab\"><select class=\"tab\" id=\"period\" onchange=\"view_period('$year')\">\n";
        foreach (["months","weeks","days"] as $key => $period) {
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
    function ShowViewTab($label) {
        $class = "tab";
        if ($label == "readings") $class = "activetab";
        $text = $this->language[$label];
        print "                <div class=\"$class\" id=\"$label\" onclick=\"view_$label(event)\">$text</div>\n";
    }
    function ShowYearTab() {
        $period = $this->period;
        print "                <div class=\"activetab\"><select class=\"tab\" id=\"year\" onchange=\"view_year('$period')\">\n";
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
