.. include:: ../Includes.txt


===========================
JavaScript API
===========================


the following methods are available:

- `cc.show(<optional_delay>, <create_modal>)`
- `cc.hide()`
- `cc.showSettings(<optional_delay>)`
- `cc.hideSettings()`


- `cc.accept(<accepted_categories>, <optional_rejected_categories>)`
   * `accepted_categories`: string or string[]
   * `rejected_categories`: string[] - optional

Note: **all categories marked as `readonly` will ALWAYS be enabled/accepted regardless of the categories provided inside the `.accept()` API call.

.. code-block:: javascript

    cc.accept('all');                // accept all categories
    cc.accept([]);                   // accept none (reject all)
    cc.accept('analytics');          // accept only analytics category
    cc.accept(['cat_1', 'cat_2']);   // accept only these 2 categories
    cc.accept();                     // accept all currently selected categories inside modal

    cc.accept('all', ['analytics']); // accept all except "analytics" category
    cc.accept('all', ['cat_1', 'cat_2']); // accept all except these 2 categories

How to later reject a specific category (cookieconsent already accepted)? Same as above:

.. code-block:: javascript

    cc.accept('all', ['targeting']);     // opt out of targeting category

- `cc.allowedCategory(<category_name>)`

    Note: there are no default cookie categories, you create them!

    A cookie category corresponds to the string of the `value` property inside the `toggle` object:

    .. code-block:: javascript

        // ...
        toggle: {
            value: 'analytics',     // cookie category
            enabled: false,         // default status
            readonly: false         // allow to enable/disable
            // reload: 'on_disable',   // allows to reload page when the current cookie category is deselected
        }
        // ...

    Example:

    .. code-block:: javascript

        // Check if user accepts cookie consent with analytics category enabled
        if (cc.allowedCategory('analytics')) {
            // yoo, you might want to load analytics.js ...
        };

- `cc.validCookie(<cookie_name>)`

    If cookie exists and has non-empty ('') value => return true, otherwise false.

    .. code-block:: javascript

        // Example: check if '_gid' cookie is set
        if (!cc.validCookie('_gid')) {
            // yoo, _gid cookie is not set, do something ...
        };

- `cc.eraseCookies(<cookie_names>, <optional_path>, <optional_domains>)`

    - cookie_names: string[]
    - path: string - optional
    - domains: string[] - optional

    Examples:

    .. code-block:: javascript

        cc.eraseCookies(['cc_cookies']);             // erase "cc_cookie" if it exists
        cc.eraseCookies(['cookie1', 'cookie2']);     // erase these 2 cookies

        cc.eraseCookies(['cc_cookie'], "/demo");
        cc.eraseCookies(['cc_cookie'], "/demo", [location.hostname]);

- `cc.loadScript(<path>, <callback_function>, <optional_custom_attributes>)`

    Basic example:

    .. code-block:: javascript

        cc.loadScript('https://www.google-analytics.com/analytics.js', function(){
            // Script loaded, do something
        });

    How to load scripts with custom attributes:

    .. code-block:: javascript

        cc.loadScript('https://www.google-analytics.com/analytics.js', function(){
            // Script loaded, do something
        }, [
            {name: 'id', value: 'ga_id'},
            {name: 'another-attribute', value: 'value'}
        ]);

- `cc.set(<field>, <object>)`

    The `.set()` method allows you to set the following values:
    - data (used to save custom data inside the plugin's cookie)
    - revision

    How to save custom data:

    .. code-block:: javascript

        // Set cookie's "data" field to whatever the value of the `value` prop. is
        cc.set('data', {value: {id: 21, country: "italy"}});

        // Only add/update the specified props.
        cc.set('data', {value: {id: 22, new_prop: 'new prop value'}, mode: 'update'});

- `cc.get(<field>)`

    The `.get()` method allows you to retrieve any of the fields inside the plugin's cookie:

    .. code-block:: javascript

        cc.get('level');     // retrieve all accepted categories (if cookie exists)
        cc.get('data');      // retrieve custom data (if cookie exists)
        cc.get('revision');  // retrieve revision number (if cookie exists)

- `cc.getConfig(<field>)`

    The `.getConfig()` method allows you to read configuration options from the current instance:

    .. code-block:: javascript

        cc.getConfig('current_lang');        // get currently used language
        cc.getConfig('cookie_expiration');   // get configured cookie expiration
        // ...

- `cc.getUserPreferences()`

    The `.getUserPreferences()` returns the following object (for analytics/logging purposes):

    .. code-block:: javascript

        {
            accept_type: string,            // 'all', 'necessary', 'custom'
            accepted_categories: string[],  // e.g. ['necessary', 'analytics']
            rejected_categories: string[]   // e.g. ['ads']
        }

- `cc.updateScripts()`

    This method allows the plugin to manage dynamically added/injected scripts that have been loaded after the plugin's execution.

    E.g. dynamic content generated by server-side languages like php, node, ruby ...