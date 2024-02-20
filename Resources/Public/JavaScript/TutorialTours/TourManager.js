import $ from "jquery";
import Tour from '@codingfreaks/cf-cookiemanager/thirdparty/bootstrap-tour.js';
import DocumentService from '@typo3/core/document-service.js';

async function loadTour(tourName) {
    const tourModule = await import(`@codingfreaks/cf-cookiemanager/TutorialTours/${tourName}.js`);
    return new Tour(tourModule.default);
}

DocumentService.ready().then(async () => {
    let tourMap = {};

    $(".startTour").click(async function (e) {
        let selectedTourName = $(this).data("tour");
        if (!tourMap[selectedTourName]) {
            tourMap[selectedTourName] = { run: await loadTour(selectedTourName) };
        }
        tourMap[selectedTourName].run.init();
        tourMap[selectedTourName].run.restart();
    });

    if (typeof sessionStorage.getItem("currentTour") !== "undefined" && sessionStorage.getItem("currentTour") !== null && sessionStorage.getItem("currentTour") !== "") {
        let currentTour = sessionStorage.getItem("currentTour");
        setTimeout(async function () {
            if (!tourMap[currentTour]) {
                tourMap[currentTour] = { run: await loadTour(currentTour) };
            }
            tourMap[currentTour].run.init();
            tourMap[currentTour].run.start();
        }, 1000);
    }

    $("body").delegate(".force-end-tour","click", function (e) {
        let currentTour = sessionStorage.getItem("currentTour");
        tourMap[currentTour].run.end();
        sessionStorage.setItem("currentTour", "");
    });
});