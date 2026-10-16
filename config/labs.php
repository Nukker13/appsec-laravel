<?php

return [
    /*
     | Absolute path of the labs/ directory. Inside the app/queue/scheduler containers this is
     | the same path as on the host (docs/decisions/0001-host-path-bind-mounts.md).
     */
    'path' => env('LABS_PATH') ?: base_path('labs'),

    /* Per-instance runtime files (flag.txt), bind-mounted into lab containers in phase 6. */
    'instances_path' => storage_path('app/instances'),

    /* Instances idle longer than this are stopped by labs:reap. */
    'idle_minutes' => (int) env('LAB_IDLE_MINUTES', 15),

    /* Hard lifetime of an instance flag (cache TTL). */
    'max_minutes' => (int) env('LAB_MAX_MINUTES', 120),

    /* Cap on concurrent lab instances (phase 6). */
    'max_instances' => (int) env('LABS_MAX_INSTANCES', 4),

    /* External Docker network shared with Traefik. */
    'proxy_network' => 'labs_proxy',

    /* Instance host: {project with - for _}.{host_suffix}. */
    'host_suffix' => 'labs.localhost',
];
