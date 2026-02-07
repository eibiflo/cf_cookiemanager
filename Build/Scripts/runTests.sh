#!/usr/bin/env bash

#
# cf_cookiemanager test runner based on docker/podman.
#
# Modernized version based on TYPO3 Kickstarter approach.
# Executes tests in containers without requiring docker-compose.
#

set -e

# Extension information
EXTENSION_KEY="cf_cookiemanager"
COMPOSER_ROOT_VERSION="3.0.x-dev"

# Script directory and root path
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_PATH="$(cd "${SCRIPT_PATH}/../../" && pwd)"

# CI detection (GitHub Actions, GitLab CI, etc.)
IS_CI=0
IMAGE_PREFIX="ghcr.io/typo3/"

if [ "${CI}" == "true" ]; then
    IS_CI=1
fi

# TTY detection - only use -it if stdin is a terminal
CONTAINER_INTERACTIVE=""
if [ -t 0 ]; then
    CONTAINER_INTERACTIVE="-it"
fi

# User ID for container (prevents permission issues)
# Always use host UID on Linux to match file ownership
HOST_UID=$(id -u)
USERSET=""
if [ "$(uname)" != "Darwin" ]; then
    USERSET="--user ${HOST_UID}"
fi

# Cleanup function - removes containers and network
cleanUp() {
    local NETWORK="${1}"
    local CONTAINER_BIN="${2}"

    # Remove containers attached to network
    ATTACHED_CONTAINERS=$(${CONTAINER_BIN} ps --filter network=${NETWORK} --format='{{.Names}}' 2>/dev/null || true)
    if [ -n "${ATTACHED_CONTAINERS}" ]; then
        echo "Stopping containers..."
        ${CONTAINER_BIN} stop ${ATTACHED_CONTAINERS} >/dev/null 2>&1 || true
        ${CONTAINER_BIN} rm -f ${ATTACHED_CONTAINERS} >/dev/null 2>&1 || true
    fi

    # Remove network
    ${CONTAINER_BIN} network rm "${NETWORK}" >/dev/null 2>&1 || true
}

# Wait for database to be ready
waitForDatabase() {
    local CONTAINER_BIN="${1}"
    local NETWORK="${2}"
    local DB_HOST="${3}"
    local DB_PORT="${4}"
    local CONTAINER_IMAGE="${5}"
    local MAX_ATTEMPTS=30
    local ATTEMPT=0

    echo -n "Waiting for database..."
    while [ ${ATTEMPT} -lt ${MAX_ATTEMPTS} ]; do
        if ${CONTAINER_BIN} run --rm --network ${NETWORK} ${CONTAINER_IMAGE} \
            /bin/sh -c "nc -z ${DB_HOST} ${DB_PORT}" >/dev/null 2>&1; then
            echo " ready!"
            return 0
        fi
        echo -n "."
        sleep 1
        ATTEMPT=$((ATTEMPT + 1))
    done

    echo " timeout!"
    return 1
}

# Load help text
read -r -d '' HELP <<EOF || true
${EXTENSION_KEY} test runner. Execute test suites in containers.

Usage: $0 [options] [file]

Options:
    -s <suite>
        Specifies which test suite to run:
            - cgl: PHP Coding Guidelines check/fix
            - clean: Clean up build artifacts
            - composer: Execute composer command (use -e for arguments)
            - composerUpdate: Update dependencies for specific TYPO3 version
            - functional: Functional tests
            - lint: PHP syntax check
            - phpstan: Static code analysis
            - unit: Unit tests

    -b <docker|podman>
        Container binary to use (default: podman if available, else docker)

    -p <8.2|8.3|8.4|8.5>
        PHP version (default: 8.3)

    -d <sqlite|mariadb|mysql|postgres>
        Database for functional tests (default: sqlite)
            - sqlite: SQLite (file-based, fastest)
            - mariadb: MariaDB
            - mysql: MySQL
            - postgres: PostgreSQL

    -a <mysqli|pdo_mysql>
        Database driver for mysql/mariadb (default: mysqli)

    -i <10.4|10.5|10.6|10.7|10.11|11.4>
        MariaDB version (default: 10.4)

    -j <8.0|8.4>
        MySQL version (default: 8.0)

    -k <12|13|14|15|16>
        PostgreSQL version (default: 15)

    -t <13|14>
        TYPO3 core version for composer operations (default: 13)

    -e "<options>"
        Extra options to pass to phpunit, composer, etc.
        Example: -e "-v --filter canDoSomething"

    -x
        Enable Xdebug for debugging (default port: 9003)

    -y <port>
        Xdebug port (default: 9003)

    -n
        Dry-run for cgl (show changes without applying)

    -u
        Update container images to latest versions

    -v
        Verbose output

    -h
        Show this help

