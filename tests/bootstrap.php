<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Now call the bootstrap method of WP Mock
WP_Mock::bootstrap();

require_once __DIR__ . '/../class/UserAccessManager.php';
require_once __DIR__ . '/../class/UamAccessHandler.php';
require_once __DIR__ . '/../class/UamUserGroup.php';
require_once __DIR__ . '/../class/UamConfig.php';