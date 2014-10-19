<?php
/*
 * Channel
 */
define('DEVELOPERKEY', '00000000-0000-0000-0000-00000000000');
define('PASSWORD', 'yourPassword');
define('localID', '');
define('ACCOUNTID', '00000000-0000-0000-0000-00000000000');

/*
 * FTP + DIRECTORY
 */
define('IMPORT_DIR', __DIR__ . '/GLOBAL/');
define('IMPORT_DIR_HISTORY', __DIR__ . '/GLOBAL_HISTORY/');
define('FTP_SERVER', "#");
define('FTP_USER', "#");
define('FTP_PASSWORD', "#");

/*
 * EMAIL
 */
define('EMAIL_TO', 'mailbox@box.com');
define('EMAIL_SUBJECT', 'SCV PRODUCT IMPORT ' . date("Y-m-d H:i:s", time()));
define('EMAIL_HEADERS', "From: noreply@email.com \r\n" .
    "Reply-To: noreply@email.com \r\n" .
    "Content-type: text/html; charset=UTF-8 \r\n");




?>