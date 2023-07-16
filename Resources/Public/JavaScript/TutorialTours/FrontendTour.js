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
                    content: "In the language selection you filter trough languages to edit. <br>For a list of all languages, select 'Localization' <br> <strong>For this example we need your main Language</strong>."
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
                    element: "#EditDocumentController > div > div:nth-child(1) > ul > li:first-child",
                    orphan: true,
                    placement: "bottom",
                    title: "Tabs",
                    prev: -1, //Disable prev Button because iframe
                    content: "The Consent Modal is what the user sees when he visits the website for the first time. <br> In this section you can edit the <strong>consent text</strong> and <strong>layout</strong>."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(1)",
                    orphan: true,
                    placement: "bottom",
                    title: "Title and Description",
                    content: "Title and Description should be self-explanatory. <br> Let's take a look at the <strong>Buttons and Layout</strong> settings."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(6)",
                    orphan: true,
                    placement: "top",
                    title: "Buttons",
                    content: "The user can click to accept or reject the cookies. <br> You can change the text and the color of the buttons in this Section for the Consent Modal.<br> We have a look at the Settings modal later."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(15)",
                    orphan: true,
                    placement: "top",
                    title: "Layout",
                    content: "Change between <strong>Cloud, Box</strong> or <strong>Bar</strong> Layout. <br> The Layout is the position of the Consent Modal on the website."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(2)",
                    orphan: true,
                    placement: "top",
                    title: "Settings Modal",
                    reflex: true,
                    next: -1, //Disable next Button because User should click on the Tab
                    onNext: function (tour) {
                        //Jump to next step
                        tour.goTo(9);
                    },
                    content: "The Settings Modal is what the user sees when he clicks on the <strong>Settings Icon</strong> in the Consent Modal. <br> In this section you can edit the <strong>settings text</strong> and <strong>layout</strong>."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active.show > fieldset > div > div:nth-child(1)",
                    orphan: true,
                    reflex: true,
                    title: "Save and close",
                    content: "more todo... sorry come back later."
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


