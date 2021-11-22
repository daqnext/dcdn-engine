<?php
/*
   Plugin Name: DCDN Engine
   Text Domain: dcdn-engine
   Description: Simply integrate a Distributed Content Delivery Network (DCDN) into your WordPress site.
   Author: Meson Network
   Author URI: https://meson.network
   License: GPLv2 or later
   Version: 0.0.1
 */

/*
   Copyright (C)  2021 Meson Network

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License along
   with this program; if not, write to the Free Software Foundation, Inc.,
   51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/* Check & Quit */
defined('ABSPATH') OR exit;


/* constants */
define('DCDN_ENGINE_FILE', __FILE__);
define('DCDN_ENGINE_DIR', dirname(__FILE__));
define('DCDN_ENGINE_BASE', plugin_basename(__FILE__));
define('DCDN_ENGINE_MIN_WP', '3.8');


/* loader */
add_action(
    'plugins_loaded',
    [
        'DCDN_Engine',
        'instance',
    ]
);


/* uninstall */
register_uninstall_hook(
    __FILE__,
    [
        'DCDN_Engine',
        'handle_uninstall_hook',
    ]
);


/* activation */
register_activation_hook(
    __FILE__,
    [
        'DCDN_Engine',
        'handle_activation_hook',
    ]
);


/* autoload init */
spl_autoload_register('DCDN_ENGINE_autoload');

/* autoload funktion */
function DCDN_ENGINE_autoload($class) {
    if ( in_array($class, ['DCDN_Engine', 'DCDN_Engine_Rewriter', 'DCDN_Engine_Settings']) ) {
        require_once(
            sprintf(
                '%s/inc/%s.class.php',
                DCDN_ENGINE_DIR,
                strtolower($class)
            )
        );
    }
}