Examples:
    # Run unit tests
    $0 -s unit

    # Run unit tests with PHP 8.4
    $0 -s unit -p 8.4

    # Run functional tests with MariaDB
    $0 -s functional -d mariadb

    # Run specific test file
    $0 -s unit Tests/Unit/Service/ScanServiceTest.php

    # Run with Xdebug
    $0 -s unit -x

    # Composer update for TYPO3 13
    $0 -s composerUpdate -t 13 -p 8.3

EOF

# Determine container binary (prefer podman over docker)
if command -v podman >/dev/null 2>&1; then
    CONTAINER_BIN="podman"
elif command -v docker >/dev/null 2>&1; then
    CONTAINER_BIN="docker"
else
    echo "Error: Neither podman nor docker found. Please install one of them." >&2
    exit 1
fi

# Default options
TEST_SUITE=""
PHP_VERSION="8.3"
TYPO3_VERSION="13"
DBMS="sqlite"
DATABASE_DRIVER=""
MARIADB_VERSION="10.4"
MYSQL_VERSION="8.0"
POSTGRES_VERSION="15"
EXTRA_OPTIONS=""
XDEBUG_ENABLED=0
XDEBUG_PORT=9003
XDEBUG_MODES="debug,develop"
CGL_DRY_RUN=""
SCRIPT_VERBOSE=0

# Parse command line options
OPTIND=1
INVALID_OPTIONS=()
while getopts ":s:b:p:d:a:i:j:k:t:e:xy:z:nuvh" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE="${OPTARG}"
            ;;
        b)
            CONTAINER_BIN="${OPTARG}"
            if ! [[ "${CONTAINER_BIN}" =~ ^(docker|podman)$ ]]; then
                INVALID_OPTIONS+=("b ${OPTARG}")
            fi
            ;;
        p)
            PHP_VERSION="${OPTARG}"
            if ! [[ "${PHP_VERSION}" =~ ^(8\.2|8\.3|8\.4|8\.5)$ ]]; then
                INVALID_OPTIONS+=("p ${OPTARG}")
            fi
            ;;
        d)
            DBMS="${OPTARG}"
            if ! [[ "${DBMS}" =~ ^(sqlite|mariadb|mysql|postgres)$ ]]; then
                INVALID_OPTIONS+=("d ${OPTARG}")
            fi
            ;;
        a)
            DATABASE_DRIVER="${OPTARG}"
            if ! [[ "${DATABASE_DRIVER}" =~ ^(mysqli|pdo_mysql)$ ]]; then
                INVALID_OPTIONS+=("a ${OPTARG}")
            fi
            ;;
        i)
            MARIADB_VERSION="${OPTARG}"
            if ! [[ "${MARIADB_VERSION}" =~ ^(10\.4|10\.5|10\.6|10\.7|10\.11|11\.4)$ ]]; then
                INVALID_OPTIONS+=("i ${OPTARG}")
            fi
            ;;
        j)
            MYSQL_VERSION="${OPTARG}"
            if ! [[ "${MYSQL_VERSION}" =~ ^(8\.0|8\.4)$ ]]; then
                INVALID_OPTIONS+=("j ${OPTARG}")
            fi
            ;;
        k)
            POSTGRES_VERSION="${OPTARG}"
            if ! [[ "${POSTGRES_VERSION}" =~ ^(12|13|14|15|16)$ ]]; then
                INVALID_OPTIONS+=("k ${OPTARG}")
            fi
            ;;
        t)
            TYPO3_VERSION="${OPTARG}"
            if ! [[ "${TYPO3_VERSION}" =~ ^(13|14)$ ]]; then
                INVALID_OPTIONS+=("t ${OPTARG}")
            fi
            ;;
        e)
            EXTRA_OPTIONS="${OPTARG}"
            ;;
        x)
            XDEBUG_ENABLED=1
            ;;
        y)
            XDEBUG_PORT="${OPTARG}"
            ;;
        z)
            XDEBUG_MODES="${OPTARG}"
            ;;
        n)
            CGL_DRY_RUN="--dry-run --diff"
            ;;
        u)
            TEST_SUITE="update"
            ;;
        v)
            SCRIPT_VERBOSE=1
            set -x
            ;;
        h)
            echo "${HELP}"
            exit 0
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Handle invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for OPTION in "${INVALID_OPTIONS[@]}"; do
        echo "  -${OPTION}" >&2
    done
    echo "" >&2
    echo "${HELP}" >&2
    exit 1
