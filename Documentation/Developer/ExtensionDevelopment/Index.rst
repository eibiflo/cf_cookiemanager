

===========================
Environment Tools
===========================

This section describes the tools and scripts available for Contributors to set up their development environment, run tests, and maintain code quality.

Documentation Rendering
-----------------------

.. code-block:: bash

    # Render Documentation
    docker run --rm -it --pull always \
        -v "./Documentation:/project/Documentation" \
        -v "./Documentation-GENERATED-temp:/project/Documentation-GENERATED-temp" \
        -p 1337:1337 \
        ghcr.io/typo3-documentation/render-guides:latest --config="Documentation" --watch


Running Tests
-------------

All test suites run inside containers via ``Build/Scripts/runTests.sh``.

**Prerequisites:**

.. code-block:: bash

    # Install dependencies first (only needed once or after composer.json changes, or version switch):

    ./Build/Scripts/runTests.sh -s composerUpdate -t 13

    ./Build/Scripts/runTests.sh -s composerUpdate -t 14

Unit Tests
^^^^^^^^^^

Dependend on your Installation command, you can run unit tests with the following command. By default, it runs with Typo3 13, but you can specify a different version with the ``-t`` option.

.. code-block:: bash

    ./Build/Scripts/runTests.sh -s unit -t 14

    # With a specific PHP version:
    ./Build/Scripts/runTests.sh -s unit -p 8.4 -t 14

    # Run a single test file:
    ./Build/Scripts/runTests.sh -s unit Tests/Unit/Service/ScanServiceTest.php -t 14

    # With Xdebug:
    ./Build/Scripts/runTests.sh -s unit -x -t 14

Functional Tests
^^^^^^^^^^^^^^^^

.. code-block:: bash

    # SQLite (default, fastest):
    ./Build/Scripts/runTests.sh -s functional

    # MariaDB:
    ./Build/Scripts/runTests.sh -s functional -d mariadb

    # MySQL 8.4:
    ./Build/Scripts/runTests.sh -s functional -d mysql -j 8.4

    # PostgreSQL:
    ./Build/Scripts/runTests.sh -s functional -d postgres

Acceptance Tests
^^^^^^^^^^^^^^^^

Acceptance tests use Codeception with Selenium Chrome to test the TYPO3 backend
and frontend through a real browser. The script starts three containers
(Selenium Chrome, PHP-FPM, Apache) and runs the test suite against them.
Apache port 80 is published to the host as port 8080 for manual inspection.

.. code-block:: bash

    # Run all acceptance tests (SQLite, headless Chrome):
    ./Build/Scripts/runTests.sh -s acceptance

    # With MariaDB:
    ./Build/Scripts/runTests.sh -s acceptance -d mariadb

    # Run a specific test file:
    ./Build/Scripts/runTests.sh -s acceptance Tests/Acceptance/Backend/Dashboard/SettingsBackendModuleCest.php

    # With Xdebug:
    ./Build/Scripts/runTests.sh -s acceptance -x

Test reports are written to ``.Build/public/typo3temp/var/tests/AcceptanceReports/``.

Port 8080 is published to the host, so while the containers are running you can
inspect the test environment in your browser:

- Frontend: http://localhost:8080/
- Backend: http://localhost:8080/typo3 (credentials: ``admin`` / ``password``)

Code Quality
^^^^^^^^^^^^

.. code-block:: bash

    # PHP linting:
    ./Build/Scripts/runTests.sh -s lint

    # PHPStan static analysis:
    ./Build/Scripts/runTests.sh -s phpstan

    # Coding guidelines check (dry-run):
    ./Build/Scripts/runTests.sh -s cgl -n

    # Coding guidelines fix:
    ./Build/Scripts/runTests.sh -s cgl

Common Options
^^^^^^^^^^^^^^

``-p <version>``
    PHP version: ``8.2``, ``8.3``, ``8.4``, ``8.5`` (default: ``8.3``)

``-d <dbms>``
    Database: ``sqlite``, ``mariadb``, ``mysql``, ``postgres`` (default: ``sqlite``)

``-b <docker|podman>``
    Container runtime (default: auto-detect)

``-x``
    Enable Xdebug (port 9003)

``-e "<options>"``
    Pass extra options to the underlying tool (phpunit, codecept, composer)

``-v``
    Verbose output

``-u``
    Update container images to latest versions

``-h``
    Show full help text

