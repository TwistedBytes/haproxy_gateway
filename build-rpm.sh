#!/bin/bash

set -ex

[[ -f ./rpm/last_version ]] && . ./rpm/last_version

VERSION=$( date +%Y.%m.%d )
if [[ ${VERSION} == ${LAST_VERSION} ]]; then
    ITERATION=$(( ${LAST_ITERATION} + 1 ))
else
    ITERATION=1
fi

rm -Rf rpmbuild
mkdir rpmbuild
mkdir -p \
    rpmbuild/opt/haproxy-gateway \
    rpmbuild/etc/sysconfig/ \
    rpmbuild/usr/lib/systemd/system \
    rpmbuild/etc/systemd/system/haproxy.service.d \
    rpmbuild/var/log/haproxy-gateway \
    rpmbuild/scripts

cp -f haproxy-gateway rpmbuild/opt/haproxy-gateway
chmod +x rpmbuild/opt/haproxy-gateway
cp -f rpm/haproxy-gateway.service rpmbuild/usr/lib/systemd/system/haproxy-gateway.service
chmod 644 rpmbuild/usr/lib/systemd/system/haproxy-gateway.service
cp -f rpm/haproxy-gateway-loadservers.service rpmbuild/usr/lib/systemd/system/haproxy-gateway-loadservers.service
chmod 644 rpmbuild/usr/lib/systemd/system/haproxy-gateway-loadservers.service
cp -f rpm/sysconfig-haproxy-gateway.conf rpmbuild/etc/sysconfig/haproxy-gateway-defaults
chmod 644 rpmbuild/etc/sysconfig/haproxy-gateway-defaults
cp -f rpm/haproxy-dropin-loadservers.conf rpmbuild/etc/systemd/system/haproxy.service.d/haproxy-loadservers.conf
chmod 644 rpmbuild/etc/sysconfig/haproxy-gateway-defaults


cp -f rpm/after-install.sh rpmbuild/scripts/
chmod +x rpmbuild/scripts/after-install.sh

podman run --rm -ti \
    -v $(pwd)/rpmbuild:/build \
    pgrzesiecki/docker-fpm \
        --verbose -s dir -t rpm \
        --rpm-user haproxy \
        --rpm-group haproxy \
        --after-install scripts/after-install.sh  \
        --exclude scripts \
        --config-files /etc/sysconfig/haproxy-gateway-defaults \
        --name haproxy-gateway --version ${VERSION} --iteration ${ITERATION} \
        --description "twistedbytes haproxy-gateway" \
        --rpm-tag 'Requires: curl' \
        ./
mv rpmbuild/haproxy-gateway-${VERSION}-${ITERATION}.x86_64.rpm .
rm rpmbuild -Rvf

echo LAST_VERSION=${VERSION} > ./rpm/last_version
echo LAST_ITERATION=${ITERATION} >> ./rpm/last_version
