
define(['jquery','TourFunctions'], function ($,TF) {
    // Instance the tour
    return  {
          onStart: function() {
              sessionStorage.setItem("currentTour", "CategoryTour");
              return true
          },
          onEnd: function() {
              sessionStorage.setItem("currentTour", "");
              return true
          },
          debug: true,
          backdrop: true,
          storage: window.sessionStorage,
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
                  content: "In this Example we configure the <strong>Youtube Provider</strong>,<br> which is part of the Category <strong>External Media</strong>."
              },
              {
                  path: $("#externalmedia").find(".settings-item-head-right a").attr("href"),
                // element: TF.selectFormEngineInput("cookie_services",".t3js-formengine-field-group .form-multigroup-item:nth-child(2) .form-wizards-element"),
                  element:  TF.selectFormEngineInput("cookie_services",".form-group",false),
                  placement: "top",
                  orphan: true,
                  title: "Add Cookie Services",
                  content: "Search for the Youtube provider and add it to the list of Cookie Services"
              },
              {
               //   element: TF.selectFormEngineInput("cookie_services",".t3js-formengine-field-group .form-multigroup-item:nth-child(1) .form-wizards-element"),
                  element:  TF.selectFormEngineInput("cookie_services",".form-group",false),
                  orphan: true,
                  placement: "top",
                  title: "Youtube Provider added",
                  content: "Done, the Youtube Provider is now part of the Category 'External Media'."
              },
              {
                  element: "body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button",
                  orphan: true,
                  reflex: true,
                  title: "Save and close",
                  content: "Done, save and close the configuration."
              },
       ]
    };


});


