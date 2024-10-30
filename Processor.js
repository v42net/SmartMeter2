process.env["NODE_CONFIG_DIR"] = __dirname;
process.env["NODE_ENV"] = "Processor";

const config = require('config');
const fs = require("fs");
const net = require('node:net'); 
const path = require('path'); 
const debug = false;

const p1_keys = {
    "0-0:1.0.0": "T",
    "1-0:1.8.1": "P1",
    "1-0:1.8.2": "P2",
    "0-1:24.2.1": "G"
};

class Processor {
    //-------------------------------------------------------------------------
    // Process methods
    //-------------------------------------------------------------------------
    constructor(config) {
        console.log("Processor started")   
        this.Configure(config);
        this.LoadState();
        this.abort = false;
        this.data = {};
        this.period = {};
        this.ProcessInput();
        for (var year in this.data) this.SaveData(year);
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
        this.SaveState();
    }
    ProcessLine(line) {
        const parts = line.split("(");
        if (! (parts[0] in p1_keys)) return;
        if (parts.length < 2) return;
        switch(p1_keys[parts[0]]) {
            case "T": return this.ProcessTime(parts.slice(1));
            case "P1": return this.ProcessPower("P1", parts.slice(1));
            case "P2": return this.ProcessPower("P2", parts.slice(1));
            case "G": return this.ProcessGas(parts.slice(1));
        }
    }
    ProcessTime(parts) {
        this.period["T"] = false;
        if (parts.length != 1) return;
        if (! parts[0].match(/^\d{12}[WS]\)$/)) return;
        this.period["T"] = this.ParsePeriod(parts[0]);
    }
    ProcessPower(meter, parts) {
        if (! ("T" in this.period)) return;
        const period = this.period["T"];
        if (! period) return;
        if (parts.length != 1) return;
        if (! parts[0].match(/^\d{6}\.\d{3}\*kWh\)$/)) return;
        const kWh = parseFloat(parts[0]);
        switch (meter) {
            case "P1": return this.UpdatePeriod(period, meter, 0, kWh);
            case "P2": return this.UpdatePeriod(period, meter, 1, kWh);
        }
    }
    ProcessGas(parts) {
        if (parts.length != 2) return;
        if (! parts[0].match(/^\d{12}[WS]\)$/)) return;
        const period = this.ParsePeriod(parts[0]);
        if (! period) return;
        if (! parts[1].match(/^\d{5}\.\d{3}\*m3\)$/)) return;
        const m3 = parseFloat(parts[1]);
        this.UpdatePeriod(period, "G", 2, m3);
    }
    //-------------------------------------------------------------------------
    // Helper methods
    //-------------------------------------------------------------------------
    Configure(config) {
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
    LoadData(year) {
        try {
            const data = fs.readFileSync(
                path.join(this["output.path"], year+".json")
            );
            this.data[year] = JSON.parse(data); 
        }
        catch(e) {
            this.data[year] = { "day":{}, "week":{}, "month":{} };
            this.SaveData(year);
        }
    }
    LoadState() {
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
            this.state["year"] = year;
            this.state["entry"] = 0;
            if (process.argv.length > 3) {
                var entry = parseInt(process.argv[3]);
                this.state["entry"] = entry;
            }
            }
        if (this.state["year"] < 2000) {
            this.state["year"] = 2000;
            this.state["entry"] = 0;
        }
        this.SaveState();
    }
    ParsePeriod(str) {
        try {
            var ymd = str.match(/\d{2}/g);
            const period = {
                "month": [ ymd[1], "20"+ymd[0] ],
                "week": this.Week(ymd),
                "day": [ ymd[1]+ymd[2], "20"+ymd[0] ]
            }
            return period;
            }
        catch(err) {
            return false
        }
    }
    SaveData(year) {
        if (year in this.data) {
            fs.writeFileSync(
                path.join(this["output.path"], year+".json"),
                JSON.stringify(this.data[year], null, 2)
            );
        }
    }
    SaveState() {
        fs.writeFileSync(
            path.join(this["output.path"],".state"),
            JSON.stringify(this.state)
        );
    }
    UpdatePeriod(period, meter, index, value) {
        for (var dwm in period) { // day, week or month
            const year = period[dwm][1];
            if (! (year in this.data)) this.LoadData(year);
            const id = period[dwm][0];
            if (meter in this.period) {
                const lastyear = this.period[meter][dwm][1];
                if (! (lastyear in this.data)) this.LoadData(lastyear);
                const lastid = this.period[meter][dwm][0];
                if (id != lastid) { // start of new period
                    // update last meter reading for last period
                    this.UpdateRecord(lastyear, dwm, lastid, 1, index, value); // fl = 1 (last)
                    // update first meter reading for this period
                    this.UpdateRecord(year, dwm, id, 0, index, value); // fl = 0 (first)
                    if (year != lastyear) { // start of new year
                        // update last meter reading for last year
                        this.UpdateRecord(lastyear, dwm, "0", 1, index, value); // fl = 1 (last)
                        // save last year's data
                        this.SaveData(lastyear);
                        // update first meter reading for this year
                        this.UpdateRecord(year, dwm, "0", 0, index, value); // fl = 0 (first)
                    }
                }
            }
            // update last meter reading for this period and this year
            this.UpdateRecord(year, dwm, id, 1, index, value); // fl = 1 (last)
            this.UpdateRecord(year, dwm, "0", 1, index, value); // fl = 1 (last)
        }
        this.period[meter] = Object.assign({}, period);
    }
    UpdateRecord(year, dwm, id, fl, index, value) { // fl = 0 (first) or 1 (last)
        if (! (dwm in this.data[year])) this.data[year][dwm] = {};
        if (! (id in this.data[year][dwm])) this.data[year][dwm][id] = [[0,0,0],[0,0,0]];
        this.data[year][dwm][id][fl][index] = value;
    }
    Week(ymd) {
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
        // const dow = (date.getDay() || 7) - 1; // 0 = Monday, 6 = Sunday
        // const sow = new Date(date.valueOf() - dow * 86400000);
        // const sowDay = sow.toISOString().slice(5,10).replace("-","");
        // const sowYear = sow.getUTCFullYear().toString();
        // const eow = new Date(sow.valueOf() + 6 * 86400000);
        // const eowDay = eow.toISOString().slice(5,10).replace("-","");
        // const eowYear = eow.getUTCFullYear().toString();
        return [week, year]; //, sowDay, sowYear, eowDay, eowYear];
    }
}
//-----------------------------------------------------------------------------
// Only allow one instance to be active
//-----------------------------------------------------------------------------
function main() {
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
}
//-----------------------------------------------------------------------------
main();