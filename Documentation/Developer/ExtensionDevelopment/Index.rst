

===========================
Environment Tools
===========================


Examples of runTests.sh
----------

.. code-block:: bash

    # Install TYPO3 13 with PHP 8.3:
    ./Build/Scripts/runTests.sh -s composerUpdate -p 8.3 -t 13

    # Install TYPO3 14 with PHP 8.3:
    ./Build/Scripts/runTests.sh -s composerUpdate -p 8.3 -t 14

    # Run Unit-Tests:
    ./Build/Scripts/runTests.sh -s unit

    # Run Functional-Tests with PHP 8.3:
    ./Build/Scripts/runTests.sh -s functional -p 8.3

    - Render Documentation
    docker run --rm -it --pull always \
        -v "./Documentation:/project/Documentation" \
        -v "./Documentation-GENERATED-temp:/project/Documentation-GENERATED-temp" \
        -p 1337:1337 \
        ghcr.io/typo3-documentation/render-guides:latest --config="Documentation" --watch


     ddev exec -d /var/www/html/packages/cf_cookiemanager .Build/bin/phpstan analyse -c phpstan.neon


Development Environment
-----------------------

You can use the Acceptance Tests docker-compose file to setup a development environment for quick testing.

``cd Build/testing-docker`` folder and run: ``docker-compose run acceptance_test``
This Launches a container with your runTests.sh install parameters and Setup a basic Frontend and Backend.

Add the following to your ``/etc/hosts`` file: ``127.0.0.1 web``

- Login to the Backend: http://web/typo3temp/var/tests/acceptance/typo3
- Credentials are: ``admin`` / ``password``

Frontend is available at: http://web/typo3temp/var/tests/acceptance/

