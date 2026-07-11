import $ from 'jquery';
import { lll } from '@typo3/core/lit-helper.js';
import { selectFormEngineInput } from '@codingfreaks/cf-cookiemanager/TutorialTours/TourFunctions.js';

// Instance the tour
const tour = {
    onStart: function() {
        sessionStorage.setItem("currentTour", "ServiceTour");
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
            element: "[aria-controls=\"DTM-services-1\"]",
            title: lll('tour.service.s1.title'),
            placement: "bottom",
            content: lll('tour.service.s1.content')
        },
        {
            path: $("#createNewServiceLink").attr("href"),
            element: selectFormEngineInput("name",".form-group",false),
            orphan: true,
            placement: "right",
            title: lll('tour.service.s2.title'),
            content: lll('tour.service.s2.content')
        },

        {


            element: selectFormEngineInput("identifier",".form-group",false),
            placement: "right",
            orphan: true,
            title: lll('tour.service.s3.title'),
            content: lll('tour.service.s3.content')
        },
        {
            element: selectFormEngineInput("provider",".form-group",false),
            placement: "right",
            orphan: true,
            title: lll('tour.service.s4.title'),
            content: lll('tour.service.s4.content')
        },
        {
            element: selectFormEngineInput("category_suggestion",".form-group",false),
            placement: "right",
            orphan: true,
            title: lll('tour.service.s5.title'),
            content: lll('tour.service.s5.content')
        },
        {
            element: selectFormEngineInput("dsgvo_link",".form-group",false),
            placement: "right",
            orphan: true,
            title: lll('tour.service.s6.title'),
            content: lll('tour.service.s6.content')
        },
        {
            element: selectFormEngineInput("description",".form-group",false),
            placement: "top",
            orphan: true,
            title: lll('tour.service.s7.title'),
            content: lll('tour.service.s7.content')
        },
        {
            element: selectFormEngineInput("cookie",".form-group",false),
            placement: "top",
            orphan: true,
            title: lll('tour.service.s8.title'),
            content: lll('tour.service.s8.content')
        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(2)",
            placement: "top",
            orphan: true,
            title: lll('tour.service.s9.title'),
            reflex: true,
           // delay: 1000, //Wait for the Editor is loaded
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep()	+1);
            },
            content: lll('tour.service.s9.content')
        },
        {
            element: selectFormEngineInput("iframe_embed_url",".form-group",false),
            placement: "bottom",
            orphan: true,
            //delay: 1000, //Wait for the Editor is loaded
            title: lll('tour.service.s10.title'),

            content: lll('tour.service.s10.content')
        },
        {
            element:  selectFormEngineInput("iframe_thumbnail_url",".form-group",false),
            placement: "top",
            orphan: true,
            title: lll('tour.service.s11.title'),
            content: lll('tour.service.s11.content')
        },
        {
            element:  selectFormEngineInput("iframe_notice",".form-group",false),
            placement: "top",
            orphan: true,
            title: lll('tour.service.s12.title'),
            content: lll('tour.service.s12.content')
        },
        {
            element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(3)",
            placement: "bottom",
            orphan: true,
            title: lll('tour.service.s13.title'),
            reflex: true,
            next: -1, //Disable next Button because User should click on the Tab
            onNext: function (tour) {
                //Jump to next step
                tour.goTo(tour.getCurrentStep() + 1);
            },
            content: lll('tour.service.s13.content')
        },
        {
            element:  selectFormEngineInput("variable_priovider",".form-group",false),
            placement: "bottom",
            delay: 1000, //Wait for the Editor is loaded
            orphan: true,
            title: lll('tour.service.s14.title'),
            content: lll('tour.service.s14.content')
        },
        {
            element: "body > div.module > div.module-docheader button[name=\"_savedok\"]",
            orphan: true,
            reflex: true,
            title: lll('tour.service.s15.title'),
            content: lll('tour.service.s15.content')
        },
    ]
};

export default tour;
