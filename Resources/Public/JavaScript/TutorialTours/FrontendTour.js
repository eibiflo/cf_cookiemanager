define(['jquery'], function ($) {
    // Instance the tour
    return {
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
            storage: window.sessionStorage,
            steps: [
                {
                    //path: "/",
                    element: "#start-frontend-tour",
                    title: "Frontend Settings Tour",
                    placement: "bottom",
                    content: "This tour is Interactive,<br> you have to click on the elements to continue if <br>the <strong>Next button</strong> is <strong>disabled</strong>!."
                },
                {
                    element: "body > div > div.module-body.t3js-module-body > div > div.module-body.t3js-module-body.cf_manager > div.typo3-TCEforms > div > ul > li:nth-child(3) > a",
                    orphan: true,
                    reflex: true,
                    title: "Frontend Settings Tour",
                    prev: -1, //Disable prev Button because User should click on the Tab
                    next: -1, //Disable next Button because User should click on the Tab
                    onNext: function (tour) {
                        //Jump to next step
                        tour.goTo(2);
                    },
                    content: "<strong>Click on the Tab 'Frontend Settings'</strong> , <br>to navigate to the Frontend Configuration.",
                },
                {
                    element: "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend > form > div.recordlist-heading.multi-record-selection-panel > div:nth-child(1) > div.recordlist-heading-actions > select",
                    orphan: true,
                    title: "Languages",
                    content: "In the language selection you filter trough languages to edit. <br>For a list of all languages, select 'Localization' <br> <strong>For this example we need your Main Language</strong>."
                },
                {
                    element: "#recordlist-tx_cfcookiemanager_domain_model_cookiefrontend > div > table > tbody > tr:nth-child(1) > td.col-control.nowrap > div:nth-child(1) > a",
                    orphan: true,
                    placement: "top",
                    title: "Edit a Frontend Setting",
                    reflex: true,
                    next: -1, //Disable next Button because User should click on the Edit Icon
                    onNext: function (tour) {
                        //Jump to next step
                        tour.goTo(4);
                    },
                    content: "Click on the 'Edit' Icon to edit the Frontend Setting."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > ul",
                    orphan: true,
                    placement: "bottom",
                    title: "Tabs",
                    prev: -1, //Disable prev Button because iframe
                    content: "The Consent Modal is what the user sees when he visits the website for the first time. <br> <br>The Settings Modal informs the user about the use of cookies. The Settings Modal is used to configure the Optins/Optouts of the Services.<br>"
                },
                {
                    element: "body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button",
                    orphan: true,
                    reflex: true,
                    title: "Save and close",
                    content: "more todo... sorry come back later."
                },
            ]
        };
});


