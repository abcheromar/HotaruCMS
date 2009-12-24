<?php
/**
 * Upgrade Hotaru CMS
 * 
 * Steps through the set-up process, creating database tables and registering 
 * the Admin user. Note: You must delete this file after installation as it 
 * poses a serious security risk if left.
 *
 * PHP version 5
 *
 * LICENSE: Hotaru CMS is free software: you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of 
 * the License, or (at your option) any later version. 
 *
 * Hotaru CMS is distributed in the hope that it will be useful, but WITHOUT 
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE. 
 *
 * You should have received a copy of the GNU General Public License along 
 * with Hotaru CMS. If not, see http://www.gnu.org/licenses/.
 * 
 * @category  Content Management System
 * @package   HotaruCMS
 * @author    Nick Ramsay <admin@hotarucms.org>
 * @copyright Copyright (c) 2009, Hotaru CMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link      http://www.hotarucms.org/
 */

require_once('../hotaru_settings.php');
require_once(BASE . 'Hotaru.php');
$h = new Hotaru('install'); // must come before language inclusion
$sql = "SELECT miscdata_value FROM " . TABLE_MISCDATA . " WHERE miscdata_key = %s";
$old_version = $h->db->get_var($h->db->prepare($sql, "hotaru_version"));
require_once(INSTALL . 'install_language.php');    // language file for install

// delete existing cache
$h->deleteFiles(CACHE . 'db_cache');
$h->deleteFiles(CACHE . 'css_js_cache');
$h->deleteFiles(CACHE . 'rss_cache');

$step = $h->cage->get->getInt('step');        // Installation steps.

switch ($step) {
    case 1:
        upgrade_welcome();     // "Welcome to Hotaru CMS. 
        break;
    case 2:
        do_upgrade();
        upgrade_complete();    // Delete "install" folder. Visit your site"
        break;
    default:
        // Anything other than step=2
        upgrade_welcome();
        break;        
}

exit;


/**
 * HTML header
 *
 * @return string returns the html output for the page header
 */
function html_header()
{
    global $lang;
    
    $header = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 3.2//EN'>\n";
    $header .= "<HTML><HEAD>\n";
    $header .= "<meta http-equiv=Content-Type content='text/html; charset=UTF-8'>\n";
    
    // Title
    $header .= "<TITLE>" . $lang['upgrade_title'] . "</TITLE>\n";
    $header .= "<META HTTP-EQUIV='Content-Type' CONTENT='text'>\n";
    $header .= "<link rel='stylesheet' type='text/css' href='" . BASEURL . "install/reset-fonts-grids.css' type='text/css'>\n";
    $header .= "<link rel='stylesheet' type='text/css' href='" . BASEURL . "install/install_style.css'>\n";
    $header .= "</HEAD>\n";
    
    // Body start
    $header .= "<BODY>\n";
    $header .= "<div id='doc' class='yui-t7 install'>\n";
    $header .= "<div id='hd' role='banner'>";
    $header .= "<img align='left' src='" . BASEURL . "content/admin_themes/admin_default/images/hotaru.png' style='height:60px; width:60px;'>";
    $header .= "<h1>" . $lang['upgrade_title'] . "</h1></div>\n"; 
    $header .= "<div id='bd' role='main'>\n";
    $header .= "<div class='yui-g'>\n";
    
    return $header;
}


/**
 * HTML footer
 *
 * @return string returns the html output for the page footer
 */
function html_footer()
{
    global $lang;
    
    $footer = "<div class='clear'></div>\n"; // clear floats
    
    // Footer content (a link to the forums)
    $footer .= "<div id='ft' role='contentinfo'>";
    $footer .= "<p>" . $lang['install_trouble'] . "</p>";
    $footer .= "</div>\n"; // close "ft" div
    
    $footer .= "</div>\n"; // close "yui-g" div
    $footer .= "</div>\n"; // close "main" div
    $footer .= "</div>\n"; // close "yui-t7 install" div
    
    $footer .= "</BODY>\n";
    $footer .= "</HTML>\n";
    
    return $footer;
}


/**
 * Step 1 of installation - Welcome message
 */
function upgrade_welcome()
{
    global $lang;
    
    echo html_header();
    
    // Step title
    echo "<h2>" . $lang['upgrade_step1'] . "</h2>\n";
    
    // Step content
    echo "<div class='install_content'>" . $lang['upgrade_step1_details'] . "</div>\n";
    
    // Next button
    echo "<div class='next'><a href='upgrade.php?step=2'>" . $lang['install_next'] . "</a></div>\n";
    
    echo html_footer();
}

    
/**
 * Step 2 of upgrade - shows completion.
 */
function upgrade_complete()
{
    global $lang;
    
    echo html_header();

    // Step title
    echo "<h2>" . $lang['upgrade_step2'] . "</h2>\n";
    
    // Step content
    echo "<div class='install_content'>" . $lang['upgrade_step2_details'] . "</div>\n";

    // Next button
    echo "<div class='next'><a href='" . BASEURL . "'>" . $lang['upgrade_home'] . "</a></div>\n";
    
    echo html_footer();    
}

