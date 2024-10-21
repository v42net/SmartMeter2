process.env["NODE_CONFIG_DIR"] = __dirname;
process.env["NODE_ENV"] = "Processor";

const config = require('config');
const fs = require("fs");
const net = require('node:net'); 
const path = require('path'); 
const { stringify } = require('querystring');
const { start } = require('repl');
const debug = true;

const p1_keys = {
    "0-0:1.0.0": "T",
    "1-0:1.8.1": "P1",
    "1-0:1.8.2": "P2",
    "0-1:24.2.1": "G"
};

class Processor {
    constructor(config) {
        console.log("Processor started")   
        this._configure(config);
        this._load_state();
        this.abort = false;
        this.data = {};
        this.period = {};
        this.ProcessInput();
        for (var year in this.data) this._save_data(year);
        if (this.abort) {
            console.log("Processor aborted")
            process.exit(1);
        }
        console.log("Processor finished")
        process.exit(0);
    }
    ProcessInput() {
        var folders = fs.readdirSync(this["input.path"]);
        folders.sort();
        folders.forEach(foldername => {
            if (this.abort) return;
            if (isNaN(foldername)) return;
            const folderpath = path.join(this["input.path"], foldername);
            if (! fs.lstatSync(folderpath).isDirectory()) return;
            const year = parseInt(foldername);
            if (year < this.state["year"]) return;
            this.ProcessFolder(folderpath, year);
        });
    }
    ProcessFolder(folderpath, year) {
        if (debug) console.log("ProcessFolder:", folderpath)
        var files = fs.readdirSync(folderpath);
        files.sort();
        files.forEach(filename => {
            if (this.abort) return;
            const filepath = path.join(folderpath, filename);
            if (fs.lstatSync(filepath).isDirectory()) return;
            const entry = parseInt(filename);
            if (year == this.state["year"]) {
                if (entry < this.state["entry"]) return;
            }
            this.ProcessFile(filepath, year, entry);
        });
    }
    ProcessFile(filepath, year, entry) {
        if (debug) console.log("ProcessFile:", filepath);
            else console.log(filepath);
        const input = fs.readFileSync(filepath, 'utf-8');
        input.split(/[\r\n\s]+/).forEach(line => {
            if (this.abort) return;
            this.ProcessLine(line);
        });
        if (this.abort) return;
        this.state["year"] = year;
        this.state["entry"] = entry;
        this._save_state();
    }
    ProcessLine(line) {
        const parts = line.split("(");
        if (! (parts[0] in p1_keys)) return;
        if (parts.length < 2) return;
        switch(p1_keys[parts[0]]) {
            case "T": return this.ProcessTime(parts.slice(1));
            case "P1": return this.ProcessPower1(parts.slice(1));
            case "P2": return this.ProcessPower2(parts.slice(1));
            case "G": return this.ProcessGas(parts.slice(1));
        }
    }
    ProcessTime(parts) {
        this.period["T"] = this._period(parts[0]);
    }
    ProcessPower1(parts) {
        if (! ("T" in this.period)) return;
        const period = this.period["T"];
        if (! period) return;
        const kWh = parseFloat(parts[0]);
        this._update_period(period, "P1", 0, kWh);
    }
    ProcessPower2(parts) {
        if (! ("T" in this.period)) return;
        const period = this.period["T"];
        if (! period) return;
        const kWh = parseFloat(parts[0]);
        this._update_period(period, "P2", 1, kWh);
    }
    ProcessGas(parts) {
        if (parts.length < 2) return;
        const period = this._period(parts[0]);
        if (! period) return;
        const m3 = parseFloat(parts[1]);
        this._update_period(period, "G", 2, m3);
    }
    //-------------------------------------------------------------------------
    _configure(config) {
        var errors = 0;
        const requires = ['input.path','output.path'];
        requires.forEach(entry => {
            if (! config.has(entry)) {
                console.log(`Missing config: ${entry}`);
                errors++;
            }
            else {
                this[entry] = String(config.get(entry));
            }
        });
        if (errors) {
            console.log("Processor aborted")
            process.exit(1);
        }
        if (! fs.existsSync(this["output.path"])) {
            fs.mkdirSync(this["output.path"]);
        }
        return config;
    }
    _init_day(thisYear, pastYear, p, id) {
        return this._init_month(thisYear, pastYear, p, id);
    }
    _init_history(period, p) {
        if (debug) console.log("_init_history:", p, period[p]);
        const thisId = period[p][0];
        const thisYear = period[p][1];
        if (!(thisId in this.data[thisYear][p])) {
            this.data[thisYear][p][thisId] = [ // data for this period:
                -1,-1,-1,-1,-1,-1,   // firstE1, firstE2, firstG, lastE1, lastE2, lastG
                -1,-1,-1,-1,         // Readings in year-1: firstE, firstG, lastE, lastG
                -1,-1,-1,-1,         // Readings in year-2: firstE, firstG, lastE, lastG
                -1,-1,-1,-1,         // Readings in year-3: firstE, firstG, lastE, lastG
                -1,-1,-1,-1          // Readings in year-4: firstE, firstG, lastE, lastG
            ];
        }
        const y = parseInt(thisYear);
        for (var i = 1; i <= 4; i++) {
            const pastYear = (y-i).toString();
            if (! (pastYear in this.data)) this._load_data(pastYear);
            switch(p) {
                case "month":
                    this._init_month(thisYear, pastYear, p, thisId);
                    break;
                case "week":
                    this._init_week(thisYear, pastYear, p, thisId);
                    break;
                case "day":
                    this._init_day(thisYear, pastYear, p, thisId);
                    break;
            }
        }
        this.data[thisYear]["updated"] = true; 
        if (debug) console.log(this.data[thisYear][p][thisId]);
    }
    _init_month(thisYear, pastYear, p, id) {
        const index = 2 + (thisYear - pastYear) * 4;
        if (p in this.data[pastYear]) {
            var data = false;
            if (id in this.data[pastYear][p]) {
                data = this.data[pastYear][p][id];
            }
            else if ((id == "0229") && ("0301" in this.data[pastYear][p])) {
                data = this.data[pastYear][p]["0301"]; // february 29th ...
            }
            if (data) {
                if ((data[0] > 0) && (data[1] > 0)) {
                    this.data[thisYear][p][id][index] = data[0]+data[1];
                }
                if (data[2] > 0) {
                    this.data[thisYear][p][id][index+1] = data[2];
                }
                if ((data[3] > 0) && (data[4] > 0)) {
                    this.data[thisYear][p][id][index+2] = data[3]+data[4];
                }
                if (data[5] > 0) {
                    this.data[thisYear][p][id][index+3] = data[5];
                }
            }
        }
    }
    _init_week(thisYear, pastYear, p, thisId) {
        if (debug) console.log("_init_week:", thisYear, pastYear, p, thisId);
        // handle week 53 !!!
    }
    _load_data(year) {
        try {
            const data = fs.readFileSync(
                path.join(this["output.path"], year+".json")
            );
            this.data[year] = JSON.parse(data); 
            this.data[year]["updated"] = false; 
        }
        catch(e) {
            this.data[year] = { "updated": false, "month":{}, "week":{}, "day":{} };
            this._save_data(year);
        }
    }
    _load_state() {
        try {
            const data = fs.readFileSync(
                path.join(this["output.path"],".state")
            );
            this.state = JSON.parse(data); 
        }
        catch(e) {
            this.state = { "year": 2000, "entry": 0 };
        }
        if (process.argv.length > 2) {
            var year = parseInt(process.argv[2]);
            if (this.state["year"] >= year) {
                this.state["year"] = year;
                this.state["entry"] = 0;
            }
        }
        if (this.state["year"] < 2000) {
            this.state["year"] = 2000;
            this.state["entry"] = 0;
        }
        this._save_state();
    }
    _period(str) {
        try {
            var ymd = str.match(/\d{2}/g);
            const period = {
                "month": [ ymd[1], "20"+ymd[0] ],
                "week": this._week(ymd),
                "day": [ ymd[1]+ymd[2], "20"+ymd[0] ]
            }
            return period;
            }
        catch(err) {
            return false
        }
    }
    _save_data(year) {
        if (year in this.data) {
            if (this.data[year]["updated"]) {
                fs.writeFileSync(
                    path.join(this["output.path"], year+".json"),
                    JSON.stringify(this.data[year], null, 2)
                );
            }
        }
    }
    _save_state() {
        fs.writeFileSync(
            path.join(this["output.path"],".state"),
            JSON.stringify(this.state)
        );
    }
    _update_data(period, p, key, value) {
        const year = period[p][1];
        const id = period[p][0];
        if (! (p in this.data[year])) this.data[year][p] = {};
        if (! (id in this.data[year][p])) this.data[year][p][id] = [];
        this.data[year][p][id][key] = value;
        this.data[year]["updated"] = true; 
    }
    _update_period(period, meter, index, value) {
        for (var p in period) {
            const year = period[p][1];
            if (! (year in this.data)) this._load_data(year);
            if (meter in this.period) {
                if (period[p][0] != this.period[meter][p][0]) {
                    // update last meter reading for previous period
                    this._update_data(this.period[meter], p, index+3, value); 
                    // initialize the history for this period ...
                    if (meter == "P1") { // ... only once per period
                        this._init_history(period, p);
                    }
                    // update first meter reading for this period
                    this._update_data(period, p, index, value);
                    this._init_record
                }
                // update last meter reading for this period
                this._update_data(period, p, index+4, value); 
            }
        }
        this.period[meter] = Object.assign({}, period);
    }
    _week(ymd) {
        const date = new Date(Date.UTC("20"+ymd[0], ymd[1]-1, ymd[2], 12));
        var year = date.getUTCFullYear();
        var jan1 = new Date(Date.UTC(year, 0, 1, 11));
        var dow1 = (jan1.getDay() || 7) - 1; // 0 = Monday, 6 = Sunday
        var soy = new Date(jan1 - ((dow1<4)?dow1:(dow1-7)) * 86400000);
        if (date < soy) {
            year--; // date in last week of previous year !!!
            jan1 = new Date(Date.UTC(year, 0, 1, 11));
            dow1 = (jan1.getDay() || 7) - 1; // 0 = Monday, 6 = Sunday
            soy = new Date(jan1 - ((dow1<4)?dow1:(dow1-7)) * 86400000);
        }
        var week = Math.floor((date - soy)/86400000/7+1).toString();
        if (week.length < 2) week = "0" + week;
        year = year.toString();
        const dow = (date.getDay() || 7) - 1; // 0 = Monday, 6 = Sunday
        const sow = new Date(date.valueOf() - dow * 86400000);
        const sowDay = sow.toISOString().slice(5,10).replace("-","");
        const sowYear = sow.getUTCFullYear().toString();
        const eow = new Date(sow.valueOf() + 6 * 86400000);
        const eowDay = eow.toISOString().slice(5,10).replace("-","");
        const eowYear = eow.getUTCFullYear().toString();
        return [week, year, sowDay, sowYear, eowDay, eowYear];
    }
}
var processor = net.createServer(function(req, res) { res.send("ok"); });
processor.unref();
processor.on('error', function(e) {
    if (e.code === "EADDRINUSE") {
        console.log("Processor already running");
    } else {
        console.log(e);
    }
    process.exit(1);
});
processor.listen("\0smartmeter2.v42.net/processsor", function() {
    new Processor(config);
});
