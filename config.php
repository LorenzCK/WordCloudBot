<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Configuration file.
 */

define('PROGRAM_O_API_URI','/chatbot/conversation_start.php');

define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_API_URI_BASE', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/');
define('TELEGRAM_API_URI_ME', TELEGRAM_API_URI_BASE . 'getMe');
define('TELEGRAM_API_URI_MESSAGE', TELEGRAM_API_URI_BASE . 'sendMessage');
define('TELEGRAM_API_URI_LOCATION', TELEGRAM_API_URI_BASE . 'sendLocation');
define('TELEGRAM_API_URI_PHOTO', TELEGRAM_API_URI_BASE . 'sendPhoto');
define('TELEGRAM_API_URI_UPDATES', TELEGRAM_API_URI_BASE . 'getUpdates');

define('DATABASE_HOST', '');
define('DATABASE_NAME', '');
define('DATABASE_USERNAME', '');
define('DATABASE_PASSWORD', '');

define('BING_API', '');

define('RESULT_DISTANCE', 30);

?>
