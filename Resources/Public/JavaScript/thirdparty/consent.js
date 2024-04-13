/*!
 * Forked from CookieConsent v2.8.9
 * https://www.github.com/orestbida/cookieconsent
 * Author Orest Bida
 * Released under the MIT License
 * Modified by Codingfreaks for Typo3 CMS integration
 */
(function () {
    'use strict';
    /**
     * @param {HTMLElement} [root] - [optional] element where the cookieconsent will be appended
     * @returns {Object} cookieconsent object with API
     */
    var CookieConsent = function (root) {

        /**
         * CHANGE THIS FLAG FALSE TO DISABLE console.log()
         */
        var ENABLE_LOGS = false;

        var _config = {
            'mode': 'opt-in',                         // 'opt-in', 'opt-out'
            'current_lang': 0,                        // Set Default SySLanguage UID (0 = System Default Language)
            'auto_language': null,
            'autorun': true,                          // run as soon as loaded
            'page_scripts': true,
            'hide_from_bots': true,
            'cookie_name': 'cf_cookie',
            'cookie_expiration': 182,                 // default: 6 months (in days)
            'cookie_domain': window.location.hostname,       // default: current domain
            'cookie_path': '/',
            'cookie_same_site': 'Lax',
            'use_rfc_cookie': false,
            'autoclear_cookies': true,
            'revision': 0,
            'script_selector': 'data-service'
        };

        var
            /**
             * Object which holds the main methods/API (.show, .run, ...)
             */
            _cookieconsent = {},
            /**
             * Object which holds the main Categorys selected)
             */
            _categorysSelected = {},

            /**
             * Global user configuration object
             */
            user_config,

            /**
             * Internal state variables
             */
            saved_cookie_content = {},
            cookie_data = null,

            /**
             * @type {Date}
             */
            consent_date,

            /**
             * @type {Date}
             */
            last_consent_update,

            /**
             * @type {string}
             */
            consent_uuid,

            /**
             * @type {boolean}
             */
            invalid_consent = true,

            consent_modal_exists = false,
            consent_modal_visible = false,

            settings_modal_visible = false,
            clicked_inside_modal = false,
            current_modal_focusable,

            all_table_headers,
            all_blocks,

            // Helper callback functions
            // (avoid calling "user_config['onAccept']" all the time)
            onAccept,
            onChange,
            onFirstAction,

            revision_enabled = false,
            valid_revision = true,
            revision_message = '',

            // State variables for the autoclearCookies function
            changed_settings = [],
            reload_page = false;

        /**
         * Accept type:
         *  - "all"
         *  - "necessary"
         *  - "custom"
         * @type {string}
         */
        var accept_type;

        /**
         * Contains all accepted categories
         * @type {string[]}
         */
        var accepted_categories = [];

        /**
         * Contains all non-accepted (rejected) categories
         * @type {string[]}
         */
        var rejected_categories = [];

        /**
         * Contains all categories enabled by default
         * @type {string[]}
         */
        var default_enabled_categories = [];

        // Don't run plugin (to avoid indexing its text content) if bot detected
        var is_bot = false;

        /**
         * Save reference to the last focused element on the page
         * (used later to restore focus when both modals are closed)
         */
        var last_elem_before_modal;
        var last_consent_modal_btn_focus;

        /**
         * Both of the arrays below have the same structure:
         * [0] => holds reference to the FIRST focusable element inside modal
         * [1] => holds reference to the LAST focusable element inside modal
         */
        var consent_modal_focusable = [];
        var settings_modal_focusable = [];

        /**
         * Keep track of enabled/disabled categories
         * @type {boolean[]}
         */
        var toggle_states = [];

        /**
         * Stores all available categories
         * @type {string[]}
         */
        var all_categories = [];

        /**
         * Keep track of readonly toggles
         * @type {boolean[]}
         */
        var readonly_categories = [];

        /**
         * Pointers to main dom elements (to avoid retrieving them later using document.getElementById)
         */
        var
            /** @type {HTMLElement} */ html_dom = document.documentElement,
            /** @type {HTMLElement} */ main_container,
            /** @type {HTMLElement} */ all_modals_container,
            /** @type {HTMLElement} */ consent_modal,
            /** @type {HTMLElement} */ settings_container,
            /** @type {HTMLElement} */ new_settings_blocks;



        /**
         * Update config settings
         * @param {Object} user_config
         */
        var _setConfig = function (_user_config) {
            /**
             * Make user configuration globally available
             */
            user_config = _user_config;

            _log("CookieConsent [CONFIG]: received_config_settings ", user_config);

            if (typeof user_config['cookie_expiration'] === "number")
                _config.cookie_expiration = user_config['cookie_expiration'];

            if (typeof user_config['cookie_necessary_only_expiration'] === "number")
                _config.cookie_necessary_only_expiration = user_config['cookie_necessary_only_expiration'];

            if (typeof user_config['autorun'] === "boolean")
                _config.autorun = user_config['autorun'];

            if (typeof user_config['cookie_domain'] === "string")
                _config.cookie_domain = user_config['cookie_domain'];

            if (typeof user_config['cookie_same_site'] === "string")
                _config.cookie_same_site = user_config['cookie_same_site'];

            if (typeof user_config['cookie_path'] === "string")
                _config.cookie_path = user_config['cookie_path'];

            if (typeof user_config['cookie_name'] === "string")
                _config.cookie_name = user_config['cookie_name'];

            if (typeof user_config['onAccept'] === "function")
                onAccept = user_config['onAccept'];

            if (typeof user_config['onFirstAction'] === "function")
                onFirstAction = user_config['onFirstAction'];

            if (typeof user_config['onChange'] === "function")
                onChange = user_config['onChange'];

            if (user_config['mode'] === 'opt-out')
                _config.mode = 'opt-out';

            if (typeof user_config['revision'] === "number") {
                user_config['revision'] > -1 && (_config.revision = user_config['revision']);
                revision_enabled = true;
            }

            if (typeof user_config['autoclear_cookies'] === "boolean")
                _config.autoclear_cookies = user_config['autoclear_cookies'];

            if (user_config['use_rfc_cookie'] === true)
                _config.use_rfc_cookie = true;

            if (typeof user_config['hide_from_bots'] === "boolean") {
                _config.hide_from_bots = user_config['hide_from_bots'];
            }

            if (_config.hide_from_bots) {
                is_bot = navigator &&
                    ((navigator.userAgent && /bot|crawl|spider|slurp|teoma|lighthouse/i.test(navigator.userAgent)) || ( navigator.webdriver && navigator.userAgent.indexOf("CF-CookieScanner") === -1));
            }

            _config.page_scripts = user_config['page_scripts'] === true;

            if (user_config['auto_language'] === 'browser' || user_config['auto_language'] === true) {
                _config.auto_language = 'browser';
            } else if (user_config['auto_language'] === 'document') {
                _config.auto_language = 'document';
            }

            _log("CookieConsent [LANG]: auto_language strategy is '" + _config.auto_language + "'");

            _config.current_lang = _resolveCurrentLang(user_config.languages, user_config['current_lang']);
        }

        /**
         * Add an onClick listeners to all html elements with data-cc attribute
         */
        var _addDataButtonListeners = function (elem) {

            var _a = 'accept-';

            var show_settings = _getElements('c-settings');
            var accept_all = _getElements(_a + 'all');
            var accept_necessary = _getElements(_a + 'necessary');
            var accept_custom_selection = _getElements(_a + 'custom');

            for (var i = 0; i < show_settings.length; i++) {
                show_settings[i].setAttribute('aria-haspopup', 'dialog');
                _addEvent(show_settings[i], 'click', function (event) {
                    event.preventDefault();
                    _cookieconsent.showSettings(0);
                });
            }

            for (i = 0; i < accept_all.length; i++) {
                _addEvent(accept_all[i], 'click', function (event) {
                    _acceptAction(event, 'all');
                });
            }

            for (i = 0; i < accept_custom_selection.length; i++) {
                _addEvent(accept_custom_selection[i], 'click', function (event) {
                    _acceptAction(event);
                });
            }

            for (i = 0; i < accept_necessary.length; i++) {
                _addEvent(accept_necessary[i], 'click', function (event) {
                    _acceptAction(event, []);
                });
            }

            /**
             * Return all elements with given data-cc role
             * @param {string} data_role
             * @returns {NodeListOf<Element>}
             */
            function _getElements(data_role) {
                return (elem || document).querySelectorAll('a[data-cc="' + data_role + '"], button[data-cc="' + data_role + '"]');
            }

            /**
             * Helper function: accept and then hide modals
             * @param {PointerEvent} e source event
             * @param {string} [accept_type]
             */
            function _acceptAction(e, accept_type) {
                e.preventDefault();
                _cookieconsent.accept(accept_type);
                _cookieconsent.hideSettings();
                _cookieconsent.hide();
            }
        }

        /**
         * Get a valid language (at least 1 must be defined)
         * @param {string} lang - desired language
         * @param {Object} all_languages - all defined languages
         * @returns {string} validated language
         */
        var _getValidatedLanguage = function (lang, all_languages) {
            if (Object.prototype.hasOwnProperty.call(all_languages, lang)) {
                return lang;
            } else if (_getKeys(all_languages).length > 0) {
                if (Object.prototype.hasOwnProperty.call(all_languages, _config.current_lang)) {
                    return _config.current_lang;
                } else {
                    return _getKeys(all_languages)[0];
                }
            }
        }

        /**
         * Save reference to first and last focusable elements inside each modal
         * to prevent losing focus while navigating with TAB
         */
        var _getModalFocusableData = function () {

            /**
             * Note: any of the below focusable elements, which has the attribute tabindex="-1" AND is either
             * the first or last element of the modal, won't receive focus during "open/close" modal
             */
            var allowed_focusable_types = ['[href]', 'button', 'input', 'details', '[tabindex="0"]'];

            function _getAllFocusableElements(modal, _array) {
                var focus_later = false, focus_first = false;
                // ie might throw exception due to complex unsupported selector => a:not([tabindex="-1"])
                try {
                    var focusable_elems = modal.querySelectorAll(allowed_focusable_types.join(':not([tabindex="-1"]), '));
                    var attr, len = focusable_elems.length, i = 0;

                    while (i < len) {

                        attr = focusable_elems[i].getAttribute('data-focus');

                        if (!focus_first && attr === "1") {
                            focus_first = focusable_elems[i];

                        } else if (attr === "0") {
                            focus_later = focusable_elems[i];
                            if (!focus_first && focusable_elems[i + 1].getAttribute('data-focus') !== "0") {
                                focus_first = focusable_elems[i + 1];
                            }
                        }

                        i++;
                    }

                } catch (e) {
                    return modal.querySelectorAll(allowed_focusable_types.join(', '));
                }

                /**
                 * Save first and last elements (used to lock/trap focus inside modal)
                 */
                _array[0] = focusable_elems[0];
                _array[1] = focusable_elems[focusable_elems.length - 1];
                _array[2] = focus_later;
                _array[3] = focus_first;
            }

            /**
             * Get settings modal'S all focusable elements
             * Save first and last elements (used to lock/trap focus inside modal)
             */
            _getAllFocusableElements(all_modals_container.querySelector("#s-inr"), settings_modal_focusable);

            /**
             * If consent modal exists, do the same
             */
            if (consent_modal_exists) {
                _getAllFocusableElements(all_modals_container, consent_modal_focusable);
            }
        }

        var _createConsentModal = function (lang) {

            if (user_config['force_consent'] === true)
                _addClass(html_dom, 'force--consent');

            // Create modal if it doesn't exist
            if (!consent_modal) {
                const consent_modal = _createNode('div');

                if(typeof CF_CONSENTMODAL_TEMPLATE !== "undefined"){
                    consent_modal.innerHTML = CF_CONSENTMODAL_TEMPLATE;
                }else{
                    consent_modal.innerHTML = `
                    <div id="cm" role="dialog" aria-modal="true" aria-hidden="false" aria-labelledby="c-ttl" aria-describedby="c-txt" style="visibility: hidden;">
                        <div id="c-inr">
                            <div id="c-inr-i">
                                <div id="c-ttl" role="heading" aria-level="2"></div>
                                <div id="c-txt"></div>
                            </div>
                            <div id="c-bns"><button type="button" id="c-p-bn" class="c-bn"></button><button type="button" id="c-s-bn" class="c-bn c_link"></button><button type="button" id="c-t-bn" class="c-bn c_settings"></button></div>
                        </div>
                        <div id="c-footer"><div class="c-links"><div class="c-link-group">[##linkPrivacy##] [##linkImpress##]</div></div></div>
                    </div>`;

                }

                var overlay = _createNode('div');
                overlay.id = 'cm-ov';
                // Append consent modal to main container
                all_modals_container.appendChild(consent_modal);
                all_modals_container.appendChild(overlay);
                /**
                 * Make modal by default hidden to prevent weird page jumps/flashes (shown only once css is loaded)
                 */
                consent_modal.style.visibility = overlay.style.visibility = "hidden";
                overlay.style.opacity = 0;
            }

            // Use insertAdjacentHTML instead of innerHTML
            var consent_modal_title_value = user_config.languages[lang]['consent_modal']['title'];

            // Add title (if valid)
            if (consent_modal_title_value) {
                all_modals_container.querySelector("#c-ttl").innerHTML = consent_modal_title_value;
            }

            var description = user_config.languages[lang]['consent_modal']['description'];
            var impress_link = user_config.languages[lang]['consent_modal']['impress_link'];
            var data_policy_link = user_config.languages[lang]['consent_modal']['data_policy_link'];


            if(data_policy_link.length === 0 && impress_link.length === 0){
                all_modals_container.querySelector("#c-footer").style.display = "none";
            }

            if (data_policy_link.length > 0) {
                _replaceLink("[##linkPrivacy##]", data_policy_link);
            } else {
                _replaceLink("[##linkPrivacy##]", "");
            }


            if (impress_link.length > 0) {
                _replaceLink("[##linkImpress##]", impress_link);
            } else {
                _replaceLink("[##linkImpress##]", "");
            }


            if (revision_enabled) {
                if (!valid_revision) {
                    description = description.replace("{{revision_message}}", revision_message || user_config.languages[lang]['consent_modal']['revision_message'] || "");
                }else{
                    description = description.replace("{{revision_message}}", "");
                }
            }else{
                description = description.replace("{{revision_message}}", "");
            }

            // Set description content
            all_modals_container.querySelector("#c-txt").innerHTML = description;

            var primary_btn_data = user_config.languages[lang]['consent_modal']['primary_btn'],   // accept current selection
                secondary_btn_data = user_config.languages[lang]['consent_modal']['secondary_btn'],
                tertiary_btn_data = user_config.languages[lang]['consent_modal']['tertiary_btn'];

            // Add primary button if not falsy
            if (primary_btn_data) {
                if (primary_btn_data['role'] === 'display_none') {
                    all_modals_container.querySelector("#c-p-bn").style.display = "none";
                }else if(primary_btn_data['role'] === "accept_all"){
                    _addEvent(all_modals_container.querySelector("#c-p-bn"), "click", function () {
                        _cookieconsent.hide();
                        _cookieconsent.accept("all");
                    });
                } else if (primary_btn_data['role'] === 'accept_necessary') {
                    _addEvent(all_modals_container.querySelector("#c-p-bn"), 'click', function () {
                        _cookieconsent.hide();
                        _cookieconsent.accept([]); // accept necessary only
                    });
                } else {
                    _addEvent(all_modals_container.querySelector("#c-p-bn"), 'click', function () {
                        _cookieconsent.showSettings(0);
                    });
                }
                all_modals_container.querySelector("#c-p-bn").innerHTML = user_config.languages[lang]['consent_modal']['primary_btn']['text'];
            }

            // Add secondary button if not falsy
            if (secondary_btn_data) {
                if (secondary_btn_data['role'] === 'display_none') {
                    all_modals_container.querySelector("#c-s-bn").style.display = "none";
                }else if(secondary_btn_data['role'] === "accept_all"){
                    _addEvent(all_modals_container.querySelector("#c-s-bn"), "click", function () {
                        _cookieconsent.hide();
                        _cookieconsent.accept("all");
                    });
                } else if (secondary_btn_data['role'] === 'accept_necessary') {
                    _addEvent(all_modals_container.querySelector("#c-s-bn"), 'click', function () {
                        _cookieconsent.hide();
                        _cookieconsent.accept([]); // accept necessary only
                    });
                } else {
                    _addEvent(all_modals_container.querySelector("#c-s-bn"), 'click', function () {
                        _cookieconsent.showSettings(0);
                    });
                }
                all_modals_container.querySelector("#c-s-bn").innerHTML = user_config.languages[lang]['consent_modal']['secondary_btn']['text'];
            }



            // Add tertiary button if not falsy c-t-bn
            if(tertiary_btn_data){
                if (tertiary_btn_data['role'] === 'display_none') {
                    all_modals_container.querySelector("#c-t-bn").style.display = "none";
                }else if(tertiary_btn_data['role'] === "accept_all"){
                    _addEvent(all_modals_container.querySelector("#c-t-bn"), "click", function () {
                        _cookieconsent.hide();
                        _cookieconsent.accept("all");
                    });
                } else if (tertiary_btn_data['role'] === 'accept_necessary') {
                    _addEvent(all_modals_container.querySelector("#c-t-bn"), 'click', function () {
                        _cookieconsent.hide();
                        _cookieconsent.accept([]); // accept necessary only
                    });
                } else {
                    _addEvent(all_modals_container.querySelector("#c-t-bn"), 'click', function () {
                        _cookieconsent.showSettings(0);
                    });
                }
                all_modals_container.querySelector("#c-t-bn").innerHTML = user_config.languages[lang]['consent_modal']['tertiary_btn']['text'];

            }


            consent_modal_exists = true;

            _addDataButtonListeners(all_modals_container);
        }


        var _createSettingsModal = function (lang) {
            /**
             * Create all consent_modal elements
             */

            if(typeof CF_SETTINGSMODAL_TEMPLATE !== "undefined"){
                var settingsHTML = CF_SETTINGSMODAL_TEMPLATE;
            }else{
                var settingsHTML = `
                    <div id="s-cnt" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="s-ttl" style="visibility: hidden;">
                        <div id="c-vln">
                            <div id="cs">
                                <div id="c-s-in">
                                    <div id="s-inr" class="bns-t">
                                        <div id="s-hdr">
                                            <div id="s-ttl" role="heading">Cookie Settings</div>
                                            <div id="s-c-bnc">
                                                <button type="button" id="s-c-bn" class="c-bn" aria-label=""></button>
                                            </div>
                                        </div>
                                        <div id="s-bl">
                                            <div id="cf-category-wrapper" class="cf-category-wrapper">
                               
                                            </div>
                                        </div>
                                        <div id="s-bns">
                                            <button type="button" id="s-all-bn" class="c-bn">Accept All</button>
                                            <button type="button" id="s-rall-bn" class="c-bn">Reject All</button>
                                            <button type="button" id="s-sv-bn" class="c-bn">Save</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            `;
            }

            const settings_modal = _createNode('div');
            settings_modal.innerHTML = settingsHTML;
            all_modals_container.appendChild(settings_modal);

            var impress_link = user_config.languages[lang]['consent_modal']['impress_link'];
            var data_policy_link = user_config.languages[lang]['consent_modal']['data_policy_link'];



            if (data_policy_link.length > 0) {
                _replaceLink("[##linkPrivacy##]", data_policy_link);
            } else {
                _replaceLink("[##linkPrivacy##]", "");
            }


            if (impress_link.length > 0) {
                _replaceLink("[##linkImpress##]", impress_link);
            } else {
                _replaceLink("[##linkImpress##]", "");
            }

            if (!settings_container) {
                // If 'esc' key is pressed inside settings_container div => hide settings
                _addEvent(all_modals_container.querySelector("#c-vln"), 'keydown', function (evt) {
                    evt = evt || window.event;
                    if (evt.keyCode === 27) {
                        _cookieconsent.hideSettings(0);
                    }
                }, true);

                _addEvent(all_modals_container.querySelector("#s-c-bn"), 'click', function () {
                    _cookieconsent.hideSettings(0);
                });
            } else {
                new_settings_blocks = _createNode('div');
                new_settings_blocks.id = 's-bl';
            }

            // Add label to close button
            all_modals_container.querySelector("#s-c-bn").setAttribute('aria-label', user_config.languages[lang]['settings_modal']['close_btn_label'] || 'Close');

            all_blocks = user_config.languages[lang]['settings_modal']['blocks'];
            all_table_headers = user_config.languages[lang]['settings_modal']['cookie_table_headers'];

            var n_blocks = all_blocks.length;
            // Set settings modal title
            all_modals_container.querySelector("#s-ttl").innerHTML = user_config.languages[lang]['settings_modal']['title'];
            /* CF HOOK */

            if(typeof CF_SETTINGSMODAL_CATEGORY_TEMPLATE !== "undefined"){
                var categoryHTML = CF_SETTINGSMODAL_CATEGORY_TEMPLATE;
            }else{
                var categoryHTML = `
                    <div class="titlecategory b-bn">
                        <button type="button" class="b-tl exp" aria-expanded="false" aria-controls="">Externe Medien</button>
                        <label class="expand-button-cfwrapper b-tg"><input class="expand-button "  type="checkbox"  value="">
                            <span class="c-tg" aria-hidden="true">
                                <span class="on-i"></span>
                                <span class="off-i"></span>
                            </span>
                            <span class="t-lb"></span>
                        </label>
                    </div>
                    <div class="block-section b-ex">
                        <div class="cf-category-description"></div>
                    </div>
               `;
            }


            var categories = user_config.languages[lang]['settings_modal']['categories'];
            var n_categories = categories.length;
            var cf_category_list_full = [];
            for (var ii = 0; ii < n_categories; ++ii) {
                var category;
                category = _createNode('div');
                category.id = categories[ii]["category"];
                category.className = categories[ii]["category"] + "-cfwrapper cfwrapper";
                category.innerHTML = categoryHTML;

                 category.querySelector(".cf-category-description").insertAdjacentHTML('beforeend', categories[ii]["description"]);
                // Create toggle if specified (opt in/out)
                isExpandable = true;
                if (true) {
                    var accordion_id2 = "c-acc-" + ii;

                    // Create button (to collapse/expand block description)
                    var block_title_btn2 = category.querySelector(".b-tl");
                    var block_switch2 = category.querySelector(".expand-button");
                    var block_switch_span2 = category.querySelector(".c-tg");

                    // These 2 spans will contain each 2 pseudo-elements to generate 'tick' and 'x' icons
                    block_title_btn2.innerText = categories[ii]["title"];

                    if (isExpandable) {
                        block_title_btn2.setAttribute('aria-expanded', 'false');
                        block_title_btn2.setAttribute('aria-controls', accordion_id2);
                    }

                    //block_switch2.type = 'checkbox';
                    block_switch_span2.setAttribute('aria-hidden', 'true');
                    block_switch2.value = categories[ii]["category"];

                    /**
                     * Set toggle as readonly if true (disable checkbox)
                     */
                    if (categories[ii]["toggle"]["readonly"]) {
                        block_switch2.disabled = true;
                        _addClass(block_switch_span2, 'c-ro');
                    }

                    /**
                     * Set toggle as required if true
                     */
                    if(categories[ii]["toggle"]["enabled"]){
                        block_switch2.checked = true;
                    }

                    category.setAttribute('aria-hidden', 'true');

                    /**
                     * On button click handle the following :=> aria-expanded, aria-hidden and act class for current block
                    */
                    (function (accordion2, block_section2, btn2) {
                        _addEvent(block_title_btn2, 'click', function () {
                            if (!_hasClass(block_section2, 'opencategory')) {
                                _addClass(block_section2, 'opencategory');
                                _addClass(btn2, 'act');
                                btn2.setAttribute('aria-expanded', 'true');
                                accordion2.setAttribute('aria-hidden', 'false');
                            } else {
                                _removeClass(block_section2, 'opencategory');
                                _removeClass(btn2, 'act');
                                btn2.setAttribute('aria-expanded', 'false');
                                accordion2.setAttribute('aria-hidden', 'true');
                            }
                        }, false);
                    })(category, category.querySelector(".block-section"), block_title_btn2);

                }

                cf_category_list_full.push(category);
            }
            /* CF HOOK  END*/


            // Create settings modal content (blocks)
            for (var i = 0; i < n_blocks; ++i) {

                var title_data = all_blocks[i]['title'],
                    description_data = all_blocks[i]['description'],
                    toggle_data = all_blocks[i]['toggle'],
                    cookie_table_data = all_blocks[i]['cookie_table'],
                    remove_cookie_tables = user_config['remove_cookie_tables'] === true,
                    isExpandable = (description_data && 'truthy') || (!remove_cookie_tables && (cookie_table_data && 'truthy'));


                // Create title
                var block_section = _createNode('div');
                var block_table_container = _createNode('div');

                // Create description
                if (description_data) {
                    var block_desc = _createNode('div');
                    block_desc.className = 'p';
                    block_desc.insertAdjacentHTML('beforeend', description_data);
                }

                var block_title_container = _createNode('div');
                block_title_container.className = 'title';

                block_section.className = 'c-bl';
                block_table_container.className = 'desc';


                // Create toggle if specified (opt in/out)
                if (typeof toggle_data !== 'undefined') {
                    var accordion_id = "c-ac-" + i;

                    // Create button (to collapse/expand block description)
                    var block_title_btn = isExpandable ? _createNode('button') : _createNode('div');
                    var block_switch_label = _createNode('label');
                    var block_switch = _createNode('input');
                    var block_switch_span = _createNode('span');
                    var label_text_span = _createNode('span');

                    // These 2 spans will contain each 2 pseudo-elements to generate 'tick' and 'x' icons
                    var block_switch_span_on_icon = _createNode('span');
                    var block_switch_span_off_icon = _createNode('span');

                    block_title_btn.className = isExpandable ? 'b-tl exp' : 'b-tl';
                    block_switch_label.className = 'b-tg';
                    block_switch.className = 'c-tgl';
                    block_switch_span_on_icon.className = 'on-i';
                    block_switch_span_off_icon.className = 'off-i';
                    block_switch_span.className = 'c-tg';
                    label_text_span.className = "t-lb";

                    if (isExpandable) {
                        block_title_btn.setAttribute('aria-expanded', 'false');
                        block_title_btn.setAttribute('aria-controls', accordion_id);
                    }

                    block_switch.type = 'checkbox';
                    block_switch_span.setAttribute('aria-hidden', 'true');

                    var cookie_category = toggle_data.value;
                    block_switch.value = cookie_category;

                    label_text_span.textContent = title_data;
                    block_title_btn.insertAdjacentHTML('beforeend', title_data);

                    block_title_container.appendChild(block_title_btn);
                    block_switch_span.appendChild(block_switch_span_on_icon);
                    block_switch_span.appendChild(block_switch_span_off_icon);

                    /**
                     * If consent is valid => retrieve category states from cookie
                     * Otherwise use states defined in the user_config. object
                     */

                    if (!invalid_consent) {
                        if (_inArray(saved_cookie_content['categories'], cookie_category) > -1) {
                            block_switch.checked = true;
                            !new_settings_blocks && toggle_states.push(true);
                        } else {
                            !new_settings_blocks && toggle_states.push(false);
                        }
                    } else if (toggle_data['enabled']) {

                        block_switch.checked = true;
                        !new_settings_blocks && toggle_states.push(true);

                        /**
                         * Keep track of categories enabled by default (useful when mode=='opt-out')
                         */
                        if (toggle_data['enabled'])
                            !new_settings_blocks && default_enabled_categories.push(cookie_category);

                    } else {
                        !new_settings_blocks && toggle_states.push(false);
                    }

                    !new_settings_blocks && all_categories.push(cookie_category);

                    /**
                     * Set toggle as readonly if true (disable checkbox)
                     */
                    if (toggle_data['readonly']) {
                        block_switch.disabled = true;
                        _addClass(block_switch_span, 'c-ro');
                        !new_settings_blocks && readonly_categories.push(true);
                    } else {
                        !new_settings_blocks && readonly_categories.push(false);
                    }

                    if(toggle_data["enabled"]){
                        block_switch.checked = true;
                    }

                    block_switch.setAttribute("data-cfcookie-category", all_blocks[i]["category"]);
                    if (_categorysSelected[all_blocks[i]["category"]] === false || _categorysSelected[all_blocks[i]["category"]] === undefined) {
                        _categorysSelected[all_blocks[i]["category"]] = block_switch.checked;
                    }


                    _addClass(block_table_container, 'b-acc');
                    _addClass(block_title_container, 'b-bn');
                    _addClass(block_section, 'b-ex');
                    _addClass(block_section, all_blocks[i]["category"]);

                    block_table_container.id = accordion_id;
                    block_table_container.setAttribute('aria-hidden', 'true');

                    block_switch_label.appendChild(block_switch);
                    block_switch_label.appendChild(block_switch_span);
                    block_switch_label.appendChild(label_text_span);
                    block_title_container.appendChild(block_switch_label);

                    /**
                     * On button click handle the following :=> aria-expanded, aria-hidden and act class for current block
                     */

                    isExpandable && (function (accordion, block_section, btn) {
                        _addEvent(block_title_btn, 'click', function () {
                            if (!_hasClass(block_section, 'act')) {
                                _addClass(block_section, 'act');
                                btn.setAttribute('aria-expanded', 'true');
                                accordion.setAttribute('aria-hidden', 'false');
                            } else {
                                _removeClass(block_section, 'act');
                                btn.setAttribute('aria-expanded', 'false');
                                accordion.setAttribute('aria-hidden', 'true');
                            }
                        }, false);
                    })(block_table_container, block_section, block_title_btn);

                } else {
                    /**
                     * If block is not a button (no toggle defined),
                     * create a simple div instead
                     */
                    if (title_data) {
                        var block_title = _createNode('div');
                        block_title.className = 'b-tl freaking';
                        block_title.setAttribute('role', 'heading');
                        block_title.setAttribute('aria-level', '3');
                        block_title.insertAdjacentHTML('beforeend', title_data);
                        block_title_container.appendChild(block_title);
                    }
                }

                title_data && block_section.appendChild(block_title_container);
                description_data && block_table_container.appendChild(block_desc);
                all_modals_container.querySelector("#cf-category-wrapper").appendChild(block_section);
                // if cookie table found, generate table for this block
                if (!remove_cookie_tables && typeof cookie_table_data !== 'undefined' && cookie_table_data.length > 0) {
                    var tr_tmp_fragment = document.createDocumentFragment();

                    /**
                     * Use custom table headers
                     */
                    for (var p = 0; p < all_table_headers.length; ++p) {
                        // create new header
                        var th1 = _createNode('th');
                        var obj = all_table_headers[p];
                        th1.setAttribute('scope', 'col');

                        // get custom header content
                        if (obj) {
                            var new_column_key = obj && _getKeys(obj)[0];
                            th1.textContent = all_table_headers[p][new_column_key];
                            tr_tmp_fragment.appendChild(th1);
                        }
                    }

                    var tr_tmp = _createNode('tr');
                    tr_tmp.appendChild(tr_tmp_fragment);

                    // create table header & append fragment
                    var thead = _createNode('thead');
                    thead.appendChild(tr_tmp);

                    // append header to table
                    var block_table = _createNode('table');
                    block_table.appendChild(thead);

                    var tbody_fragment = document.createDocumentFragment();

                    // create table content
                    for (var n = 0; n < cookie_table_data.length; n++) {
                        var tr = _createNode('tr');
                        tr.setAttribute("class","cookie-item");
                        var tr_description = _createNode('tr');
                        //tr_description.style.display = "none";
                        tr_description.setAttribute("class", "cookie-additional-description");
                        _addEvent(tr, 'click', function () {
                            /* Hide all open Descriptions, only once can be opened */
                            Array.from(all_modals_container.querySelectorAll(".cookie-additional-header"))
                                .forEach(function(val) {
                                    val.setAttribute("class", "cookie-item");
                                });
                            if (_hasClass(this.nextSibling, "cookie-description-active") === false) {
                                Array.from(all_modals_container.querySelectorAll(".cookie-additional-description"))
                                    .forEach(function(val) {
                                        _removeClass(val, "cookie-description-active");
                                    });
                                this.setAttribute("class", "cookie-additional-header cookie-item");
                                _addClass(this.nextSibling, "cookie-description-active");
                            } else {
                                _removeClass(this.nextSibling, "cookie-description-active");

                            }
                        });
                        for (var g = 0; g < all_table_headers.length; ++g) {
                            // get custom header content
                            obj = all_table_headers[g];
                            if (obj) {
                                new_column_key = _getKeys(obj)[0];

                                var td_tmp = _createNode('td');
                                // Allow html inside table cells
                                td_tmp.insertAdjacentHTML('beforeend', cookie_table_data[n][new_column_key]);
                                td_tmp.setAttribute('data-column', obj[new_column_key]);


                                tr.appendChild(td_tmp);
                            }
                        }

                        var td_tmp_description = _createNode('td');
                        td_tmp_description.setAttribute('colspan', all_table_headers.length);
                        td_tmp_description.setAttribute('class', "cookie-informations");

                        var description_html_box = _createNode('div');
                        for (const key in cookie_table_data[n]["additional_information"]) {
                            if(cookie_table_data[n]["additional_information"][key]["value"] === "" || cookie_table_data[n]["additional_information"][key]["value"] === 0) continue;
                            let text = _createNode('p');
                            text.setAttribute('data-key', key);
                            text.insertAdjacentHTML('beforeend', "<span class='cookie-title'>" + cookie_table_data[n]["additional_information"][key]["title"] + ":</span>  " + cookie_table_data[n]["additional_information"][key]["value"]);
                            description_html_box.appendChild(text);
                        }

                        td_tmp_description.insertAdjacentHTML("beforeend",  description_html_box.innerHTML);
                        tr_description.appendChild(td_tmp_description);

                        tbody_fragment.appendChild(tr);
                        tbody_fragment.appendChild(tr_description);
                    }

                    // append tbody_fragment to tbody & append the latter into the table
                    var tbody = _createNode('tbody');
                    tbody.appendChild(tbody_fragment);
                    block_table.appendChild(tbody);
                    block_table_container.appendChild(block_table);

                }

                /**
                 * Append only if is either:
                 * - togglable div with title
                 * - a simple div with at least a title or description
                 */
                if (toggle_data && title_data || (!toggle_data && (title_data || description_data))) {
                    block_section.appendChild(block_table_container);

                    /*CF HOOK */
                    for (var iii = 0; iii < cf_category_list_full.length; ++iii) {

                        if (block_section.classList.contains(cf_category_list_full[iii].id)) {
                            cf_category_list_full[iii].children[1].appendChild(block_section);
                        }
                    }

                    for (var iiii = 0; iiii < cf_category_list_full.length; ++iiii) {

                        all_modals_container.querySelector("#cf-category-wrapper").appendChild(cf_category_list_full[iiii]);

                    }

                    /*CF HOOK END */
                    //if(new_settings_blocks)
                    //  new_settings_blocks.appendChild(cf_category_container);
                    //  else
                    //   settings_blocks.appendChild(cf_category_container);
                }
            }


            _addEvent(all_modals_container.querySelector("#s-all-bn"), 'click', function () {
                _cookieconsent.hideSettings();
                _cookieconsent.hide();
                _cookieconsent.accept('all');
            });


            all_modals_container.querySelector("#s-all-bn").innerHTML = user_config.languages[lang]['settings_modal']['accept_all_btn'];

            var reject_all_btn_text = user_config.languages[lang]['settings_modal']['reject_all_btn'];

            // Add third [optional] reject all button if provided
            _addEvent(all_modals_container.querySelector("#s-rall-bn"), 'click', function () {
                _cookieconsent.hideSettings();
                _cookieconsent.hide();
                _cookieconsent.accept([]);
            });
            all_modals_container.querySelector("#s-rall-bn").innerHTML = reject_all_btn_text;


            // Add save preferences button onClick event
            // Hide both settings modal and consent modal
            _addEvent(all_modals_container.querySelector("#s-sv-bn"), 'click', function () {
                _cookieconsent.hideSettings();
                _cookieconsent.hide();
                _cookieconsent.accept();
            });

            all_modals_container.querySelector("#s-sv-bn").innerHTML = user_config.languages[lang]['settings_modal']['save_settings_btn'];

            /*
                        if(new_settings_blocks) {
                            // replace entire existing cookie category blocks with the new cookie categories new blocks (in a different language)
                            settings_inner.replaceChild(new_settings_blocks, settings_blocks);
                            settings_blocks = new_settings_blocks;
                            return;
                        };
            */
        }

        /**
         * Generate cookie consent html markup
         */
        var _createCookieConsentHTML = function () {

            // Create main container which holds both consent modal & settings modal
            main_container = _createNode('div');
            main_container.id = 'cc--main';

            // Fix layout flash
            main_container.style.position = "fixed";
            main_container.style.zIndex = "2147483647";
            main_container.innerHTML = '<!--[if lt IE 9 ]><div id="cc_div" class="cc_div ie"></div><![endif]--><!--[if (gt IE 8)|!(IE)]><!--><div id="cc_div" class="cc_div"></div><!--<![endif]-->'
            all_modals_container = main_container.children[0];

            // Get current language
            var lang = _config.current_lang;

            // Create consent modal
            if (consent_modal_exists)
                _createConsentModal(lang);

            // Always create settings modal
            _createSettingsModal(lang);

            for (const key in _categorysSelected) {
                if (_categorysSelected[key] === true || _categorysSelected[key] === "true") {
                    main_container.querySelector(".expand-button[value=" + key + "]").setAttribute("checked", "checked");
                }

            }
            // Finally append everything (main_container holds both modals)
            (root || document.body).appendChild(main_container);
        }

        /**
         * Update/change modals language
         * @param {String} lang new language
         * @param {Boolean} [force] update language fields forcefully
         * @returns {Boolean}
         */
        _cookieconsent.updateLanguage = function (lang, force) {

            if (typeof lang !== 'string') return;

            /**
             * Validate language to avoid errors
             */
            var new_validated_lang = _getValidatedLanguage(lang, user_config.languages);

            /**
             * Set language only if it differs from current
             */
            if (new_validated_lang !== _config.current_lang || force === true) {
                _config.current_lang = new_validated_lang;

                if (consent_modal_exists) {
                    _createConsentModal(new_validated_lang);
                }

                _createSettingsModal(new_validated_lang);

                _log("CookieConsent [LANGUAGE]: curr_lang: '" + new_validated_lang + "'");

                return true;
            }

            return false;
        }

        /**
         * Delete all cookies which are unused (based on selected preferences)
         *
         * @param {boolean} [clearOnFirstAction]
         */
        var _autoclearCookies = function (clearOnFirstAction) {

            // Get number of blocks
            var len = all_blocks.length;
            var count = -1;

            // reset reload state
            reload_page = false;

            // Retrieve all cookies
            var all_cookies_array = _getCookie('', 'all');

            // delete cookies on 'www.domain.com' and '.www.domain.com' (can also be without www)
            var domains = [_config.cookie_domain, '.' + _config.cookie_domain];



            // if domain has www, delete cookies also for 'domain.com' and '.domain.com'
            if (_config.cookie_domain.slice(0, 4) === 'www.') {
                var non_www_domain = _config.cookie_domain.substr(4);  // remove first 4 chars (www.)
                domains.push(non_www_domain);
                domains.push('.' + non_www_domain);
            }


            // For each block
            for (var i = 0; i < len; i++) {

                // Save current block (local scope & less accesses -> ~faster value retrieval)
                var curr_block = all_blocks[i];

                // If current block has a toggle for opt in/out
                if (Object.prototype.hasOwnProperty.call(curr_block, "toggle")) {

                    // if current block has a cookie table, an off toggle,
                    // and its preferences were just changed => delete cookies
                    var category_just_disabled = _inArray(changed_settings, curr_block['toggle']['value']) > -1;
                    if (
                        !toggle_states[++count] &&
                        Object.prototype.hasOwnProperty.call(curr_block, "cookie_table") &&
                        (clearOnFirstAction || category_just_disabled)
                    ) {
                        var curr_cookie_table = curr_block['cookie_table'];

                        // Get first property name
                        var ckey = _getKeys(all_table_headers[0])[0];

                        // Get number of cookies defined in cookie_table
                        var clen = curr_cookie_table.length;

                        // set "reload_page" to true if reload=on_disable
                        if (curr_block['toggle']['reload'] === 'on_disable')
                            category_just_disabled && (reload_page = true);

                        // for each row defined in the cookie table
                        for (var j = 0; j < clen; j++) {
                            var curr_domains = domains;

                            // Get current row of table (corresponds to all cookie params)
                            var curr_row = curr_cookie_table[j], found_cookies = [];
                            var curr_cookie_name = curr_row[ckey];
                            var is_regex = curr_row['is_regex'] || false;
                            var curr_cookie_domain = curr_row['domain'] || null;
                            var curr_cookie_path = curr_row['path'] || false;

                            // set domain to the specified domain
                            curr_cookie_domain && (curr_domains = [curr_cookie_domain, '.' + curr_cookie_domain]);


                            // If regex provided => filter cookie array
                            if (is_regex) {
                                for (var n = 0; n < all_cookies_array.length; n++) {
                                    if (all_cookies_array[n].match(curr_cookie_name)) {
                                        found_cookies.push(all_cookies_array[n]);
                                    }
                                }
                            } else {
                                var found_index = _inArray(all_cookies_array, curr_cookie_name);
                                if (found_index > -1) found_cookies.push(all_cookies_array[found_index]);
                            }

                            _log("CookieConsent [AUTOCLEAR]: search cookie: '" + curr_cookie_name + "', found:", found_cookies);

                            // If cookie exists -> delete it
                            if (found_cookies.length > 0) {
                                _eraseCookies(found_cookies, curr_cookie_path, curr_domains);
                                curr_block['toggle']['reload'] === 'on_clear' && (reload_page = true);
                            }
                        }
                    }
                }
            }
        }

        /**
         * Set toggles/checkboxes based on accepted categories and save cookie
         * @param {string[]} accepted_categories - Array of categories to accept
         */
        var _saveCookiePreferences = function (accepted_categories) {

            changed_settings = [];

            // Retrieve all toggle/checkbox values
            var category_toggles = document.querySelectorAll('.c-tgl') || [];

            // If there are opt in/out toggles ...
            if (category_toggles.length > 0) {
                for (var i = 0; i < category_toggles.length; i++) {
                    var categoryContainer = all_modals_container.querySelector('#'+category_toggles[i].getAttribute("data-cfcookie-category")).querySelector(".expand-button");
                    if (_inArray(accepted_categories, all_categories[i]) !== -1) {
                        categoryContainer.checked = true;
                        category_toggles[i].checked = true;
                        if (!toggle_states[i]) {
                            changed_settings.push(all_categories[i]);
                            toggle_states[i] = true;
                        }
                    } else {
                        category_toggles[i].checked = false;
                        if (toggle_states[i]) {
                            changed_settings.push(all_categories[i]);
                            toggle_states[i] = false;
                        }

                        var isActive = all_modals_container.querySelector('#'+category_toggles[i].getAttribute("data-cfcookie-category")).querySelectorAll(".block-section input:checked");
                        if(!!isActive){
                            //categoryContainer.checked = true;
                            if(isActive.length === 0){
                                categoryContainer.checked = false;
                            }
                        }
                    }
                }
            }




            /**
             * Clear cookies when settings/preferences change
             */
            if (!invalid_consent && _config.autoclear_cookies && changed_settings.length > 0)
                _autoclearCookies();

            if (!consent_date) consent_date = new Date();
            if (!consent_uuid) consent_uuid = _uuidv4();

            saved_cookie_content = {
                "categories": accepted_categories,
                "level": accepted_categories, // Copy of the `categories` property for compatibility purposes with version v2.8.0 and below.
                "revision": _config.revision,
                "data": cookie_data,
                "rfc_cookie": _config.use_rfc_cookie,
                "consent_date": consent_date.toISOString(),
                "consent_uuid": consent_uuid
            }

            // save cookie with preferences 'categories' (only if never accepted or settings were updated)
            if (invalid_consent || changed_settings.length > 0) {
                valid_revision = true;


                /**
                 * Update "last_consent_update" only if it is invalid (after t)
                 */
                if (!last_consent_update)
                    last_consent_update = consent_date;
                else
                    last_consent_update = new Date();

                saved_cookie_content['last_consent_update'] = last_consent_update.toISOString();

                /**
                 * Update accept type
                 */
                accept_type = _getAcceptType(_getCurrentCategoriesState());

                _setCookie(_config.cookie_name, JSON.stringify(saved_cookie_content));
                _manageExistingScripts();
            }

            if (invalid_consent) {

                /**
                 * Delete unused/"zombie" cookies if consent is not valid (not yet expressed or cookie has expired)
                 */
                if (_config.autoclear_cookies)
                    _autoclearCookies(true);

                if (typeof onFirstAction === 'function')
                    onFirstAction(_cookieconsent.getUserPreferences(), saved_cookie_content);

                if (typeof onAccept === 'function')
                    onAccept(saved_cookie_content);

                /**
                 * Set consent as valid
                 */
                invalid_consent = false;

                if (_config.mode === 'opt-in') return;
            }

            // fire onChange only if settings were changed
            if (typeof onChange === "function" && changed_settings.length > 0)
                onChange(saved_cookie_content, changed_settings);

            /**
             * reload page if needed
             */
            if (reload_page)
                window.location.reload();
        }

        /**
         * Returns index of found element inside array, otherwise -1
         * @param {Array} arr
         * @param {Object} value
         * @returns {number}
         */
        var _inArray = function (arr, value) {
            return arr.indexOf(value);
        }

        /**
         * Helper function which prints info (console.log())
         * @param {Object} print_msg
         * @param {Object} [optional_param]
         */
        var _log = function (print_msg, optional_param, error) {
            ENABLE_LOGS && (!error ? console.log(print_msg, optional_param !== undefined ? optional_param : ' ') : console.error(print_msg, optional_param || ""));
        }

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
         * Generate RFC4122-compliant UUIDs.
         * https://stackoverflow.com/questions/105034/how-to-create-a-guid-uuid?page=1&tab=votes#tab-top
         * @returns {string}
         */
        var _uuidv4 = function () {
            return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, function (c) {
                try {
                    return (c ^ (window.crypto || window.msCrypto).getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
                } catch (e) {
                    return '';
                }
            });
        }

        /**
         * Resolve which language should be used.
         *
         * @param {Object} languages Object with language translations
         * @param {string} [requested_language] Language specified by given configuration parameters
         * @returns {string}
         */
        var _resolveCurrentLang = function (languages, requested_language) {

            if (_config.auto_language === 'browser') {
                return _getValidatedLanguage(_getBrowserLang(), languages);
            } else if (_config.auto_language === 'document') {
                return _getValidatedLanguage(document.documentElement.lang, languages);
            } else {
                if (typeof requested_language === 'string') {
                    return _config.current_lang = _getValidatedLanguage(requested_language, languages);
                }
            }

            _log("CookieConsent [LANG]: setting current_lang = '" + _config.current_lang + "'");
            return _config.current_lang; // otherwise return default
        }

        /**
         * Get current client's browser language
         * @returns {string}
         */
        var _getBrowserLang = function () {
            var browser_lang = navigator.language || navigator.browserLanguage;
            browser_lang.length > 2 && (browser_lang = browser_lang[0] + browser_lang[1]);
            _log("CookieConsent [LANG]: detected_browser_lang = '" + browser_lang + "'");
            return browser_lang.toLowerCase()
        }

        /**
         * Trap focus inside modal and focus the first
         * focusable element of current active modal
         */
        var _handleFocusTrap = function () {
            var tabbedOutsideDiv = false;
            var tabbedInsideModal = false;

            _addEvent(document, 'keydown', function (e) {
                e = e || window.event;

                // If is tab key => ok
                if (e.key !== 'Tab') return;

                // If there is any modal to focus
                if (current_modal_focusable) {
                    // If reached natural end of the tab sequence => restart
                    if (e.shiftKey) {
                        if (document.activeElement === current_modal_focusable[0]) {
                            current_modal_focusable[1].focus();
                            e.preventDefault();
                        }
                    } else {
                        if (document.activeElement === current_modal_focusable[1]) {
                            current_modal_focusable[0].focus();
                            e.preventDefault();
                        }
                    }

                    // If have not yet used tab (or shift+tab) and modal is open ...
                    // Focus the first focusable element
                    if (!tabbedInsideModal && !clicked_inside_modal) {
                        tabbedInsideModal = true;
                        !tabbedOutsideDiv && e.preventDefault();

                        if (e.shiftKey) {
                            if (current_modal_focusable[3]) {
                                if (!current_modal_focusable[2]) {
                                    current_modal_focusable[0].focus();
                                } else {
                                    current_modal_focusable[2].focus();
                                }
                            } else {
                                current_modal_focusable[1].focus();
                            }
                        } else {
                            if (current_modal_focusable[3]) {
                                current_modal_focusable[3].focus();
                            } else {
                                current_modal_focusable[0].focus();
                            }
                        }
                    }
                }

                !tabbedInsideModal && (tabbedOutsideDiv = true);
            });


            if (document.contains) {
                _addEvent(main_container, 'click', function (e) {
                    e = e || window.event;
                    /**
                     * If click is on the foreground overlay (and not inside settings_modal),
                     * hide settings modal
                     *
                     * Notice: click on div is not supported in IE
                     */

                    if (_hasClass(e.target, "c-tgl")) {
                        var categoryIdentifier = e.target.getAttribute("data-cfcookie-category");
                        var categoriesSwitchButton = main_container.querySelector("#" + e.target.getAttribute("data-cfcookie-category")).querySelector(".expand-button");
                        var services = main_container.querySelector("#" + categoryIdentifier).querySelectorAll(".block-section ." + categoryIdentifier);


                        var isServiceActive = false;
                        for (var i = 0; i < services.length; i++) {
                            if (services[i].querySelector("input").checked === true) {
                                isServiceActive = true;
                            }
                        }

                        if (isServiceActive === false) {
                            /* All Services are Disabled */
                            categoriesSwitchButton.checked = false;
                        } else {
                            categoriesSwitchButton.checked = true;
                        }


                    }

                    if (_hasClass(e.target, "expand-button")) {
                        /** IF Category Switch button **/
                        var services = main_container.querySelector("#" + e.target.value).querySelectorAll(".block-section ." + e.target.value);
                        for (var i = 0; i < services.length; i++) {
                            if (e.target.checked === true) {
                                services[i].querySelector("input").checked = true;
                            } else {
                                services[i].querySelector("input").checked = false;
                            }
                        }
                    }

                    if (settings_modal_visible) {
                        if (!all_modals_container.querySelector("#s-inr").contains(e.target)) {
                            _cookieconsent.hideSettings(0);
                            clicked_inside_modal = false;
                        } else {
                            clicked_inside_modal = true;
                        }
                    } else if (consent_modal_visible) {
                        if (all_modals_container.querySelector("#cm").contains(e.target)) {
                            clicked_inside_modal = true;
                        }
                    }

                }, true);
            }
        }

        /**
         * Manage each modal's layout
         * @param {Object} gui_options
         */
        var _guiManager = function (gui_options, only_consent_modal) {

            // If gui_options is not object => exit
            if (typeof gui_options !== 'object') return;

            var consent_modal_options = gui_options['consent_modal'];
            var settings_modal_options = gui_options['settings_modal'];

            /**
             * Helper function which adds layout and
             * position classes to given modal
             *
             * @param {HTMLElement} modal
             * @param {string[]} allowed_layouts
             * @param {string[]} allowed_positions
             * @param {string} layout
             * @param {string[]} position
             */
            function _setLayout(modal, allowed_layouts, allowed_positions, allowed_transitions, layout, position, transition) {
                position = (position && position.split(" ")) || [];
                // Check if specified layout is valid
                if (_inArray(allowed_layouts, layout) > -1) {

                    // Add layout classes
                    _addClass(modal, layout);

                    // Add position class (if specified)
                    if (!(layout === 'bar' && position[0] === 'middle') && _inArray(allowed_positions, position[0]) > -1) {
                        for (var i = 0; i < position.length; i++) {
                            _addClass(modal, position[i]);
                        }
                    }
                }

                // Add transition class
                (_inArray(allowed_transitions, transition) > -1) && _addClass(modal, transition);
            }

            if (consent_modal_exists && consent_modal_options) {
                _setLayout(
                    all_modals_container.querySelector("#cm"),
                    ['box', 'bar', 'cloud'],
                    ['top', 'middle', 'bottom'],
                    ['zoom', 'slide'],
                    consent_modal_options['layout'],
                    consent_modal_options['position'],
                    consent_modal_options['transition']
                );
            }

            if (!only_consent_modal && settings_modal_options) {
                _setLayout(
                    all_modals_container.querySelector("#s-cnt"),
                    ['bar'],
                    ['left', 'right'],
                    ['zoom', 'slide'],
                    settings_modal_options['layout'],
                    settings_modal_options['position'],
                    settings_modal_options['transition']
                );
            }
        }

        /**
         * Returns true if cookie category is accepted by the user
         * @param {string} cookie_category
         * @returns {boolean}
         */
        _cookieconsent.allowedCategory = function (cookie_category) {

            if (!invalid_consent || _config.mode === 'opt-in')
                var allowed_categories = JSON.parse(_getCookie(_config.cookie_name, 'one', true) || '{}')['categories'] || []
            else  // mode is 'opt-out'
                var allowed_categories = default_enabled_categories;

            return _inArray(allowed_categories, cookie_category) > -1;
        }

        /**
         * "Init" method. Will run once and only if modals do not exist
         */
        _cookieconsent.run = function (user_config) {

            if (!document.getElementById('cc_div')) {

                // configure all parameters
                _setConfig(user_config);

                // if is bot, don't run plugin
                if (is_bot) return;

                // Retrieve cookie value (if set)
                saved_cookie_content = JSON.parse(_getCookie(_config.cookie_name, 'one', true) || "{}");


                // Retrieve "consent_uuid"
                consent_uuid = saved_cookie_content['consent_uuid'];

                // If "consent_uuid" is present => assume that consent was previously given
                var cookie_consent_accepted = consent_uuid !== undefined;

                // Retrieve "consent_date"
                consent_date = saved_cookie_content['consent_date'];
                consent_date && (consent_date = new Date(consent_date));

                // Retrieve "last_consent_update"
                last_consent_update = saved_cookie_content['last_consent_update'];
                last_consent_update && (last_consent_update = new Date(last_consent_update));

                // Retrieve "data"
                cookie_data = saved_cookie_content['data'] !== undefined ? saved_cookie_content['data'] : null;

                // If revision is enabled and current value !== saved value inside the cookie => revision is not valid
                if(typeof saved_cookie_content['revision'] !== "undefined" && revision_enabled && saved_cookie_content['revision'] !== _config.revision){
                    //user has a cookie
                    valid_revision = false;
                }else if(saved_cookie_content['revision'] !== _config.revision && typeof saved_cookie_content['revision'] !== "undefined"){
                    valid_revision = true;
                }

                // If consent is not valid => create consent modal
                consent_modal_exists = invalid_consent = (!cookie_consent_accepted || !valid_revision || !consent_date || !last_consent_update || !consent_uuid);

                // Generate cookie-settings dom (& consent modal)
                _createCookieConsentHTML();

                  _getModalFocusableData();

                _guiManager(user_config['gui_options']);
                _addDataButtonListeners();

                if (_config.autorun && consent_modal_exists) {
                    _cookieconsent.show(user_config['delay'] || 0);
                }

                // Add class to enable animations/transitions
                setTimeout(function () {
                    _addClass(main_container, 'c--anim');
                }, 30);

                // Accessibility :=> if tab pressed => trap focus inside modal
                setTimeout(function () {
                    _handleFocusTrap();
                }, 100);

                // If consent is valid
                if (!invalid_consent) {
                    var rfc_prop_exists = typeof saved_cookie_content['rfc_cookie'] === "boolean";

                    /*
                     * Convert cookie to rfc format (if `use_rfc_cookie` is enabled)
                     */
                    if (!rfc_prop_exists || (rfc_prop_exists && saved_cookie_content['rfc_cookie'] !== _config.use_rfc_cookie)) {
                        saved_cookie_content['rfc_cookie'] = _config.use_rfc_cookie;
                        _setCookie(_config.cookie_name, JSON.stringify(saved_cookie_content));
                    }

                    /**
                     * Update accept type
                     */
                    accept_type = _getAcceptType(_getCurrentCategoriesState());

                    _manageExistingScripts();

                    if (typeof onAccept === 'function')
                        onAccept(saved_cookie_content);

                    _log("CookieConsent [NOTICE]: consent already given!", saved_cookie_content);

                } else {
                    if (_config.mode === 'opt-out') {
                        _log("CookieConsent [CONFIG] mode='" + _config.mode + "', default enabled categories:", default_enabled_categories);
                        _manageExistingScripts(default_enabled_categories);
                    }
                    _log("CookieConsent [NOTICE]: ask for consent!");
                }
            } else {
                _log("CookieConsent [NOTICE]: cookie consent already attached to body!");
            }
        }

        /**
         * Show settings modal (with optional delay)
         * @param {number} delay
         */
        _cookieconsent.showSettings = function (delay) {
            setTimeout(function () {
                _addClass(html_dom, "show--settings");
                all_modals_container.querySelector("#s-cnt").setAttribute('aria-hidden', 'false');
                settings_modal_visible = true;
                /**
                 * Set focus to the first focusable element inside settings modal
                 */
                setTimeout(function () {
                    // If there is no consent-modal, keep track of the last focused elem.
                    if (!consent_modal_visible) {
                        last_elem_before_modal = document.activeElement;
                    } else {
                        last_consent_modal_btn_focus = document.activeElement;
                    }

                    if (settings_modal_focusable.length === 0) return;

                    if (settings_modal_focusable[3]) {
                        settings_modal_focusable[3].focus();
                    } else {
                        settings_modal_focusable[0].focus();
                    }
                    current_modal_focusable = settings_modal_focusable;
                }, 200);

                _log("CookieConsent [SETTINGS]: show settings_modal");
            }, delay > 0 ? delay : 0);
        }

        /**
         * This function handles the loading/activation logic of the already
         * existing scripts based on the current accepted cookie categories
         *
         * @param {string[]} [must_enable_categories]
         */
        var _manageExistingScripts = function (must_enable_categories) {

            if (!_config.page_scripts) return;

            // get all the scripts with "cookie-category" attribute
            var scripts = document.querySelectorAll('script[' + _config.script_selector + ']');
            var accepted_categories = must_enable_categories || saved_cookie_content['categories'] || [];

            /**
             * Load scripts (sequentially), using a recursive function
             * which loops through the scripts array
             * @param {Element[]} scripts scripts to load
             * @param {number} index current script to load
             */
            var _loadScripts = function (scripts, index) {
                if (index < scripts.length) {

                    var curr_script = scripts[index];
                    var curr_script_category = curr_script.getAttribute(_config.script_selector);

                    /**
                     * If current script's category is on the array of categories
                     * accepted by the user => load script
                     */

                    if (_inArray(accepted_categories, curr_script_category) > -1) {

                        curr_script.type = 'text/javascript';
                        curr_script.removeAttribute(_config.script_selector);

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

                        /**
                         * If we managed to get here and scr is still set, it means that
                         * the script is loading/loaded sequentially so don't go any further
                         */
                        if (src) return;
                    }

                    // Go to next script right away
                    _loadScripts(scripts, ++index);
                }
            }

            _loadScripts(scripts, 0);
        }

        /**
         * Save custom data inside cookie
         * @param {object|string} new_data
         * @param {string} [mode]
         * @returns {boolean}
         */
        var _setCookieData = function (new_data, mode) {

            var set = false;
            /**
             * If mode is 'update':
             * add/update only the specified props.
             */
            if (mode === 'update') {
                cookie_data = _cookieconsent.get('data');
                var same_type = typeof cookie_data === typeof new_data;

                if (same_type && typeof cookie_data === "object") {
                    !cookie_data && (cookie_data = {});

                    for (var prop in new_data) {
                        if (cookie_data[prop] !== new_data[prop]) {
                            cookie_data[prop] = new_data[prop]
                            set = true;
                        }
                    }
                } else if ((same_type || !cookie_data) && cookie_data !== new_data) {
                    cookie_data = new_data;
                    set = true;
                }
            } else {
                cookie_data = new_data;
                set = true;
            }

            if (set) {
                saved_cookie_content['data'] = cookie_data;
                _setCookie(_config.cookie_name, JSON.stringify(saved_cookie_content));
            }

            return set;
        }

        /**
         * Helper method to set a variety of fields
         * @param {string} field
         * @param {object} data
         * @returns {boolean}
         */
        _cookieconsent.set = function (field, data) {
            switch (field) {
                case 'data':
                    return _setCookieData(data['value'], data['mode']);
                default:
                    return false;
            }
        }

        /**
         * Retrieve data from existing cookie
         * @param {string} field
         * @param {string} [cookie_name]
         * @returns {any}
         */
        _cookieconsent.get = function (field, cookie_name) {
            var cookie = JSON.parse(_getCookie(cookie_name || _config.cookie_name, 'one', true) || "{}");

            return cookie[field];
        }

        /**
         * Read current configuration value
         * @returns {any}
         */
        _cookieconsent.getConfig = function (field) {
            return _config[field] || user_config[field];
        }

        /**
         * Obtain accepted and rejected categories
         * @returns {{accepted: string[], rejected: string[]}}
         */
        var _getCurrentCategoriesState = function () {

            // get accepted categories
            accepted_categories = saved_cookie_content['categories'] || [];

            // calculate rejected categories (all_categories - accepted_categories)
            rejected_categories = all_categories.filter(function (category) {
                return (_inArray(accepted_categories, category) === -1);
            });

            return {
                accepted: accepted_categories,
                rejected: rejected_categories
            }
        }

        /**
         * Calculate "accept type" given current categories state
         * @param {{accepted: string[], rejected: string[]}} currentCategoriesState
         * @returns {string}
         */
        var _getAcceptType = function (currentCategoriesState) {

            var type = 'custom';

            // number of categories marked as necessary/readonly
            var necessary_categories_length = readonly_categories.filter(function (readonly) {
                return readonly === true;
            }).length;

            // calculate accept type based on accepted/rejected categories
            if (currentCategoriesState.accepted.length === all_categories.length)
                type = 'all';
            else if (currentCategoriesState.accepted.length === necessary_categories_length)
                type = 'necessary'

            return type;
        }

        /**
         * @typedef {object} userPreferences
         * @property {string} accept_type
         * @property {string[]} accepted_categories
         * @property {string[]} rejected_categories
         */

        /**
         * Retrieve current user preferences (summary)
         * @returns {userPreferences}
         */
        _cookieconsent.getUserPreferences = function () {
            var currentCategoriesState = _getCurrentCategoriesState();
            var accept_type = _getAcceptType(currentCategoriesState);

            return {
                'accept_type': accept_type,
                'accepted_categories': currentCategoriesState.accepted,
                'rejected_categories': currentCategoriesState.rejected
            }
        }

        /**
         * Function which will run after script load
         * @callback scriptLoaded
         */

        /**
         * Dynamically load script (append to head)
         * @param {string} src
         * @param {scriptLoaded} callback
         * @param {object[]} [attrs] Custom attributes
         */
        _cookieconsent.loadScript = function (src, callback, attrs) {

            var function_defined = typeof callback === 'function';

            // Load script only if not already loaded
            if (!document.querySelector('script[src="' + src + '"]')) {

                var script = _createNode('script');

                // if an array is provided => add custom attributes
                if (attrs && attrs.length > 0) {
                    for (var i = 0; i < attrs.length; ++i) {
                        attrs[i] && script.setAttribute(attrs[i]['name'], attrs[i]['value']);
                    }
                }

                // if callback function defined => run callback onload
                if (function_defined) {
                    script.onload = callback;
                }

                script.src = src;

                /**
                 * Append script to head
                 */
                document.head.appendChild(script);
            } else {
                function_defined && callback();
            }
        }

        /**
         * Manage dynamically loaded scripts: https://github.com/orestbida/cookieconsent/issues/101
         * If plugin has already run, call this method to enable
         * the newly added scripts based on currently selected preferences
         */
        _cookieconsent.updateScripts = function () {
            _manageExistingScripts();
        }

        /**
         * Show cookie consent modal (with delay parameter)
         * @param {number} [delay]
         * @param {boolean} [create_modal] create modal if it doesn't exist
         */
        _cookieconsent.show = function (delay, create_modal) {

            if (create_modal === true)
                _createConsentModal(_config.current_lang);

            if (consent_modal_exists) {
                setTimeout(function () {
                    _addClass(html_dom, "show--consent");

                    /**
                     * Update attributes/internal statuses
                     */
                    all_modals_container.querySelector("#cm").setAttribute('aria-hidden', 'false');
                    consent_modal_visible = true;

                    setTimeout(function () {
                        last_elem_before_modal = document.activeElement;
                        current_modal_focusable = consent_modal_focusable;
                    }, 200);

                    _log("CookieConsent [MODAL]: show consent_modal");
                }, delay > 0 ? delay : (create_modal ? 30 : 0));
            }
        }

        /**
         * Hide consent modal
         */
        _cookieconsent.hide = function () {
            if (consent_modal_exists) {
                _removeClass(html_dom, "show--consent");
                all_modals_container.querySelector("#cm").setAttribute('aria-hidden', 'true');
                consent_modal_visible = false;

                setTimeout(function () {
                    //restore focus to the last page element which had focus before modal opening
                    last_elem_before_modal.focus();
                    current_modal_focusable = null;
                }, 200);

                _log("CookieConsent [MODAL]: hide");
            }
        }

        /**
         * Hide settings modal
         */
        _cookieconsent.hideSettings = function () {
            _removeClass(html_dom, "show--settings");
            settings_modal_visible = false;
            all_modals_container.querySelector("#s-cnt").setAttribute('aria-hidden', 'true');


            setTimeout(function () {
                /**
                 * If consent modal is visible, focus him (instead of page document)
                 */
                if (consent_modal_visible) {
                    last_consent_modal_btn_focus && last_consent_modal_btn_focus.focus();
                    current_modal_focusable = consent_modal_focusable;
                } else {
                    /**
                     * Restore focus to last page element which had focus before modal opening
                     */
                    last_elem_before_modal && last_elem_before_modal.focus();
                    current_modal_focusable = null;
                }

                clicked_inside_modal = false;
            }, 200);

            _log("CookieConsent [SETTINGS]: hide settings_modal");
        }

        /**
         * Accept cookieconsent function API
         * @param {string[]|string} _categories - Categories to accept
         * @param {string[]} [_exclusions] - Excluded categories [optional]
         */
        _cookieconsent.accept = function (_categories, _exclusions) {
            var categories = _categories || undefined;
            var exclusions = _exclusions || [];
            var to_accept = [];

            /**
             * Get all accepted categories
             * @returns {string[]}
             */
            var _getCurrentPreferences = function () {
                var toggles = document.querySelectorAll('.c-tgl') || [];
                var states = [];

                for (var i = 0; i < toggles.length; i++) {
                    if (toggles[i].checked) {
                        states.push(toggles[i].value);
                    }
                }
                return states;
            }

            if (!categories) {
                to_accept = _getCurrentPreferences();
            } else {
                if (
                    typeof categories === "object" &&
                    typeof categories.length === "number"
                ) {
                    for (var i = 0; i < categories.length; i++) {
                        if (_inArray(all_categories, categories[i]) !== -1)
                            to_accept.push(categories[i]);
                    }
                } else if (typeof categories === "string") {
                    if (categories === 'all')
                        to_accept = all_categories.slice();
                    else {
                        if (_inArray(all_categories, categories) !== -1)
                            to_accept.push(categories);
                    }
                }
            }

            // Remove excluded categories
            if (exclusions.length >= 1) {
                for (i = 0; i < exclusions.length; i++) {
                    to_accept = to_accept.filter(function (item) {
                        return item !== exclusions[i]
                    })
                }
            }

            // Add back all the categories set as "readonly/required"
            for (i = 0; i < all_categories.length; i++) {
                if (
                    readonly_categories[i] === true &&
                    _inArray(to_accept, all_categories[i]) === -1
                ) {
                    to_accept.push(all_categories[i]);
                }
            }

            _saveCookiePreferences(to_accept);
        }

        /**
         * API function to easily erase cookies
         * @param {(string|string[])} _cookies
         * @param {string} [_path] - optional
         * @param {string} [_domain] - optional
         */
        _cookieconsent.eraseCookies = function (_cookies, _path, _domain) {
            var cookies = [];
            var domains = _domain
                ? [_domain, "." + _domain]
                : [_config.cookie_domain, "." + _config.cookie_domain];

            if (typeof _cookies === "object" && _cookies.length > 0) {
                for (var i = 0; i < _cookies.length; i++) {
                    this.validCookie(_cookies[i]) && cookies.push(_cookies[i]);
                }
            } else {
                this.validCookie(_cookies) && cookies.push(_cookies);
            }

            _eraseCookies(cookies, _path, domains);
        }

        /**
         * Set cookie, by specifying name and value
         * @param {string} name
         * @param {string} value
         */
        var _setCookie = function (name, value) {

            var cookie_expiration = _config.cookie_expiration;

            if (typeof _config.cookie_necessary_only_expiration === 'number' && accept_type === 'necessary')
                cookie_expiration = _config.cookie_necessary_only_expiration;

            value = _config.use_rfc_cookie ? encodeURIComponent(value) : value;

            var date = new Date();
            date.setTime(date.getTime() + (1000 * (cookie_expiration * 24 * 60 * 60)));
            var expires = "; expires=" + date.toUTCString();

            var cookieStr = name + "=" + (value || "") + expires + "; Path=" + _config.cookie_path + ";";
            cookieStr += " SameSite=" + _config.cookie_same_site + ";";

            // assures cookie works with localhost (=> don't specify domain if on localhost)
            if (window.location.hostname.indexOf(".") > -1) {
                cookieStr += " Domain=" + _config.cookie_domain + ";";
            }

            if (window.location.protocol === "https:") {
                cookieStr += " Secure;";
            }

            document.cookie = cookieStr;

            _log("CookieConsent [SET_COOKIE]: '" + name + "' expires after " + cookie_expiration + " day(s)");
        }

        /**
         * Get cookie value by name,
         * returns the cookie value if found (or an array
         * of cookies if filter provided), otherwise empty string: ""
         * @param {string} name
         * @param {string} filter 'one' or 'all'
         * @param {boolean} [get_value] set to true to obtain its value
         * @returns {string|string[]}
         */
        var _getCookie = function (name, filter, get_value) {
            var found;

            if (filter === 'one') {
                found = document.cookie.match("(^|;)\\s*" + name + "\\s*=\\s*([^;]+)");
                found = found ? (get_value ? found.pop() : name) : "";

                if (found && name === _config.cookie_name) {
                    try {
                        found = JSON.parse(found)
                    } catch (e) {
                        try {
                            found = JSON.parse(decodeURIComponent(found))
                        } catch (e) {
                            // if I got here => cookie value is not a valid json string
                            found = {};
                        }
                    }
                    found = JSON.stringify(found);
                } else {
                    //Undetected Cookies
                }
            } else if (filter === 'all') {
                // array of names of all existing cookies
                var cookies = document.cookie.split(/;\s*/);
                found = [];
                for (var i = 0; i < cookies.length; i++) {
                    found.push(cookies[i].split("=")[0]);
                }
            }

            return found;
        }

        /**
         * Delete cookie by name & path
         * @param {string[]} cookies
         * @param {string} [custom_path] - optional
         * @param {string[]} domains - example: ['domain.com', '.domain.com']
         */
        var _eraseCookies = function (cookies, custom_path, domains) {
            var path = custom_path ? custom_path : '/';
            var expires = 'Expires=Thu, 01 Jan 1970 00:00:01 GMT;';

            for (var i = 0; i < cookies.length; i++) {
                for (var j = 0; j < domains.length; j++) {
                    var d = window.location.hostname.split(".");
                    while (d.length > 0) {
                        var cookieBase = encodeURIComponent(cookies[i].split(";")[0].split("=")[0]) + '=; expires=Thu, 01-Jan-1970 00:00:01 GMT; domain=' + d.join('.') + ' ;path=';
                        var p = location.pathname.split('/');
                        document.cookie = cookieBase + '/';
                        while (p.length > 0) {
                            document.cookie = cookieBase + p.join('/');
                            p.pop();
                        };
                        d.shift();
                    }
                    document.cookie = cookies[i] + '=; path=' + path + (domains[j].indexOf('.') == 0 ? '; domain=' + domains[j] : "") + '; ' + expires;
                }
                _log("CookieConsent [AUTOCLEAR]: deleting cookie: '" + cookies[i] + "' path: '" + path + "' domain:", domains);
            }
        }

        /**
         * Returns true if cookie was found and has valid value (not empty string)
         * @param {string} cookie_name
         * @returns {boolean}
         */
        _cookieconsent.validCookie = function (cookie_name) {
            return _getCookie(cookie_name, 'one', true) !== "";
        }

        /**
         * Function to run when event is fired
         * @callback eventFired
         */

        /**
         * Add event listener to dom object (cross browser function)
         * @param {Element} elem
         * @param {string} event
         * @param {eventFired} fn
         * @param {boolean} [isPassive]
         */
        var _addEvent = function (elem, event, fn, isPassive) {
            elem.addEventListener(event, fn, isPassive === true ? {passive: true} : false);
        }

        /**
         * Get all prop. keys defined inside object
         * @param {Object} obj
         */
        var _getKeys = function (obj) {
            if (typeof obj === "object") {
                return Object.keys(obj);
            }
        }

        /**
         * Append class to the specified dom element
         * @param {HTMLElement} elem
         * @param {string} classname
         */
        var _addClass = function (elem, classname) {
            if(typeof elem !== "undefined"){
                elem.classList.add(classname);
            }
        }

        /**
         * Remove specified class from dom element
         * @param {HTMLElement} elem
         * @param {string} classname
         */
        var _removeClass = function (el, className) {
            el.classList.remove(className);
        }

        /**
         * Check if html element has class
         * @param {HTMLElement} el
         * @param {string} className
         */
        var _hasClass = function (el, className) {
            return el.classList.contains(className);
        }


        /**
         * Replace all link placeholders in the cookiemanager temp html
         * @param token
         * @param link
         */
        function _replaceLink(token, link) {
            for (const element of all_modals_container.querySelectorAll("#s-cnt *, #cm *")) {
                if (element.textContent.includes(token)) {
                    element.innerHTML = element.innerHTML.replace(token, link);
                }
            }
        }


        return _cookieconsent;
    };

    var init = 'initCookieConsent';
    /**
     * Make CookieConsent object accessible globally
     */
    if (typeof window !== 'undefined' && typeof window[init] !== 'function') {
        window[init] = CookieConsent
    }
})();