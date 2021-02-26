<?php

/*
Plugin Name: Tuyul Ninja
Plugin URI: https://forum.nusagates.com/tags/tuyul-ninja/
Description: Tuyul Ninja enables you to send wordpress post to available providers via cronjob.
Version: 1.2.0
Author: Ahmad Budairi<budairi.contact@gmail.com>
Author URI: https://skutik.com/author/5737498951
Tested up to: 5.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://paypal.me/nusagates
Text Domain:  tuyul-ninja
Domain Path:  /languages/.
*
 *
 * LICENSE
 * This file is part of Tuyul Ninja.
 *
 * Tuyul Ninja is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package    wp-tuyul
 * @author     Ahmad Budairi <budairi.contact@gmail.com>
 * @copyright  Copyright 2021 Ahmad Budairi
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GPL 2.0
 * @since      1.0
*/

use App\Includes\TuyulCore;

global $wpdb;
define("TUYUL_TABLE_HISTORIES", $wpdb->prefix . "tuyul_histories");
define("TUYUL_TABLE_JOBS", $wpdb->prefix . "tuyul_jobs");
define("TUYUL_DB_VERSION", "1");
define('TUYUL_PATH', plugin_dir_path(__FILE__));
define('TUYUL_URL', plugin_dir_url(__FILE__));
define('TUYUL_PLUGIN_FILE_URL', __FILE__);
require_once(TUYUL_PATH . "vendor/autoload.php");
new TuyulCore();





