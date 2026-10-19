<?php
/**
 * Live server database settings - TEMPLATE.
 *
 * On the cPanel server:
 *   1. In File Manager, open public_html/includes/
 *   2. Copy this file and name the copy  config.local.php
 *   3. Fill in the database name, user and password from cPanel > MySQL Databases
 *
 * config.local.php is never uploaded to GitHub and deploying new code never replaces it.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_dbname');      // full name as shown in cPanel, e.g. tameer_school
define('DB_USER', 'cpaneluser_dbuser');      // the database user added to that database
define('DB_PASS', 'the-database-password');
