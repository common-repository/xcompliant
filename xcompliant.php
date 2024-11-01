<?php
/**
 * Plugin Name:       Web Accessibility - Free Accessibility Scan and Audit for ADA & WCAG 2.1 Compliance by XCompliant
 * Description:       XCompliant supports web accessibility for websites by replacing a costly, manual process with an automated, state-of-the-art AI technology.
 * Version:           1.0.8
 * Requires at least: 5.2
 * Author:            XCompliant
 * License:           GNU General Public License (GPL) version 3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

require_once('config.php');
require_once('helpers.php');

/**
 * Plugin menu item.
 */
function xCompliantAdminMenu() {
  add_submenu_page(
    'tools.php',
    __( 'XCompliant ‑ ADA & WCAG 2.1 - Accessibility', 'textdomain' ),
    __( 'XCompliant', 'textdomain' ),
    'manage_options',
    'XCompliant',
    'xCompliantPluginPage'
  );
}
add_action( 'admin_menu', 'xCompliantAdminMenu' );


/**
 * Plugin page.
 */
function xCompliantPluginPage() {
global $xCompliantFailed;

  if ($xCompliantFailed) {
    include('admin/failed.html');
    return null;
  }

  echo "<div id='root'></div>\n";
}

/**
 * Enqueue scripts and styles on the admin plugin page.
 *
 * @param int $hook Hook suffix for the current admin page.
 */
add_action('admin_enqueue_scripts', 'xCompliantAdminScripts');
function xCompliantAdminScripts($hook) {
global $xCompliantFailed;
  if (!in_array($hook, ['plugins_page_XCompliant', 'settings_page_XCompliant', 'tools_page_XCompliant'])) {
    return;
  }
  $data = xCompliantLoadData();
  if (!is_array($data)) {
    $xCompliantFailed = true;
    return;
  }

  foreach ($data['styles_urls'] as $key => $styles_url) {
    wp_enqueue_style("xCompliantStyle$key", $styles_url);
  }
  
  foreach ($data['scripts_urls'] as $key => $scripts_url) {
    wp_enqueue_script("xCompliantScript$key", $scripts_url, [], false, true);
  }

  foreach ($data['scripts'] as $script) {
    wp_add_inline_script('xCompliantScript0', $script, 'before');
  }

}

/**
 * Enqueue the widget script.
 */
add_action('wp_enqueue_scripts', 'xCompliantLoadWidget');
function xCompliantLoadWidget() {
global $xCompliantAppUrl, $xCompliantApiUrl;
  $url = get_site_url();
  $parse = parse_url($url);
  $xCompliantDomain = $parse['host'];
  $url = $xCompliantApiUrl ? $xCompliantApiUrl : $xCompliantAppUrl;
  $xcWidget = "{$url}script?shop={$xCompliantDomain}";
  wp_enqueue_script("xCompliantWidget", $xcWidget, []);
}

/**
 * Add settings link.
 */
add_filter('plugin_action_links_xcompliant/xcompliant.php', 'xCompliantSettingsLink');
function xCompliantSettingsLink($links)
{
  $settingsLink = sprintf('<a href="%s">%s</a>', menu_page_url('XCompliant', false), __('Settings'));
  array_unshift($links, $settingsLink);
  return $links;
}

?>
