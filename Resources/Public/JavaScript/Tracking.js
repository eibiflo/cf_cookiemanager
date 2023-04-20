
function sendData(url,data) {
    const XHR = new XMLHttpRequest();
    const urlEncodedDataPairs = [];
    // Turn the data object into an array of URL-encoded key/value pairs.
    for (const [name, value] of Object.entries(data)) {
        urlEncodedDataPairs.push(`${encodeURIComponent(name)}=${encodeURIComponent(value)}`);
    }
    // Combine the pairs into a single string and replace all %-encoded spaces to
    // the '+' character; matches the behavior of browser form submissions.
    const urlEncodedData = urlEncodedDataPairs.join('&').replace(/%20/g, '+');


    /* Define what happens in case of an error
    XHR.addEventListener('error', (event) => {
        callback(new Error("Request failed with status " + this.status));
    });
    XHR.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
               callback(null, this.responseText);
            } else {
                callback(new Error("Request failed with status " + this.status));
            }
        }
    };
    */

    // Set up our request
    XHR.open('POST', url);
    // Add the required HTTP header for form data POST requests
    XHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    // Finally, send our data.
    XHR.send(urlEncodedData);
}

var url = document.getElementById("cf-cookiemanager-tracker").attributes["data-url"].value;
const data = {
    languageCode: navigator.language,
    referrer: document.referrer,
    navigator: navigator.webdriver,
    consent_type:  user_preferences.accept_type,
};

sendData(url,data)