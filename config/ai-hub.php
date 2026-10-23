<?php

// The application exposes its own tenant-authorized assistant. Never expose the
// package's installation-wide settings UI to business customers.
$defaults = require base_path('vendor/imrandevbd/laravel-ai-hub/config/ai-hub.php');
$defaults['settings']['ui_enabled'] = false;

return $defaults;
