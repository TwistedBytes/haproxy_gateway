#!/bin/bash

# clean up old dirs
[[ -d /var/log/haproxy-gateway/framework ]] && rm -Rf /var/log/haproxy-gateway/framework
[[ -d /var/log/haproxy-gateway/logs ]] && rm -Rf /var/log/haproxy-gateway/logs/
#

systemctl daemon-reload
systemctl is-active --quiet haproxy-gateway && systemctl restart haproxy-gateway