fi

# Show help if no suite specified
if [ -z "${TEST_SUITE}" ]; then
    echo "${HELP}"
    exit 0
fi

# Get optional test file argument
shift $((OPTIND - 1))
TEST_FILE="${1:-}"

# Build PHP image name
PHP_IMAGE="${IMAGE_PREFIX}core-testing-$(echo php${PHP_VERSION} | tr -d '.')":latest

# Container host for Xdebug
CONTAINER_HOST="host.docker.internal"

# Build common container params as string (kickstarter approach)
if [ "${CONTAINER_BIN}" = "docker" ]; then
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm ${USERSET} -v ${ROOT_PATH}:${ROOT_PATH} -w ${ROOT_PATH} --add-host ${CONTAINER_HOST}:host-gateway"
else
    # Podman has host.containers.internal built-in
    CONTAINER_HOST="host.containers.internal"
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm ${USERSET} -v ${ROOT_PATH}:${ROOT_PATH} -w ${ROOT_PATH}"
fi

# Xdebug configuration
if [ ${XDEBUG_ENABLED} -eq 1 ]; then
    XDEBUG_MODE="-e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=foo"
    XDEBUG_CONFIG="client_port=${XDEBUG_PORT} client_host=${CONTAINER_HOST}"
else
    XDEBUG_MODE="-e XDEBUG_MODE=off"
    XDEBUG_CONFIG=""
fi

# Unique suffix for container names
SUFFIX=$(echo $RANDOM)
NETWORK="cf-cookiemanager-${SUFFIX}"

# Trap to cleanup on exit
trap 'cleanUp "${NETWORK}" "${CONTAINER_BIN}"' EXIT

# Validate database driver options
case ${DBMS} in
    mysql|mariadb)
        [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
        ;;
    postgres|sqlite)
        if [ -n "${DATABASE_DRIVER}" ]; then
            echo "Warning: -a option ignored for ${DBMS}" >&2
            DATABASE_DRIVER=""
        fi
        ;;
esac

