define(['jquery', 'jqueryDatatable'], function ($, jqueryDatatable) {
    // console.log($);
    // console.log(jqueryDatatable);

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
        "pageLength": 100,
        lengthMenu: [
            [50, 75, 100, 200, 500, 1000, -1],
            [50, 75, 100, 200, 500, 1000, 'All']]
    });
});
