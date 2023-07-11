define(['jquery', 'bootstrapTour'], function ($) {
    // Instance the tour
    var FrontendTour = new Tour({
        onStart: function () {
            sessionStorage.setItem("currentTour", "FrontendTour");
            return true
        },
        onEnd: function () {
            sessionStorage.setItem("currentTour", "");
            return true
        },
        debug: false,
        backdrop: true,
        storage: window.localStorage,
        steps: [
            {
                //path: "/",
                element: "#start-frontend-tour",
                title: "Frontend Settings Tour",
                placement: "bottom",
                content: "Tour through the Frontend Settings, which are available in the Cookie Manager."
            },
            {
                element: "body > div > div.module-body.t3js-module-body > div > div.module-body.t3js-module-body.cf_manager > div.typo3-TCEforms > div > ul > li:nth-child(3) > a",
                orphan: true,
                title: "Configure 'External Media'.",
                content: "In this Example we configure the Youtube Provider, which is part of the Category 'External Media'."
            },
            {
                element: "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend > form > div.recordlist-heading.multi-record-selection-panel > div:nth-child(1) > div.recordlist-heading-actions > select",
                orphan: true,
                title: "Languages",
                content: "Select the language you want to edit, for a better Overview. To list all languages, select 'Localization'."
            },
            {
                element: "#recordlist-tx_cfcookiemanager_domain_model_cookiefrontend > div > table > tbody > tr:nth-child(1) > td.col-control.nowrap > div:nth-child(1) > a",
                orphan: true,
                placement: "top",
                title: "Edit a Frontend Setting",
                content: "Click on the 'Edit' Icon to edit the Frontend Setting."
            },
            {
                element: "#EditDocumentController > div > div:nth-child(1) > ul",
                orphan: true,
                placement: "bottom",
                title: "Tabs",
                content: "The Consent Modal is what the user sees when he visits the website for the first time. <br> <br>The Settings Modal informs the user about the use of cookies. The Settings Modal is used to configure the Optins/Optouts of the Services.<br>"
            },
            {
                element: "body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button",
                orphan: true,
                title: "Save and close",
                content: "more todo... sorry come back later."
            },
        ]
    });

    // Initialize the tour
    if (sessionStorage.getItem("currentTour") === "FrontendTour") {
        FrontendTour.init();
    }
    $("#start-frontend-tour").click(function (e) {
        FrontendTour.init();
        //CategoriesTour.start();
        FrontendTour.restart();
    });

});


