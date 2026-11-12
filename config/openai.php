<?php

return [
    'api_key' => env('OPENAI_API_KEY', ''),
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'timeout' => (int) env('OPENAI_TIMEOUT', 60),

    // Batas jumlah artikel per sekali generate (validasi di server & input type="number" di UI).
    'max_posts' => (int) env('OPENAI_MAX_POSTS', 5),
];