/**
 * Do Upgrade
 */
function do_upgrade()
{
    global $h;
    // add new MISCDATA table for storing default permissions, etc.
    
    $table_name = "miscdata";
    $exists = $h->db->table_exists($table_name);
    if (!$exists) {
        $sql = "CREATE TABLE `" . DB_PREFIX . $table_name . "` (
          `miscdata_id` int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `miscdata_key` varchar(64) NOT NULL,
          `miscdata_value` text NOT NULL DEFAULT '',
          `miscdata_default` text NOT NULL DEFAULT '',
          `miscdata_updatedts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `miscdata_updateby` int(20) NOT NULL DEFAULT 0
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Miscellaneous Data';";
        $h->db->query($sql);

        // Default permissions
        $perms['options']['can_access_admin'] = array('yes', 'no');
        $perms['can_access_admin']['admin'] = 'yes';
        $perms['can_access_admin']['supermod'] = 'yes';
        $perms['can_access_admin']['default'] = 'no';
        $perms = serialize($perms);
        
        $sql = "INSERT INTO " . DB_PREFIX . $table_name . " (miscdata_key, miscdata_value, miscdata_default) VALUES (%s, %s, %s)";
        $h->db->query($h->db->prepare($sql, 'permissions', $perms, $perms));
    }
    
    // Update Hotaru version number in the database 
    $sql = "UPDATE " . DB_PREFIX . $table_name . " SET miscdata_value = %s, miscdata_default = %s WHERE miscdata_key = %s";
    $h->db->query($h->db->prepare($sql, $h->version, $h->version, 'hotaru_version'));
    
    $table_name = "tokens";
    $exists = $h->db->table_exists($table_name);
    if (!$exists) {
        $sql = "CREATE TABLE `" . DB_PREFIX . $table_name . "` (
          `token_id` MEDIUMINT unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `token_sid` varchar(32) NOT NULL,
          `token_key` CHAR(32) NOT NULL,
          `token_stamp` INT(11) NOT NULL default '0',
          `token_action` varchar(64),
          INDEX  (`token_key`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tokens for CSRF protection';";
        $h->db->query($sql);
    }
    
    // add SITE_OPEN setting
    $sql = "SELECT settings_id FROM " . DB_PREFIX . "settings WHERE settings_name = %s";
    $exists = $h->db->query($h->db->prepare($sql, 'SITE_OPEN'));
    if (!$exists) {
        $sql = "INSERT INTO " . DB_PREFIX . "settings (settings_name, settings_value, settings_default, settings_note) VALUES (%s, %s, %s, %s)";
        $h->db->query($h->db->prepare($sql, 'SITE_OPEN', 'true', 'true', 'true/false'));
    }
    
    // add new HTML_CACHE_ON setting
    $sql = "SELECT settings_id FROM " . DB_PREFIX . "settings WHERE settings_name = %s";
    $exists = $h->db->query($h->db->prepare($sql, 'HTML_CACHE_ON'));
    if (!$exists) {
        $sql = "INSERT INTO " . DB_PREFIX . "settings (settings_name, settings_value, settings_default, settings_note) VALUES (%s, %s, %s, %s)";
        $h->db->query($h->db->prepare($sql, 'HTML_CACHE_ON', 'true', 'true', 'true/false'));
    }
        
    // add new user_ip field to Users table
    if (!$h->db->column_exists('users', 'user_ip')) {
        $sql = "ALTER TABLE " . DB_PREFIX . "users ADD user_ip varchar(32)  NOT NULL DEFAULT %d AFTER user_permissions";
        $h->db->query($h->db->prepare($sql, 0));
    }
    
    // add new user_ip field to Users table
    if (!$h->db->column_exists('plugins', 'plugin_extends')) {
        $sql = "ALTER TABLE " . DB_PREFIX . "plugins ADD plugin_author varchar(32) NOT NULL DEFAULT %s AFTER plugin_order";
        $h->db->query($h->db->prepare($sql, ''));
        $sql = "ALTER TABLE " . DB_PREFIX . "plugins ADD plugin_authorurl varchar(128) NOT NULL DEFAULT %s AFTER plugin_author";
        $h->db->query($h->db->prepare($sql, ''));
        $sql = "ALTER TABLE " . DB_PREFIX . "plugins ADD plugin_extends varchar(64) NOT NULL DEFAULT %s AFTER plugin_class";
        $h->db->query($h->db->prepare($sql, ''));
        $sql = "ALTER TABLE " . DB_PREFIX . "plugins ADD plugin_type varchar(32) NOT NULL DEFAULT %s AFTER plugin_extends";
        $h->db->query($h->db->prepare($sql, ''));
    }
    
    //correct default for db cache
    $sql = "UPDATE " . DB_PREFIX . "settings SET settings_default = %s WHERE settings_name = %s";
    $h->db->query($h->db->prepare($sql, 'false', 'DB_CACHE_ON'));
}

?>
