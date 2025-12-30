

===========================
EventDispatcher (PSR-14 Events)
===========================


Register ClassifyContent Event Listener
------------------------------------------

1. Open your Services.yaml or Services.php file from your Extension.

2. Add the following code snippet to register the `ClassifyContent` event listener:

.. code-block:: php

    YourVendor\YourSitepackage\EventListner\ClassifyContent:
      tags:
        - name: event.listener


Handling the 'ClassifyContentEvent'
------------------------------

The `ClassifyContent` event listener handles the `ClassifyContentEvent` and performs custom logic.

1. Create a new PHP class in your TYPO3 extension, e.g., `EXT:your_sitepackage/Classes/EventListner/ClassifyContent.php`.

3. Implement the `__invoke` method in the class, which will handle the event. The method should accept a `ClassifyContentEvent` object as a parameter.

.. code-block:: php

    namespace YourVendor\YourSitepackage\EventListner;

    use CodingFreaks\CfCookiemanager\Event\ClassifyContentEvent;
    use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

    class ClassifyContent
    {
        /**
         * Handle the ClassifyContentEvent, handle the provider URL and set the service identifier.
         *
         * @param ClassifyContentEvent $event The ClassifyContentEvent object
         */
        public function __invoke(ClassifyContentEvent $event): void
        {
            // Custom logic goes here

            // Example: Dump a debug message
            // DebuggerUtility::var_dump("SitePackage invoke");

            // Example: Access the provider URL
            // DebuggerUtility::var_dump($event->getProviderURL());

            // Example: Set the service identifier
            // $event->setServiceIdentifier("TESTServiceIdentifier");
        }
    }

.. Tip::
    Please note that you need to replace: YourVendor\YourSitepackage\ with the correct namespaces for your extension.