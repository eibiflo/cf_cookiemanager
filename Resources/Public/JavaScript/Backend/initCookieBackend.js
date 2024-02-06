import $ from 'jquery';
//import jqueryDatatable from 'jquery-datatable';


$(".startConfiguration").click(function () {
    $(".startConfiguration").hide();
    $(".startConfiguration").parent().append("<br\>Loading...");
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

export default {cookieBackendLoaded: 1};