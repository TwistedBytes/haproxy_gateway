#!/bin/bash

set -ex

bash static-build-prepare.sh
bash build-rpm.sh

#rsync -e 'ssh -p2223' -avr --progress haproxy-gateway-*.rpm root@tb-backup-03.twistedbytes.eu:~repository.twistedbytes.eu/site/docroot/centos/shared/package-sets/haproxy-gateway/x86_64/
#
#ssh -p2223 root@tb-backup-03.twistedbytes.eu ~repository.twistedbytes.eu/site/updaterepo.sh centos/8
#ssh -p2223 root@tb-backup-03.twistedbytes.eu ~repository.twistedbytes.eu/site/updaterepo.sh centos/9

rm -vf \
 haproxy-gateway \
 haproxy-gateway-*.rpm
