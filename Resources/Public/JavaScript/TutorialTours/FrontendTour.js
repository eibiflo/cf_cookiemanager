import $ from 'jquery';
import { lll } from '@typo3/core/lit-helper.js';
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
            title: lll('tour.frontend.s1.title'),
            placement: "bottom",
            content: lll('tour.frontend.s1.content')
        },
        {
            element: "div.module-body.t3js-module-body.cf_manager > div.typo3-TCEforms > div > ul > li:nth-child(3)",
            orphan: true,
            reflex: true,
            title: lll('tour.frontend.s2.title'),
            prev: -1, //Disable prev Button because User should click on the Tab
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep() + 1);

            },
            content: lll('tour.frontend.s2.content'),
        },
        {
            //  element: "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend .recordlist-heading-row:first-child .cfLanguageHook:first-child,#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend .cfLanguageHook",
            //  element: '[data-multi-record-selection-identifier="t3-table-tx_cfcookiemanager_domain_model_cookiefrontend"] .cfLanguageHook',
            element: "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend",
            orphan: true,
            title: lll('tour.frontend.s3.title'),
            content: lll('tour.frontend.s3.content'),
        },

        {
            element: "#recordlist-tx_cfcookiemanager_domain_model_cookiefrontend > div > table > tbody > tr:nth-child(1) > td.col-control.nowrap > div:nth-child(1) > a",
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s4.title'),
            reflex: true,
            next: -1, //Disable next Button because User should click on the Edit Icon
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(4);
            },
            content: lll('tour.frontend.s4.content')
        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:first-child",
            orphan: true,
            placement: "bottom",
            title: lll('tour.frontend.s5.title'),
            reflex: true,
            next: -1, //Disable next Button because User should click on the Edit Icon
            prev: -1, //Disable prev Button because iframe
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(5);
            },
            content: lll('tour.frontend.s5.content')
        },
        {
            element: selectFormEngineInput("title_consent_modal",".form-group",true),
            orphan: true,
            placement: "bottom",
            title: lll('tour.frontend.s6.title'),
            content: lll('tour.frontend.s6.content')
        },
        {
            element: selectFormEngineInput("primary_btn_text_consent_modal",".form-group",true),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s7.title'),
            content: lll('tour.frontend.s7.content')
        },
        {
            element: selectFormEngineInput("layout_consent_modal",".form-group",true),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s8.title'),
            content: lll('tour.frontend.s8.content')
        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(2)",
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s9.title'),
            reflex: true,
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(9);
            },
            content: lll('tour.frontend.s9.content')
        },
        {
            element: selectFormEngineInput("title_settings",".form-group",true),
            orphan: true,
            title: lll('tour.frontend.s10.title'),
            content: lll('tour.frontend.s10.content')
        },
        {
            element: selectFormEngineInput("accept_all_btn_settings",".form-group",true),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s11.title'),
            content: lll('tour.frontend.s11.content')
        },
        {
            element: selectFormEngineInput("blocks_description",".form-group",false),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s12.title'),
            content: lll('tour.frontend.s12.content'),
        },
        {
            element: selectFormEngineInput("layout_settings",".form-group",true),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s13.title'),
            content: lll('tour.frontend.s13.content')
        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(3)",
            orphan: true,
            reflex: true,
            placement: "top",
            title: lll('tour.frontend.s14.title'),
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep() + 1);
            },
            content: lll('tour.frontend.s14.content'),

        },
        {
            element: selectFormEngineInput("custom_button_html",".form-group",false),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s15.title'),
            content: lll('tour.frontend.s15.content'),

        },
        {
            element: selectFormEngineInput("in_line_execution",".form-group",false),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s16.title'),
            content: lll('tour.frontend.s16.content'),

        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(4)",
            orphan: true,
            reflex: true,
            placement: "top",
            title: lll('tour.frontend.s17.title'),
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep() + 1);
            },
            content: lll('tour.frontend.s17.content'),
        },
        {
            element: selectFormEngineInput("name",".form-group",false),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s18.title'),
            content: lll('tour.frontend.s18.content'),

        },
        {
            element: selectFormEngineInput("impress_text",".form-group",false),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s19.title'),
            content: lll('tour.frontend.s19.content'),

        },

        {
            element: selectFormEngineInput("identifier",".form-group",false),
            orphan: true,
            placement: "top",
            title: lll('tour.frontend.s20.title'),
            content: lll('tour.frontend.s20.content'),

        },
        {
            element: "body > div.module > div.module-docheader button[name=\"_savedok\"]",
            orphan: true,
            reflex: true,
            title: lll('tour.frontend.s21.title'),
            content: lll('tour.frontend.s21.content'),
        },
    ]
};

export default tour;
