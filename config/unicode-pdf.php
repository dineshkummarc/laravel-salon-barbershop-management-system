<?php

$defaults = require base_path('vendor/imrandevbd/laravel-unicode-pdf/config/unicode-pdf.php');
$defaults['default_font'] = 'Noto Sans Bengali';
$defaults['custom_font_path'] = public_path('fonts');
$defaults['fonts'] = ['Noto Sans Bengali' => ['regular' => public_path('fonts/NotoSansBengali-Regular.ttf'), 'bold' => public_path('fonts/NotoSansBengali-Bold.ttf')]];

return $defaults;
