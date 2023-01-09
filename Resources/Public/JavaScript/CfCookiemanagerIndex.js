define(['jquery', 'jqueryDatatable'], function ($, jqueryDatatable) {


    if (sessionStorage.getItem("cf_current_tab")) {
        $(".cf_manager .active").removeClass("active");
        $("[aria-controls="+sessionStorage.getItem("cf_current_tab")+"]").addClass("active");
        $("#"+sessionStorage.getItem("cf_current_tab")).addClass("active");
    }
    $(".t3js-tabmenu-item").click(function (){
        console.log($(this).find("a").attr("aria-controls"));
        sessionStorage.setItem("cf_current_tab", $(this).find("a").attr("aria-controls"));
    });




    $(".tx_cfcookiemanager").DataTable({
        "language": {
            "decimal": ",",
            "thousands": "",
            "lengthMenu": "_MENU_ Einträge werden angezeigt",
            "search": "Suchen:",
            "zeroRecords": "Keine Einträge gefunden",
            "info": "Seite _PAGE_ von _PAGES_ wird angezeigt",
            "infoEmpty": "Einträge nicht verfügbar",
            "infoFiltered": "(gefiltert von _MAX_ totalen einträgen)",
            "paginate": {
                "first": "Erste Seite",
                "last": "Letzt Seite",
                "next": "Nächste Seite",
                "previous": "Vorige Seite"
            },
        },
        "pageLength": 10,
        lengthMenu: [
            [10, 75, 100, 200, 500, 1000, -1],
            [10, 75, 100, 200, 500, 1000, 'All']]
    });
});
