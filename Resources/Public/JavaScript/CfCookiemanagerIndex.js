define(['jquery', 'jqueryDatatable'], function ($, jqueryDatatable) {


    function hookRecordTable(){

        var lastSelection = false;
        //get last Selection
        if (sessionStorage.getItem("cf_current_search")) {
            lastSelection = sessionStorage.getItem("cf_current_search");
        }

        var availableLocalizations = {};
        $(".col-localizationa").each(function (index) {
            availableLocalizations[$(this).text().trim()] = $(this).text().trim();
        });


        $(".cf_manager th").parent().hide();
        $(".cf_manager .col-selector").remove();
        $(".cf_manager typo3-backend-column-selector-button").remove();
        //$(".cf_manager .col-selector").parent().remove();

        let col = $(".cf_manager .recordlist-heading .col-auto");
        //Create Select
        col.append("<select class='cfLanguageHook form-select form-control-adapt'></select>");
        //Add Options
        $.each(availableLocalizations, function (index) {
            var selected = "";
           if(lastSelection !== false && lastSelection === index){
               selected = "selected";
               $(".cf_manager .t3js-entity").hide();
               $(".cf_manager [title=\""+lastSelection+"\"]").closest(".t3js-entity").show();
           }
            col.find("select").append("<option "+selected+" value='" + index + "'>" + index + "</option>");
        });
        //Bind Filter function
        col.find("select").change(function () {
            $(".cfLanguageHook").val($(this).val());
            if($("[title=\""+$(this).val()+"\"]").length === 0){
                $(".t3js-entity").show();
            }else{
                sessionStorage.setItem("cf_current_search", $(this).val());
                $(".t3js-entity").hide();
                $("[title=\""+$(this).val()+"\"]").closest(".t3js-entity").show();
            }
        });

    }



    hookRecordTable();

    if (sessionStorage.getItem("cf_current_tab")) {
        $(".cf_manager .active").removeClass("active");
        $("[aria-controls=" + sessionStorage.getItem("cf_current_tab") + "]").addClass("active");
        $("#" + sessionStorage.getItem("cf_current_tab")).addClass("active");
    }
    $(".cf_manager .t3js-tabmenu-item").click(function () {
        sessionStorage.setItem("cf_current_tab", $(this).find("a").attr("aria-controls"));
    });


    $(".tx_cfcookiemanager").DataTable({
        "aaSorting": [[ 0, "desc" ]],
        "language": {
            "decimal": ",",
            "thousands": "",
            "lengthMenu": "_MENU_ Eintr??ge werden angezeigt",
            "search": "Suchen:",
            "zeroRecords": "Keine Eintr??ge gefunden",
            "info": "Seite _PAGE_ von _PAGES_ wird angezeigt",
            "infoEmpty": "Eintr??ge nicht verf??gbar",
            "infoFiltered": "(gefiltert von _MAX_ totalen eintr??gen)",
            "paginate": {
                "first": "Erste Seite",
                "last": "Letzt Seite",
                "next": "N??chste Seite",
                "previous": "Vorige Seite"
            },
        },
        "pageLength": 10,
        lengthMenu: [
            [10, 75, 100, 200, 500, 1000, -1],
            [10, 75, 100, 200, 500, 1000, 'All']]
    });

});


