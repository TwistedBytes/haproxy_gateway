#!/bin/bash

set -e

_PWD=$(pwd)

TMPDIR=./tmp
rm -Rf $TMPDIR
# Export the project to get rid of .git/, etc
mkdir -p $TMPDIR/my-prepared-app1
if [[ $_GIT == 'no' ]]; then
    cp -Rf src $TMPDIR/my-prepared-app
else
    git archive HEAD | tar -x -C $TMPDIR/my-prepared-app1
    mv $TMPDIR/my-prepared-app1/src $TMPDIR/my-prepared-app
    rm -Rf $TMPDIR/my-prepared-app1
fi

cd $TMPDIR/my-prepared-app

# Set proper environment variables
cp .env.prod .env

# Remove the tests and other unneeded files to save space
# Alternatively, add these files with the export-ignore attribute in your .gitattributes file
rm -Rf tests/

# Install the dependencies
podman compose run --rm -w /app/tmp/my-prepared-app php-cli \
    composer install --ignore-platform-reqs --no-dev -a --no-interaction --prefer-dist --optimize-autoloader
podman compose run --rm -w /app/tmp/my-prepared-app php-cli \
    php artisan view:cache
podman compose run --rm -w /app/tmp/my-prepared-app php-cli \
    php artisan route:cache

podman build -t static-app -f ${_PWD}/static-build.Dockerfile .
podman cp $(podman create --name static-app-tmp static-app):/go/src/app/dist/frankenphp-linux-x86_64 my-app
podman rm static-app-tmp
mv my-app ${_PWD}/haproxy-gateway
cd $_PWD
rm tmp -Rf
