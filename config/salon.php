<?php

return ['attendance_hosts' => array_values(array_filter(array_map('trim', explode(',', env('SALON_ATTENDANCE_HOSTS', ''))))), 'import_hosts' => array_values(array_filter(array_map('trim', explode(',', env('SALON_IMPORT_HOSTS', ''))))), 'demo' => env('SALON_DEMO', false), 'demo_password' => env('DEMO_PASSWORD', 'DemoSalon2026!'), 'ai_live' => env('SALON_AI_LIVE', false)];
