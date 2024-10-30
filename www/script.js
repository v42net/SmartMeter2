function view_days(year,event) {
    window.location.href = "?/"+year+"/days";
}
function view_details(event) {
    // alert(`view_details(${event.ctrlKey})`);
    document.getElementById("details").className = "activetab";
    if (! event.ctrlKey) {
        document.getElementById("readings").className = "tab";
        document.getElementById("usage").className = "tab";
    }
}
function view_months(year,event) {
    window.location.href = "?/"+year+"/months";
}
function view_period(year) {
    window.location.href = "?/"+year+"/"+document.getElementById("period").value;
}
function view_readings(event) {
    // alert(`view_readings(${event.ctrlKey})`);
    document.getElementById("readings").className = "activetab";
    if (! event.ctrlKey) {
        document.getElementById("usage").className = "tab";
        document.getElementById("details").className = "tab";
    }
}
function view_usage(event) {
    // alert(`view_usage(${event.ctrlKey})`);
    document.getElementById("usage").className = "activetab";
    if (! event.ctrlKey) {
        document.getElementById("readings").className = "tab";
        document.getElementById("details").className = "tab";
    }
}
function view_weeks(year,event) {
    window.location.href = "?/"+year+"/weeks";
}
function view_year(view) {
    window.location.href = "?/"+document.getElementById("year").value+"/"+view;
}
