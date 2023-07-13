define(['jquery','bootstrapTour'], function ($) {
    // Instance the tour
    if (typeof Tour !== 'undefined') {
        var ServiceTour = new Tour({
            onStart: function() {
                sessionStorage.setItem("currentTour", "ServiceTour");
                return true
            },
            onEnd: function() {
                sessionStorage.setItem("currentTour", "");
                return true
            },
            debug: false,
            backdrop: true,
            storage: window.localStorage,
            steps: [
                {
                    //path: "/",
                    element: "[aria-controls=\"DTM-services-1\"]",
                    title: "Cookie Services Tour",
                    placement: "bottom",
                    content: "In this example we crate a new Service from scratch."
                },
                {
                    path: $("#createNewServiceLink").attr("href"),
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(1)",
                    orphan: true,
                    placement: "right",
                    title: "Create new Service",
                    content: "In the Background a empty new Service was created. <br><br> We need to set some basic information. Such as the name, the identifier and the description. <br>In this example we use OpenStreetmap from the documentation example: <a href=\"https://docs.typo3.org/p/codingfreaks/cf-cookiemanager/main/en-us/Developer/CustomServices/Index.html#leaflet-openstreetmap\">Here -></a>. <br> Type the name \"My OpenStreetmap Service\" and press Next."
                },
                {

                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(2)",
                    placement: "right",
                    orphan: true,
                    title: "Service identifier",
                    content: "The Identifier is used to identify the Service in the Frontend Javascript, and in your Typoscript configuration. <br> Type the identifier \"my_openstreetmap_service\" and press Next."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(4)",
                    placement: "right",
                    orphan: true,
                    title: "Provider",
                    content: "The Provider field is used to compare the original URL with the URL from the embedded iframe or script.<br><br>You can separate different providers by using a comma or simply use the domain name like .panomax.com to match all subdomains. <br> We need to add the domain name \"openstreetmap.org\" and press Next."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(5)",
                    placement: "right",
                    orphan: true,
                    title: "Category Suggestion",
                    content: "This is used for the Classification from the API, you can add a Category Identifier like \"externalmedia\" if its empty, the you can find the service in Unknown in the Backend."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(8)",
                    placement: "right",
                    orphan: true,
                    title: "DSGVO Link",
                    content: "The DSGVO Link is used to link to the privacy policy of the provider. <br> We need to add the link \"https://wiki.osmfoundation.org/wiki/Privacy_Policy\" and press Next."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(10)",
                    placement: "top",
                    orphan: true,
                    title: "Description",
                    content: "Add a description for the Frontend Settings modal."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > div > div.tab-pane.active > fieldset > div > div:nth-child(12)",
                    placement: "top",
                    orphan: true,
                    title: "Cookies",
                    content: "Here you can add Cookies that are set by the Service. This is used for the Cookie List in the Frontend, and the Javascript Cookie handling with autoclear. For more information use the Cookie Tour. Press Next."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(2) > a",
                    placement: "bottom",
                    orphan: true,
                    title: "Iframe manager",
                    content: "Here you can set the Iframe manager Texts. For more information have a look at the Documentation. <br> Press Next."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(3) > a",
                    placement: "bottom",
                    orphan: true,
                    title: "Script manager",
                    content: "Here you can set the Script for Optin/OptOut. <br> <br> Here you can Add some External Scripts, and Variable providers. For more information have a look at the Documentation. <br> Press Next."
                },
                {
                    element: "body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button",
                    orphan: true,
                    reflex: true,
                    title: "Save and close",
                    content: "Done, save and close the configuration."
                },
            ]});

        // Initialize the tour
        // Initialize the tour
        if(sessionStorage.getItem("currentTour") === "ServiceTour"){
            ServiceTour.init();
        }

        $("#start-service-tour").click(function(e){
        //    CategoriesTour.start();
            ServiceTour.init();
            ServiceTour.restart();
        });
    };

});


