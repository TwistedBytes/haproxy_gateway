#!/bin/bash

systemctl daemon-reload
systemctl is-active --quiet haproxy-gateway && systemctl restart haproxy-gateway
