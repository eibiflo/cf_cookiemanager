define(['jquery','TourFunctions'], function ($, TF) {
    // Instance the tour
    console.log( TF.selectFormEngineInput("variable_priovider",".form-group",true));
    return {
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
            storage: window.sessionStorage,
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
                    element: TF.selectFormEngineInput("name",".form-group",false),
                    orphan: true,
                    placement: "right",
                    title: "Create new Service",
                    content: "In the Background a empty new Service was created. <br><br> We need to set some basic information. Such as the name, the identifier and the description. <br>In this example we use OpenStreetmap from the documentation example: <a href=\"https://docs.typo3.org/p/codingfreaks/cf-cookiemanager/main/en-us/Developer/CustomServices/Index.html#leaflet-openstreetmap\">Here -></a>. <br> Type the name \"My OpenStreetmap Service\" and press Next."
                },
                {


                    element: TF.selectFormEngineInput("identifier",".form-group",false),
                    placement: "right",
                    orphan: true,
                    title: "Service identifier",
                    content: "The Identifier is used to identify the Service in the Frontend Javascript, and in your Typoscript configuration. <br> Type the identifier \"my_openstreetmap_service\" and press Next."
                },
                {
                    element: TF.selectFormEngineInput("provider",".form-group",false),
                    placement: "right",
                    orphan: true,
                    title: "Provider",
                    content: "The Provider field is used to compare the original URL with the URL from the embedded iframe or script.<br><br>You can separate different providers by using a comma or simply use the domain name like .panomax.com to match all subdomains. <br> We need to add the domain name \"openstreetmap.org\" and press Next."
                },
                {
                    element: TF.selectFormEngineInput("category_suggestion",".form-group",false),
                    placement: "right",
                    orphan: true,
                    title: "Category Suggestion",
                    content: "This is used for the Classification from the API, you can add a Category Identifier like \"externalmedia\" if its empty, the you can find the service in Unknown in the Backend."
                },
                {
                    element: TF.selectFormEngineInput("dsgvo_link",".form-group",false),
                    placement: "right",
                    orphan: true,
                    title: "DSGVO Link",
                    content: "The DSGVO Link is used to link to the privacy policy of the provider. <br> We need to add the link \"https://wiki.osmfoundation.org/wiki/Privacy_Policy\" and press Next."
                },
                {
                    element: TF.selectFormEngineInput("description",".form-group",false),
                    placement: "top",
                    orphan: true,
                    title: "Description",
                    content: "Add a description for the Frontend Settings modal."
                },
                {
                    element: TF.selectFormEngineInput("cookie",".form-group",false),
                    placement: "top",
                    orphan: true,
                    title: "Cookies",
                    content: "Here you can add Cookies that are set by the Service. This is used for the Cookie List in the Frontend, and the Javascript Cookie handling with autoclear. For more information use the Cookie Tour. Press Next."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(2) > a",
                    placement: "top",
                    orphan: true,
                    title: "Iframe manager",
                    reflex: true,
                    next: -1, //Disable next Button because User should click on the Tab
                    onNext: function (tour) {
                        //Jump to next step
                        tour.goTo(9);
                    },
                    content: "Here you can set the Iframe manager Texts. For more information have a look at the Documentation. <br> Press Next."
                },
                {
                    element: TF.selectFormEngineInput("iframe_embed_url",".form-group",false),
                    placement: "bottom",
                    orphan: true,
                    title: "Embed URL",
                    content: "Here you can add a JavaScript function, that is used to embed the Iframe on consent accept. <br> Default iframes are managed by the iFrame manager self, you only need this for special embeds. <br> Press Next."
                },
                {
                    element:  TF.selectFormEngineInput("iframe_thumbnail_url",".form-group",false),
                    placement: "top",
                    orphan: true,
                    title: "Thumbnail manager",
                    content: "Here you can place a Javascript Function to fetch a Thumbnail for the Iframe, in a GDPR conform way! <a href='https://docs.typo3.org/p/codingfreaks/cf-cookiemanager/main/en-us/Configuration/CookieServices/Index.html#advanced-iframe-configuration'>Documentation</a> <br> Press Next."
                },
                {
                    element:  TF.selectFormEngineInput("iframe_notice",".form-group",false),
                    placement: "top",
                    orphan: true,
                    title: "iFrame Notice",
                    content: "This is the Text, displayed if the iframe or script was blocked. <br> Press Next."
                },
                {
                    element: "#EditDocumentController > div > div:nth-child(1) > ul > li:nth-child(3) > a",
                    placement: "bottom",
                    orphan: true,
                    title: "Script manager",
                    reflex: true,
                    next: -1, //Disable next Button because User should click on the Tab
                    onNext: function (tour) {
                        //Jump to next step
                       // tour.next();
                    },
                    content: "Execute Javascript on consent actions, for more information have a look at the Documentation. <br> Click on the Tab."
                },
                {
                    element:  TF.selectFormEngineInput("variable_priovider",".form-group",false),
                    placement: "bottom",
                    orphan: true,
                    title: "Variable Provider",
                    content: "Variable providers detect defined variables in the fields opt_in_code opt_out_code fallback_code.<br>A variable is declared with the [## and closed with ##]. In the Google Analytics service you find this example: [##googleTagManagerID##]"
                },
                {
                    element: "body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button",
                    orphan: true,
                    reflex: true,
                    title: "Save and close",
                    content: "Done, save and close the configuration."
                },
            ]};
});


