<?php

/**
 * Plugin Name:       Pepper Connector
 * Description:       Pepper Connector Plugin
 * Version:           2.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            PepperContent
 * Author URI:        https://www.peppercontent.io
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pepper
 * Domain Path:       /languages
 */


register_uninstall_hook(__FILE__, 'pepper_cleaner');
function pepper_cleaner()
{
    delete_option('pepper_settings_option_name');
    delete_option('pepper_settings_option_name_key');
    delete_option('pepper_settings_option_name_webhook_key');
}

$enable_domain_restrictions = false;
$allowed_domains = array('localhost', 'gmmkjpcadciiokjpikmkkmapphbmdjok');

define("PEPPER_DOMAIN_RESTRICTIONS", $enable_domain_restrictions);
define("PEPPER_ALLOWED_DOMAIN", $allowed_domains);



include(plugin_dir_path(__FILE__) . 'includes/settings.php');
include(plugin_dir_path(__FILE__) . 'includes/auth.php');
include(plugin_dir_path(__FILE__) . 'includes/api_helper.php');
include(plugin_dir_path(__FILE__) . 'includes/get_apis.php');
include(plugin_dir_path(__FILE__) . 'includes/post_apis.php');
include(plugin_dir_path(__FILE__) . 'includes/webhooks.php');


function pepper_set_def()
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    if (!isset($pepper_settings_options['status_1'])) {
        $pepper_settings_options['status_1'] = 1;
        update_option('pepper_settings_option_name', $pepper_settings_options);
    }
}
add_action('init', 'pepper_set_def');
