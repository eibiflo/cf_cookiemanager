import $ from 'jquery';
import { selectFormEngineInput } from '@codingfreaks/cf-cookiemanager/TutorialTours/TourFunctions.js';

// Instance the tour
const tour = {
    onStart: function() {
        sessionStorage.setItem("currentTour", "FrontendTour");
        return true;
    },
    onEnd: function() {
        sessionStorage.setItem("currentTour", "");
        return true;
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
            element: "div.module-body.t3js-module-body.cf_manager > div.typo3-TCEforms > div > ul > li:nth-child(3)",
            orphan: true,
            reflex: true,
            title: "Frontend Settings Tour",
            prev: -1, //Disable prev Button because User should click on the Tab
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep() + 1);

            },
            content: "<strong>Click on the Tab 'Frontend Settings'</strong> , <br>to navigate to the Frontend Configuration.",
        },
        {
            //  element: "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend .recordlist-heading-row:first-child .cfLanguageHook:first-child,#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend .cfLanguageHook",
            //  element: '[data-multi-record-selection-identifier="t3-table-tx_cfcookiemanager_domain_model_cookiefrontend"] .cfLanguageHook',
            element: "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend",
            orphan: true,
            title: "Languages",
            content: "In the Frontend selection you can filter trough Frontend-languages to edit.<br>  Press next to continue.",
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
            content: "Click on the 'Edit' Icon to edit the frontend setting."
        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:first-child",
            orphan: true,
            placement: "bottom",
            title: "Tabs",
            reflex: true,
            next: -1, //Disable next Button because User should click on the Edit Icon
            prev: -1, //Disable prev Button because iframe
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(5);
            },
            content: "The Consent Modal is what the user sees when he visits the website for the first time. <br> In this section you can edit the <strong>consent text</strong> and <strong>layout</strong>."
        },
        {
            element: selectFormEngineInput("title_consent_modal",".form-group",true),
            orphan: true,
            placement: "bottom",
            title: "Title and Description",
            content: "Title and Description are displayed in the <strong>Consent Modal</strong> <br> Let's take a look at the <strong>Buttons and Layout</strong> settings."
        },
        {
            element: selectFormEngineInput("primary_btn_text_consent_modal",".form-group",true),
            orphan: true,
            placement: "top",
            title: "Buttons",
            content: "The user can click to accept or reject the cookies. <br> You can change the <strong>text</strong> and the <strong>button rule</strong>.<br> We have a look at the Settings modal later."
        },
        {
            element: selectFormEngineInput("layout_consent_modal",".form-group",true),
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
            element: selectFormEngineInput("title_settings",".form-group",true),
            orphan: true,
            title: "Title",
            content: "Title of the Settings modal."
        },
        {
            element: selectFormEngineInput("accept_all_btn_settings",".form-group",true),
            orphan: true,
            placement: "top",
            title: "Buttons",
            content: "Here you can Translate the Button labels."
        },
        {
            element: selectFormEngineInput("blocks_description",".form-group",false),
            orphan: true,
            placement: "top",
            title: "Layout",
            content: "Change  the text above the Buttons",
        },
        {
            element: selectFormEngineInput("layout_settings",".form-group",true),
            orphan: true,
            placement: "top",
            title: "Layout",
            content: "Change between <strong>Cloud, Box</strong> or <strong>Bar</strong> Layout. <br> The Layout is the position of the Consent Modal on the website."
        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(3)",
            orphan: true,
            reflex: true,
            placement: "top",
            title: "Layout",
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep() + 1);
            },
            content: "Click on the 'Customize' Tab to continue.",

        },
        {
            element: selectFormEngineInput("custom_button_html",".form-group",false),
            orphan: true,
            placement: "top",
            title: "Customize",
            content: "Here you can insert a Custom HTML button for the right side of the Website, or disable it, by enable the custom-button and leave the field empty.",

        },
        {
            element: selectFormEngineInput("in_line_execution",".form-group",false),
            orphan: true,
            placement: "top",
            title: "Customize",
            content: "The Configuration of this cookiemanager is dumpes into a .js file in the Typo3 Temp folder. <br> If you want to execute the Configuration inline, enable this option. <br> This is useful if you want to use the cookiemanager in a Single Page Application.",

        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(4)",
            orphan: true,
            reflex: true,
            placement: "top",
            title: "Global Settings",
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep() + 1);
            },
            content: "Click on the 'Global Settings' Tab to continue.",
        },
        {
            element: selectFormEngineInput("name",".form-group",false),
            orphan: true,
            placement: "top",
            title: "Name",
            content: "Currently only used in the Backend as display name.",

        },
        {
            element: selectFormEngineInput("impress_text",".form-group",false),
            orphan: true,
            placement: "top",
            title: "Impress",
            content: "Here you can insert text for the impress & data policy buttons, link can be set on the right side.",

        },

        {
            element: selectFormEngineInput("identifier",".form-group",false),
            orphan: true,
            placement: "top",
            title: "Identifier",
            content: "The Identifier is used to identify the Language-Mapping between your Typo3 and the Coding-Freaks cookie API <br> <strong>Do not change, if you dont know what you are doing.</strong>",

        },
        {
            element: "body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button",
            orphan: true,
            reflex: true,
            title: "Save and close",
            content: "Hope you enjoyed the Tour, <br> Click on the 'Save and Close' Button to save your changes.",
        },
    ]
};

export default tour;
