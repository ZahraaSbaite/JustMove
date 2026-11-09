<?php

return [
    // Who gets an email for every new order (comma-separated for several).
    'notify_email' => env('NOTIFY_EMAIL'),
    // Public address of the React site, used for the "Open in admin" link in emails.
    'site_url' => env('SITE_URL', 'http://localhost:5173'),
];
