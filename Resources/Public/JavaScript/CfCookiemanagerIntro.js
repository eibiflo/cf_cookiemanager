define(['jquery','bootstrapTour'], function ($) {
    console.log("ok");
    // Instance the tour
    var tour = new Tour({
        onStart: function() {
            console.log("Beta Tour");
            return true
        },
        onEnd: function() {
            console.log("Beta End");
            return true
        },
        debug: true,
        steps: [
            {
                //path: "/",
                element: "#start-categories-tour",
                title: "Cookie Categories Tour",
                placement: "bottom",
                content: "Categories containing all third-party Providers, such as Youtube, Vimeo, Google Maps, etc.."
            },
            {
                element: "#externalmedia",
                orphan: true,
                title: "Configure 'External Media'.",
                content: "In this Example we configure the Youtube Provider, which is part of the Category 'External Media'."
            },
            {
                path: $("#externalmedia").find(".settings-item-head-right a").attr("href"),
                element: "fieldset:nth-child(5) > div > div > div > div > div > div:nth-child(2)",
                placement: "top",
                orphan: true,
                title: "Add Cookie Services",
                content: "Search for the Youtube provider and add it to the list of Cookie Services"
            },
            {
                element: "fieldset:nth-child(5) > div > div > div > div > div > div:nth-child(1) > div",
                orphan: true,
                placement: "top",
                title: "Youtube Provider added",
                content: "Done, the Youtube Provider is now part of the Category 'External Media'."
            },
            {
                element: "body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button",
                orphan: true,
                title: "Save and close",
                content: "Done, save and close the configuration."
            },
        ]});

    // Initialize the tour
    console.log(tour);
    tour.init();
    tour.start();

    $("#start-categories-tour").click(function(e){
       tour.restart();
    });

   // tour.restart();
/*
    $(document).on("click", "[data-service-tour]", function(e) {
        e.preventDefault();
        if ($(this).hasClass("disabled")) {
            return;
        }
        tour.restart();
        return $(".alert").alert("close");
    });
    */
});


