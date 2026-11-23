<?php

return [
    /*
    | Accounts allowed to see internal pages such as Movie Night stats.
    | Comma-separated in .env:  ADMIN_EMAILS=you@example.com,partner@example.com
    */
    'admin_emails' => array_values(array_filter(array_map(
        fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', ''))
    ))),
];
