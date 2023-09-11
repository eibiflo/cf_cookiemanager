.. include:: ../Includes.txt


===========================
Environment Tools
===========================


Examples of runTests.sh
----------

.. code-block:: raw

    - Install a TYPO3 11 with PHP 7.4:
     ./Build/Scripts/runTests.sh -s composerInstall -p 7.4 -t 11


    - Install a TYPO3 12 with PHP 8.1:
     ./Build/Scripts/runTests.sh -s composerInstall -p 8.1 -t 12


    -  Unit-Tests with PHP 7.4:
     ./Build/Scripts/runTests.sh -s unit


    -  Functional-Tests with PHP 8.1:
     ./Build/Scripts/runTests.sh -s unit -p 8.1


    - Run acceptance Tests with PHP 8.1 for TYPO3 12:
     ./Build/Scripts/runTests.sh -s acceptance -p 8.1 -t 12



Development Environment
-----------------------

You can use the Acceptance Tests docker-compose file to setup a development environment for quick testing.

``cd Build/testing-docker`` folder and run: ``docker-compose run acceptance_test``
This Launches a container with your runTests.sh install parameters and Setup a basic Frontend and Backend.

Add the following to your ``/etc/hosts`` file: ``127.0.0.1 web``

- Login to the Backend: http://web/typo3temp/var/tests/acceptance/typo3
- Credentials are: ``admin`` / ``password``

Frontend is available at: http://web/typo3temp/var/tests/acceptance/

