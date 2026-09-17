<?php

return [
    'tenancy' => [
        'enabled' => (bool) env('IS_TENANT', false),
        'legacy_organization_name' => env('TENANCY_LEGACY_ORGANIZATION_NAME', 'Legacy ExamUP Organisation'),
    ],
];
