function show_view() {
    const v = ["period","first","last","usage","costs","history"];
    var view = document.getElementById("view").value;
    for (var cg = 1; cg < 6; cg++) {
        const elements = document.getElementsByClassName("cg"+cg);
        for (const element of elements) {
            if (view == v[cg]) {
                element.style.display = 'table-cell';
            }
            else {
                element.style.display = 'none';
            }
        }
    }
}
function show_period(year) {
    var year = document.getElementById("year").value;
    var period = document.getElementById("period").value;
    var view = document.getElementById("view").value;
    window.location.href = "?/"+year+"/"+period+"/"+view;
}
function show_year(view) {
    var year = document.getElementById("year").value;
    var period = document.getElementById("period").value;
    var view = document.getElementById("view").value;
    window.location.href = "?/"+year+"/"+period+"/"+view;
}
