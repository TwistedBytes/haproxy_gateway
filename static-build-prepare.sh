#!/bin/bash

TMPDIR=./tmp
# Export the project to get rid of .git/, etc
mkdir -p $TMPDIR/my-prepared-app1
git archive HEAD | tar -x -C $TMPDIR/my-prepared-app1
mv $TMPDIR/my-prepared-app1/src $TMPDIR/my-prepared-app
rm -Rf $TMPDIR/my-prepared-app1
cd $TMPDIR/my-prepared-app

# Set proper environment variables
cp .env.prod .env

# Remove the tests and other unneeded files to save space
# Alternatively, add these files with the export-ignore attribute in your .gitattributes file
rm -Rf tests/

# Install the dependencies
composer install --ignore-platform-reqs --no-dev -a
