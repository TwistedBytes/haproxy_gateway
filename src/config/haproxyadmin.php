<?php

return [
    'connection_string' => env('HAPROXYADMIN_CONNECTION_STRING', 'haproxy:8081'),
    'state_path' => env('HAPROXYADMIN_STATE_PATH', '/usr/local/etc/haproxy/state'),
    'backend_defaultoptions' => env('HAPROXYADMIN_BACKEND_OPTIONS', 'check'),
];