# Execute test suite
case ${TEST_SUITE} in
    cgl)
        DRY_RUN_OPTIONS=''
        if [ -n "${CGL_DRY_RUN}" ]; then
            DRY_RUN_OPTIONS='--dry-run --diff'
        fi
        COMMAND="php -dxdebug.mode=off .Build/bin/php-cs-fixer fix -v ${DRY_RUN_OPTIONS} --config=Build/cgl/.php-cs-fixer.php --using-cache=no"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name cgl-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;

    clean)
        echo "Cleaning build artifacts..."
        rm -rf \
            "${ROOT_PATH}/.Build" \
            "${ROOT_PATH}/.cache" \
            "${ROOT_PATH}/var" \
            "${ROOT_PATH}/public" \
            "${ROOT_PATH}/composer.lock"
        echo "Clean complete."
        SUITE_EXIT_CODE=0
        ;;

    composer)
        COMMAND=(composer ${EXTRA_OPTIONS})
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name composer-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${PHP_IMAGE} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;

    composerUpdate)
        echo "Running composer update for TYPO3 ${TYPO3_VERSION} with PHP ${PHP_VERSION}..."
        rm -rf .Build/bin .Build/typo3 .Build/vendor ./composer.lock
        cp "${ROOT_PATH}/composer.json" "${ROOT_PATH}/composer.json.orig"
        if [ -f "${ROOT_PATH}/composer.json.testing" ]; then
            cp "${ROOT_PATH}/composer.json.testing" "${ROOT_PATH}/composer.json"
        fi
        COMMAND=(composer require --no-ansi --no-interaction --no-progress typo3/cms-core:^${TYPO3_VERSION}.0)
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name composer-install-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${PHP_IMAGE} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        cp "${ROOT_PATH}/composer.json" "${ROOT_PATH}/composer.json.testing"
        mv "${ROOT_PATH}/composer.json.orig" "${ROOT_PATH}/composer.json"
        ;;

    functional)
        ${CONTAINER_BIN} network create ${NETWORK} >/dev/null
        COMMAND=(.Build/bin/phpunit -c Build/phpunit/FunctionalTests.xml --exclude-group not-${DBMS} ${EXTRA_OPTIONS} "$@")
        case ${DBMS} in
            mariadb)
                echo "Using driver: ${DATABASE_DRIVER}"
                ${CONTAINER_BIN} run --rm --name mariadb-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mariadb:${MARIADB_VERSION} >/dev/null
                waitForDatabase "${CONTAINER_BIN}" "${NETWORK}" "mariadb-func-${SUFFIX}" 3306 "${PHP_IMAGE}"
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mariadb-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} --network ${NETWORK} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${PHP_IMAGE} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            mysql)
                echo "Using driver: ${DATABASE_DRIVER}"
                ${CONTAINER_BIN} run --rm --name mysql-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid mysql:${MYSQL_VERSION} >/dev/null
                waitForDatabase "${CONTAINER_BIN}" "${NETWORK}" "mysql-func-${SUFFIX}" 3306 "${PHP_IMAGE}"
                CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mysql-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} --network ${NETWORK} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${PHP_IMAGE} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                ${CONTAINER_BIN} run --rm --name postgres-func-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid postgres:${POSTGRES_VERSION}-alpine >/dev/null
                waitForDatabase "${CONTAINER_BIN}" "${NETWORK}" "postgres-func-${SUFFIX}" 5432 "${PHP_IMAGE}"
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=bamboo -e typo3DatabaseUsername=funcu -e typo3DatabaseHost=postgres-func-${SUFFIX} -e typo3DatabasePassword=funcp"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} --network ${NETWORK} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${PHP_IMAGE} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                mkdir -p "${ROOT_PATH}/.Build/web/typo3temp/var/tests/functional-sqlite-dbs/"
                CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${ROOT_PATH}/.Build/web/typo3temp/var/tests/functional-sqlite-dbs/:rw,noexec,nosuid"
                ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${PHP_IMAGE} "${COMMAND[@]}"
                SUITE_EXIT_CODE=$?
                ;;
        esac
        ;;

    lint)
        COMMAND="find . -name \\*.php ! -path './.Build/*' -print0 | xargs -0 -n1 -P4 php -dxdebug.mode=off -l >/dev/null"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;

    phpstan)
        COMMAND="php -dxdebug.mode=off .Build/bin/phpstan --configuration=Build/phpstan/phpstan.neon"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-${SUFFIX} -e COMPOSER_CACHE_DIR=.Build/.cache/composer -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} ${PHP_IMAGE} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;

    unit)
        COMMAND=(.Build/bin/phpunit -c Build/phpunit/UnitTests.xml ${EXTRA_OPTIONS} "$@")
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${PHP_IMAGE} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;

    update)
        echo "Updating container images..."
        IMAGES=$(${CONTAINER_BIN} images "${IMAGE_PREFIX}core-testing-*:latest" --format "{{.Repository}}:{{.Tag}}" 2>/dev/null || true)
        if [ -n "${IMAGES}" ]; then
            for IMAGE in ${IMAGES}; do
                echo "Pulling ${IMAGE}..."
                ${CONTAINER_BIN} pull "${IMAGE}"
            done
        else
            echo "No TYPO3 testing images found locally."
        fi

        # Remove dangling images
        DANGLING=$(${CONTAINER_BIN} images "${IMAGE_PREFIX}core-testing-*" --filter "dangling=true" --format "{{.ID}}" 2>/dev/null || true)
        if [ -n "${DANGLING}" ]; then
            echo "Removing dangling images..."
            ${CONTAINER_BIN} rmi ${DANGLING} 2>/dev/null || true
        fi

        SUITE_EXIT_CODE=0
        ;;

    *)
        echo "Invalid test suite: ${TEST_SUITE}" >&2
        echo "" >&2
        echo "${HELP}" >&2
        exit 1
        ;;
esac

exit ${SUITE_EXIT_CODE}
