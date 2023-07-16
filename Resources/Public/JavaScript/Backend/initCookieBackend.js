require(['jquery', 'jqueryDatatable'], function ($, jqueryDatatable) {

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

        let col = $(".cf_manager .recordlist-heading .col-auto .btn-group.me-2");
        if(col.length === 0 || typeof col === "undefined"){
            col = $(".cf_manager .recordlist-heading .recordlist-heading-actions");
        }

        //Create Select
        col.prepend("<select class='cfLanguageHook form-select form-control-adapt'></select>");
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

    $(".tx_cfcookiemanager").DataTable({
        "aaSorting": [[ 0, "desc" ]],
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


    $(".settings-item-head").click(function(){
        let category = $(this).parent().data("category");
        $(this).parent().find(".setting-item-row").toggle();
        $(this).toggleClass("settings-item-head-line");

        // Retrieve the current status object from session storage or create a new one if it doesn't exist
        var categoryStatus = JSON.parse(sessionStorage.getItem("categoryStatus")) || {};
        categoryStatus[category] = $(this).parent().find(".setting-item-row").css("display");
        sessionStorage.setItem("categoryStatus", JSON.stringify(categoryStatus));

        //   sessionStorage.setItem("cf_"+currentCat, $(this).parent().find(".setting-item-row").css("display") !== "none");
    });

    if (sessionStorage.getItem("cf_current_tab")) {
        $(".cf_manager .active").removeClass("active");
        $("[aria-controls=" + sessionStorage.getItem("cf_current_tab") + "]").addClass("active");
        $("#" + sessionStorage.getItem("cf_current_tab")).addClass("active");
    }

    $(".cf_manager .t3js-tabmenu-item").click(function () {
        sessionStorage.setItem("cf_current_tab", $(this).find("a").attr("aria-controls"));

    });



    var categoryStatus = JSON.parse(sessionStorage.getItem("categoryStatus")) || {};
    // Loop through each category in Home tab and show or hide the corresponding div based on its status
    for (var category in categoryStatus) {
        if (categoryStatus.hasOwnProperty(category)) {
            var divToToggle = $('[data-category=\"' + category + '\"]');
            if(categoryStatus[category] === "block"){
                divToToggle.find(".settings-item-head").addClass("settings-item-head-line");
            }
            divToToggle.find(".setting-item-row").css("display",categoryStatus[category]);
        }
    }


    $(".loadingcontainer").hide();
    $(".cf_manager").show();

    return {cookieBackendLoaded: 1};
});