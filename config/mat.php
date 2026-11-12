<?php

return [
    'brand' => 'MAT LEGAL NETWORK',

    'admin' => [
        'name' => env('ADMIN_NAME', 'MAT Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        'notify_email' => env('ADMIN_NOTIFY_EMAIL', env('ADMIN_EMAIL')),
    ],

    // Default country for the launch. Other countries can be activated later from the database.
    'default_country' => env('MAT_DEFAULT_COUNTRY', 'IN'),

    'per_page' => 12,

    // AI spam screening of new enquiries (Anthropic API). Off unless ANTHROPIC_API_KEY is set.
    // Fails open: if the key is missing or the API errors / times out, the enquiry is handled as normal.
    'spam_ai' => [
        'enabled' => (bool) env('SPAM_AI_ENABLED', true),
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('SPAM_AI_BASE_URL'), // leave empty; only for proxies and the browser tests' fake API
        'model' => env('SPAM_AI_MODEL', 'claude-opus-5-5'),
        // Only verdicts at or above this confidence (0-100) go to the "Suspected spam" queue.
        'threshold' => (int) env('SPAM_AI_THRESHOLD', 80),
        'timeout' => (float) env('SPAM_AI_TIMEOUT', 20),
    ],

    // Requests per minute per IP for search/profile pages (anti-scraping).
    'search_rate_limit' => (int) env('SEARCH_RATE_LIMIT', 90),

    'uploads' => [
        'photo_max_kb' => 2048,
        'document_max_kb' => 5120,
        'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'photo_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'experience_buckets' => [1 => '1+ years', 3 => '3+ years', 5 => '5+ years', 10 => '10+ years', 15 => '15+ years', 20 => '20+ years'],

    'genders' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'],

    'designations' => ['Advocate', 'Senior Advocate', 'Advocate-on-Record', 'Legal Consultant', 'Solicitor'],

    'document_types' => [
        'enrolment_certificate' => 'Bar enrolment certificate / licence',
        'id_proof' => 'Government photo ID',
        'other' => 'Other supporting document',
    ],

    // Defaults for editable company settings (admin > Settings overrides these).
    'settings_defaults' => [
        'company_phone' => '+91 00000 00000',
        'company_whatsapp' => '910000000000',
        'company_email' => 'support@example.com',
        'company_address' => 'Registered office address, India',
        'support_hours' => 'Mon–Sat, 9:30 AM – 7:00 PM IST',
        'legal_entity_name' => 'MAT LEGAL NETWORK',
        'jurisdiction_city' => 'New Delhi',
        'grievance_officer_name' => 'Grievance Officer',
        'grievance_officer_email' => 'grievance@example.com',
    ],
];
