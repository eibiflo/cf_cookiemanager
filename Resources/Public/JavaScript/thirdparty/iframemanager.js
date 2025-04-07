/*!
 * iframemanager v1.1.0
 * Author Orest Bida
 * Released under the MIT License
 * Modified by Codingfreaks for Typo3 CMS integration
 */
(function () {
    'use strict';

    /**
     * @typedef {Object} IframeObj
     * @property {string} _id
     * @property {string} _title
     * @property {string} _thumbnail
     * @property {string} _params
     * @property {boolean} _thumbnailPreload
     * @property {boolean} _autoscale
     * @property {HTMLDivElement} _div
     * @property {HTMLIFrameElement} _iframe
     * @property {HTMLDivElement} _backgroundDiv
     * @property {boolean} _hasIframe
     * @property {boolean} _hasNotice
     * @property {boolean} _showNotice
     * @property {Object.<string, string>} _iframeAttributes
     */

    /**
     * @typedef {HTMLIFrameElement} IframeProp
     */

    /**
     * @typedef {Object} CookieStructure
     * @property {string} name
     * @property {string} path
     * @property {string} domain
     * @property {string} sameSite
     */

    /**
     * @typedef {Object} Language
     * @property {string} notice
     * @property {string} loadBtn
     * @property {string} loadAllBtn
     */

    /**
     * @typedef {Object} Service
     * @property {string} embedUrl
     * @property {IframeProp} [iframe]
     * @property {CookieStructure} cookie
     * @property {Language} languages
     * @property {Function} [onAccept]
     * @property {Function} [onReject]
     */

    var

        /**
         * @type {Object.<string, IframeObj[]>}
         */
        iframeDivs = {},

        /**
         * @type {string[]}
         */
        preconnects = [],

        /**
         * @type {string[]}
         */
        preloads = [],

        stopObserver = false,
        currLang = '',

        /**
         * @type {Object.<string, Service>}
         */
        services = null,

        /**
         * @type {string[]}
         */
        serviceNames = [],

        doc = document,

        /**
         * Prevent direct use of the following
         * props. in the `iframe` element to avoid
         * potential issues
         */
        disallowedProps = ['onload', 'onerror', 'src'];

    /**
     * @param {HTMLDivElement} div
     * @returns {IframeObj}
     */
    function getVideoProp(div) {

        var dataset = div.dataset;
        var attrs = {};

        /**
         * Get all "data-iframe-* attributes
         */
        for (var prop in dataset) {
            if (prop.lastIndexOf('iframe') === 0) {
                attrs[prop.slice(6).toLowerCase()] = dataset[prop];
            }
        }

        return {
            _id: dataset.id,
            _title: dataset.title,
            _thumbnail: dataset.thumbnail,
            _params: dataset.params,
            _thumbnailPreload: 'thumbnailpreload' in dataset,
            _autoscale: 'autoscale' in dataset,
            _div: div,
            _backgroundDiv: null,
            _hasIframe: false,
            _hasNotice: false,
            _showNotice: true,
            _iframeAttributes: attrs
        };
    };

    /**
     * Lazy load all thumbnails of the iframes relative to specified service
     * @param {string} serviceName
     * @param {string} thumbnailUrl
     */
    function lazyLoadThumnails(serviceName, thumbnailUrl) {

        var videos = iframeDivs[serviceName];
        if ("IntersectionObserver" in window) {
            var thumbnailObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                         let thumbUrl = thumbnailUrl;
                         // true index of the video in the array relative to current service
                         if (typeof thumbUrl === 'string') {
                            const videoUrl = videos[entry.target.dataset.index]._id;
                            if(typeof videoUrl !== 'undefined' && videoUrl !== undefined) {
                                const params = `cf_width=${entry.boundingClientRect.width}&cf_height=${entry.boundingClientRect.height}`;
                                const encodedParams = params;
                                thumbUrl = thumbUrl.replace('##CF-BUILDTHUMBNAIL##', btoa(videoUrl.includes('?') ? `${videoUrl}&${encodedParams}` : `${videoUrl}?${encodedParams}`));
                            }
                         }
                         loadThumbnail(thumbUrl, videos[entry.target.dataset.index]);
                         thumbnailObserver.unobserve(entry.target);
                    }
                });
            });

            videos.forEach(function (video) {
                thumbnailObserver.observe(video._div);
            });
        } else {
            // Fallback for old browsers
            for (var i = 0; i < videos.length; i++) {
                loadThumbnail(thumbnailUrl, videos[i]);
            }
        }
    };


    // Function to fetch image and return a promise
    function fetchImage(url) {
        return fetch(url)
            .then(response => response.blob())
            .then(blob => URL.createObjectURL(blob));
    }


    /**
     * Set image as background
     * @param {string} url
     * @param {IframeObj} video
     */
    function loadThumbnail(url, video) {
        //ADDED BY CodingFreaks &&  video._thumbnail.length >= 1
        // Set custom thumbnail if provided
        if (typeof video._thumbnail === 'string' && video._thumbnail.length >= 1) {
            video._thumbnailPreload && preloadThumbnail(video._thumbnail);
            video._thumbnail !== '' &&  loadBackgroundImage(video._thumbnail,video);
        } else {
            if (typeof url === "function") {
                url(video._id, function (src) {
                    preconnect(src);
                    video._thumbnailPreload && preloadThumbnail(src);
                    loadBackgroundImage(src,video);
                });

            } else if (typeof url === 'string') {
                var src = url.replace('{data-id}', video._id);
                preconnect(src);
                video._thumbnailPreload && preloadThumbnail(src);
                fetchImage(src).then(imageUrl => loadBackgroundImage(imageUrl, video));
            }
        }

        async function loadBackgroundImage(src, video) {
            video._backgroundDiv.style.backgroundImage = "url('" + src + "')";

            const img = new Image();
            img.src = src;

            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
            });

            video._backgroundDiv.classList.add('loaded');
        }
    };


    /**
     * Helper function which creates an HTMLElement object based on 'type' and returns it.
     * @param {string} type
     * @returns {HTMLElement}
     */
    var _createNode = function (type) {
        var el = document.createElement(type);
        if (type === 'button') {
            el.setAttribute('type', type);
        }
        return el;
    }


    /**
     * Create iframe and append it into the specified div
     * @param {IframeObj} video
     * @param {Service} service
     */
    function createIframe(video, service) {

        // Create iframe only if doesn't alredy have one
        if (video._hasIframe)
            return;

        video._hasIframe = true;

        if (typeof service.onAccept === 'function') {

            // Let the onAccept method create the iframe
            service.onAccept(video._div, function (iframe) {
                //console.log("iframe_created!", iframe);
                video._iframe = iframe;
                video._hasIframe = true;
                video._div.classList.add('c-h-b');

                // if(video._autoscale){
                //     var t;
                //     video._div.style.minHeight = iframe.style.height;
                //     window.addEventListener('resize', function(){
                //         clearTimeout(t);
                //         t = setTimeout(function(){
                //             video._div.style.minHeight = iframe.style.height;
                //         }, 200);
                //     }, {passive: true});
                // }

            });

            return;
        }

        video._iframe = createNode('iframe');
        var iframeParams = video._params || (service.iframe && service.iframe.params);

        // Replace data-id with valid resource id
        var embedUrl = service.embedUrl || '';




        /** CF HOOK START */

        var scripts = document.querySelectorAll('script[data-service]');

        var _loadScripts = function (scripts, index) {
            if (index < scripts.length) {

                var curr_script = scripts[index];
                //var curr_script_category = curr_script.getAttribute("script[data-service]");
                var curr_script_category = curr_script.getAttribute("data-service");

                if(cc.allowedCategory(curr_script_category) !== false){
                    console.log(curr_script_category);
                    return;
                }

                if(service.cookie.name !== curr_script_category){
                    return;
                }


                //     if(_inArray(accepted_categories, curr_script_category) > -1){

                curr_script.type = 'text/javascript';
                curr_script.removeAttribute("script[data-service]");

                // get current script data-src
                var src = curr_script.getAttribute('data-src');

                // some scripts (like ga) might throw warning if data-src is present
                src && curr_script.removeAttribute('data-src');

                // create fresh script (with the same code)
                var fresh_script = _createNode('script');
                fresh_script.textContent = curr_script.innerHTML;

                // Copy attributes over to the new "revived" script
                (function (destination, source) {
                    var attributes = source.attributes;
                    var len = attributes.length;
                    for (var i = 0; i < len; i++) {
                        var attr_name = attributes[i].nodeName;
                        destination.setAttribute(attr_name, source[attr_name] || source.getAttribute(attr_name));
                    }
                })(fresh_script, curr_script);

                // set src (if data-src found)
                src ? (fresh_script.src = src) : (src = curr_script.src);

                // if script has "src" attribute
                // try loading it sequentially
                if (src) {
                    // load script sequentially => the next script will not be loaded
                    // until the current's script onload event triggers
                    if (fresh_script.readyState) {  // only required for IE <9
                        fresh_script.onreadystatechange = function () {
                            if (fresh_script.readyState === "loaded" || fresh_script.readyState === "complete") {
                                fresh_script.onreadystatechange = null;
                                _loadScripts(scripts, ++index);
                            }
                        };
                    } else {  // others
                        fresh_script.onload = function () {
                            fresh_script.onload = null;
                            _loadScripts(scripts, ++index);
                        };
                    }
                }

                // Replace current "sleeping" script with the new "revived" one
                curr_script.parentNode.replaceChild(fresh_script, curr_script);


                if (src) return;
            }

            if (index < scripts.length) {
                _loadScripts(scripts, ++index);
            }
        }
        //}

        _loadScripts(scripts, 0);
       /** CF HOOK END */


        if (typeof embedUrl === "function") {
            embedUrl(video._id, function (src) {

            });
        } else if (typeof embedUrl === 'string') {

            var src = embedUrl.replace('{data-id}', video._id);
            video._title && (video._iframe.title = video._title);
            //Can be undefined if the service doesn't use a Iframe, like leaflet only uses Script tags so we dont need a iframe for that
            if( src === 'undefined'){
                return false;
            }

            // Add parameters to src
            if (iframeParams) {
                if (iframeParams.substring(0, 3) === 'ap:') {
                    src += iframeParams.substring(3);
                } else {
                    src += '?' + iframeParams;
                }
            }


            var iframeProps = service.iframe;

            // When iframe is loaded => hide background image
            video._iframe.onload = function () {
                video._div.classList.add('c-h-b');
                video._iframe.onload = undefined;

                iframeProps
                && typeof iframeProps.onload === 'function'
                && iframeProps.onload(video._id, video._iframe);
            };

            /**
             * Add global internal attributes
             */
            for (var key in iframeProps) {
                setAttribute(video._iframe, key, iframeProps[key])
            }

            /**
             * Add all data-attr-* attributes (iframe specific)
             */
            for (var attr in video._iframeAttributes) {
                setAttribute(video._iframe, attr, video._iframeAttributes[attr])
            }

            video._iframe.src = src;

            appendChild(video._div, video._iframe);

        }


    };

    /**
     * @param {HTMLElement} el
     * @param {string} attrKey
     * @param {string} attrValue
     */
    function setAttribute(el, attrKey, attrValue) {
        if (!disallowedProps.includes(attrKey))
            el.setAttribute(attrKey, attrValue);
    }

    /**
     * Remove iframe HTMLElement from div
     * @param {IframeObj} video
     */
    var removeIframe = function (video) {
        if(video._div.innerHTML.length >= 1 ){
            if(video._div.id !== ""){
                var divs = video._div.querySelectorAll("#"+video._div.id+" > div");
                // for each iframe
                for (let i = 0; i < divs.length; i++) {
                    if(divs[i].classList.contains("c-nt") === true || divs[i].classList.contains("c-bg") || divs[i].classList.contains("c-ld")){
                        divs[i].parentNode.classList = [];
                        continue;
                    }
                    divs[i].style.display = "none";
                }
            }
        }

        //Check if iframe is already removed if not remove it from the dom
        var selection = video._iframe.parentNode.querySelector("iframe") !== null;
        if (selection) {
            video._iframe.parentNode.querySelector("iframe").remove()
        }

        video._hasIframe = false;
    };

    /**
     * Remove necessary classes to hide notice
     * @param {IframeObj} video
     */
    function hideNotice(video) {
        video._div.classList.add('c-h-n');
        video._showNotice = false;
    };

    /**
     * Add necessary classes to show notice
     * @param {IframeObj} video
     */
    var showNotice = function (video) {
        video._div.classList.remove('c-h-n', 'c-h-b');
        video._showNotice = true;
    };

    /**
     * Get cookie by name
     * @param {String} a cookie name
     * @returns {String} cookie value
     */
    var getCookie = function (a) {
        return (a = doc.cookie.match("(^|;)\\s*" + a + "\\s*=\\s*([^;]+)")) ? a.pop() : '';
    };

    /**
     * Delete cookie by name & path
     * @param {Array} cookies
     * @param {String} custom_path
     */
    var eraseCookie = function (cookie) {
        var path = cookie.path || '/';
        var domain = cookie.domain || location.hostname;
        var expires = 'Expires=Thu, 01 Jan 1970 00:00:01 GMT;';

        doc.cookie = cookie.name + '=; Path=' + path + '; Domain=' + domain + '; ' + expires;
    };

    /**
     * Get all prop. keys defined inside object
     * @param {Object} obj
     */
    var getKeys = function (obj) {
        return Object.keys(obj);
    };

    /**
     * Add link rel="preconnect"
     * @param {String} _url
     */
    var preconnect = function (_url) {
        var url = _url.split('://');
        var protocol = url[0];

        // if valid protocol
        if (
            protocol === 'http' ||
            protocol === 'https'
        ) {
            var domain = (url[1] && url[1].split('/')[0]) || false;

            // if not current domain
            if (domain && domain !== location.hostname) {
                if (preconnects.indexOf(domain) === -1) {
                    var l = createNode('link');
                    l.rel = 'preconnect';
                    l.href = protocol + '://' + domain;
                    appendChild(doc.head, l);
                    preconnects.push(domain);
                }
            }
        }
    };

    /**
     * Add link rel="preload"
     * @param {String} url
     */
    function preloadThumbnail(url) {
        if (url && preloads.indexOf(url) === -1) {
            var l = createNode('link');
            l.rel = 'preload';
            l.as = 'image';
            l.href = url;
            appendChild(doc.head, l);
            preloads.push(url);
        }
    }

    /**
     * Create and return HTMLElement based on specified type
     * @param {String} type
     * @returns {HTMLElement}
     */
    function createNode(type) {
        return doc.createElement(type);
    }

    /**
     * @param {HTMLElement} el
     * @param {string} className
     */
    function setClassName(el, className) {
        el.className = className;
    }

    /**
     * @param {HTMLElement} parent
     * @param {HTMLElement} child
     */
    function appendChild(parent, child) {
        parent.appendChild(child);
    }

    /**
     * Create all notices relative to the specified service
     * @param {string} serviceName
     * @param {Service} service
     * @param {boolean} hidden
     */
    var createAllNotices = function (serviceName, service, hidden) {

        // get number of iframes of current service
        var _iframes = iframeDivs[serviceName];
        var nIframes = _iframes.length;
        var languages = service.languages;

        // for each iframe
        for (var i = 0; i < nIframes; i++) {
            (function (i) {

                var video = _iframes[i];

                if (!video._hasNotice) {
                    var loadBtnText = languages[currLang].loadBtn;
                    var noticeText = languages[currLang].notice;
                    var loadAllBtnText = languages[currLang].loadAllBtn;

                    var fragment = doc.createDocumentFragment();
                    var notice = createNode('div');
                    var span = createNode('span');
                    var innerDiv = createNode('p');
                    var load_button = createNode('button');
                    var load_all_button = createNode('button');

                    var notice_text = createNode('span');
                    var ytVideoBackground = createNode('div');
                    var loaderBg = createNode('div');
                    var ytVideoBackgroundInner = createNode('div');
                    var notice_text_container = createNode('div');
                    var buttons = createNode('div');

                    load_button.type = load_all_button.type = 'button';

                    setClassName(notice_text, 'cc-text');
                    setClassName(ytVideoBackgroundInner, 'c-bg-i');

                    video._backgroundDiv = ytVideoBackgroundInner;
                    setClassName(loaderBg, 'c-ld');

                    if (typeof video._thumbnail !== 'string' || video._thumbnail !== '') {
                        setClassName(ytVideoBackground, 'c-bg');
                    }

                    var iframeTitle = video._title;
                    var fragment_2 = doc.createDocumentFragment();

                    if (iframeTitle) {
                        var title_span = createNode('span');
                        setClassName(title_span, 'c-tl');
                        title_span.insertAdjacentHTML('beforeend', iframeTitle);
                        appendChild(fragment_2, title_span);
                    }

                    load_button.textContent = loadBtnText;
                    load_all_button.textContent = loadAllBtnText;

                    appendChild(notice_text, fragment_2);
                    notice && notice_text.insertAdjacentHTML('beforeend', noticeText || "");
                    appendChild(span, notice_text);

                    setClassName(notice_text_container, 'c-t-cn');
                    setClassName(span, 'c-n-t');
                    setClassName(innerDiv, 'c-n-c');
                    setClassName(notice, 'c-nt');

                    setClassName(buttons, 'c-n-a');
                    setClassName(load_button, 'c-l-b');
                    setClassName(load_all_button, 'c-la-b');

                    if(loadBtnText.length >= 1){
                        appendChild(buttons, load_button);
                    }
                    if(loadAllBtnText.length >= 1){
                        appendChild(buttons, load_all_button);
                    }

                    appendChild(notice_text_container, span);
                    appendChild(notice_text_container, buttons);

                    appendChild(innerDiv, notice_text_container);
                    appendChild(notice, innerDiv);

                    function showVideo() {
                        hideNotice(video);
                        createIframe(video, service);
                    }

                    load_button.addEventListener('click', function () {
                        showVideo();
                    });

                    load_all_button.addEventListener('click', function () {
                        showVideo();
                        api.acceptService(serviceName);
                    });

                    appendChild(ytVideoBackground, ytVideoBackgroundInner);
                    appendChild(fragment, notice);
                    (service.thumbnailUrl || video._thumbnail) && appendChild(fragment, ytVideoBackground);
                    appendChild(fragment, loaderBg);

                    hidden && video._div.classList.add('c-h-n');

                    // Avoid reflow with fragment (only 1 appendChild)
                    appendChild(video._div, fragment);
                    video._hasNotice = true;
                }
            })(i);
        }
    };

    /**
     * Hides all notices relative to the specified service
     * and creates iframe with the video
     * @param {string} serviceName
     * @param {Service} service
     */
    var hideAllNotices = function (serviceName, service) {

        // get number of iframes of current service
        var videos = iframeDivs[serviceName];

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                if (stopObserver) {
                    observer.disconnect();
                    return;
                }
                for (var i = 0; i < entries.length; ++i) {
                    if (entries[i].isIntersecting) {
                        (function (_index) {
                            setTimeout(function () {
                                var index = entries[_index].target.dataset.index;
                                createIframe(videos[index], service);
                                hideNotice(videos[index]);
                            }, _index * 50);
                            observer.unobserve(entries[_index].target);
                        })(i);
                    }
                }
            });

            videos.forEach(function (video) {
                if (!video._hasIframe)
                    observer.observe(video._div);
            });
        } else {
            for (var i = 0; i < videos.length; i++) {
                (function (index) {
                    createIframe(videos[i], service);
                    hideNotice(videos[index]);
                })(i);
            }
        }
    };


    /**
     * Show all notices relative to the specified service
     * and hides iframe with the video
     * @param {string} serviceName
     * @param {Service} service
     */
    var showAllNotices = function (serviceName, service) {

        // get number of iframes of current service
        var videos = iframeDivs[serviceName];
        var nDivs = videos.length;

        for (var i = 0; i < nDivs; i++) {
            (function (index) {
                // if doesn't have iframe => create it
                if (videos[i]._hasIframe) {
                    if (typeof service.onReject === 'function') {
                        service.onReject(videos[i]._iframe);
                        videos[i]._hasIframe = false;
                    } else {
                       removeIframe(videos[i]);

                    }
                }
                showNotice(videos[index]);
            })(i);
        }
    };

    /**
     * Validate language (make sure it exists)
     * @param {string} lang
     * @param {Object} allLanguages
     * @returns {string} language
     */
    var getValidatedLanguage = function (lang, allLanguages) {
        if (allLanguages.hasOwnProperty(lang)) {
            return lang;
        } else if (getKeys(allLanguages).length > 0) {
            if (allLanguages.hasOwnProperty(currLang)) {
                return currLang;
            } else {
                return getKeys(allLanguages)[0];
            }
        }
    };

    /**
     * Get current client's browser language
     * @returns {String} browser language
     */
    var getBrowserLang = function () {
        return navigator.language.slice(0, 2).toLowerCase()
    };

    var api = {

        /**
         * 1. Set cookie (if not alredy set)
         * 2. show iframes (relative to the specified service)
         * @param {string} serviceName
         */
        acceptService: function (serviceName) {
            stopObserver = false;

            if (serviceName === 'all') {
                var length = serviceNames.length;
                for (var i = 0; i < length; i++) {
                    var serviceName = serviceNames[i];
                    acceptHelper(serviceName, services[serviceName]);
                }
            } else if (serviceNames.indexOf(serviceName) > -1) {
                acceptHelper(serviceName, services[serviceName]);
            }

            function acceptHelper(serviceName, service) {

                hideAllNotices(serviceName, service);
            }
        },

        /**
         * 1. set cookie
         * 2. hide all notices
         * 3. how iframes (relative to the specified service)
         * @param {string} service_name
         */
        rejectService: function (serviceName) {
            if (serviceName === 'all') {
                stopObserver = true;
                var length = serviceNames.length;
                for (var i = 0; i < length; i++) {
                    var serviceName = serviceNames[i];
                    rejectHelper(serviceName, services[serviceName]);
                }
            } else {
                if (serviceNames.indexOf(serviceName) > -1) {
                    rejectHelper(serviceName, services[serviceName]);
                }
            }

            function rejectHelper(serviceName, service) {
                if(getCookie("cf_cookie").length > 1){
                    var obj = JSON.parse(getCookie("cf_cookie"));
                    if(obj.categories.indexOf((service.cookie.name) != -1))
                    {
                        eraseCookie(service.cookie);
                    }
                }

                showAllNotices(serviceName, service);
            }
        },

        observe: function (target, callback) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'childList') {
                        setTimeout(function () {
                            callback(target.querySelector('iframe'));
                        }, 300);

                        // later, you can stop observing
                        observer.disconnect();
                        return;
                    }
                });
            });

            if (target.querySelector('iframe')) {
                setTimeout(function () {
                    callback(target.querySelector('iframe'));
                }, 300);
            } else {
                // pass in the target node, as well as the observer options
                observer.observe(target, {
                    attributes: false,
                    childList: true,
                    subtree: false
                });
            }
        },

        run: function (_config) {
            /**
             * Object with all services config.
             */
            services = _config.services;

            /**
             * Array containing the names of all services
             */
            serviceNames = getKeys(services);

            /**
             * Number of services
             */
            var nServices = serviceNames.length;

            // if there are no services => don't do anything
            if (nServices === 0) {
                return;
            }

            // Set curr lang
            currLang = _config.currLang;
            var languages = services[serviceNames[0]].languages;

            if (_config.autoLang === true) {
                currLang = getValidatedLanguage(getBrowserLang(), languages);
            } else {
                if (typeof _config.currLang === 'string') {
                    currLang = getValidatedLanguage(_config.currLang, languages);
                }
            }

            // for each service
            for (var i = 0; i < nServices; i++) {

                /**
                 * Name of current service
                 */
                var serviceName = serviceNames[i];

                // add new empty array of videos (with current service name as property)
                iframeDivs[serviceName] = [];

                /**
                 * iframes/divs in the dom that have data-service value as current service name
                 */
                /**
                 * @type {NodeListOf<HTMLDivElement>}
                 */
                var foundDivs = doc.querySelectorAll('div[data-service="' + serviceName + '"]');


                /**
                 * number of iframes with current service
                 */
                var nDivs = foundDivs.length;

                // if no iframes found => go to next service
                if (nDivs === 0) {
                    continue;
                }

                // add each iframe to array of iframes of the current service
                for (var j = 0; j < nDivs; j++) {
                    foundDivs[j].dataset.index = j;
                    iframeDivs[serviceName].push(getVideoProp(foundDivs[j]));
                }

                var currService = services[serviceName];

                // check if cookie for current service is set
                var cookie_name = currService.cookie.name;

                // get current service's cookie value
                if(getCookie("cf_cookie").length <= 0){
                     createAllNotices(serviceName, currService, false);
                }else{
                    var obj = JSON.parse(getCookie("cf_cookie"));
                    if(obj.categories.indexOf(cookie_name) != -1)
                    {
                        createAllNotices(serviceName, currService, true);
                        hideAllNotices(serviceName, currService);
                    } else {
                        createAllNotices(serviceName, currService, false);
                    }
                }



                lazyLoadThumnails(serviceName, currService.thumbnailUrl);
            }
        }
    };

    var fn_name = 'iframemanager';

    window[fn_name] = function () {
        window[fn_name] = undefined;
        return api;
    };

})();