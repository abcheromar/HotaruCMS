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
$h = new Hotaru(); // must come before language inclusion
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
        do_upgrade($old_version);
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
function do_upgrade($old_version)
{
    global $h;

    // can't upgrade from pre-1.0 versions of Hotaru.
    
    // 1.0 to 1.0.1
    if ($old_version == "1.0") {

        // Change "positive" to 10
        $sql = "UPDATE " . TABLE_POSTVOTES . " SET vote_rating = %d WHERE vote_rating = %s";
        $h->db->query($h->db->prepare($sql, 10, 'positive'));
        
        // Change "negative" to -10
        $sql = "UPDATE " . TABLE_POSTVOTES . " SET vote_rating = %d WHERE vote_rating = %s";
        $h->db->query($h->db->prepare($sql, -10, 'negative'));
        
        // Change "alert" to -999
        $sql = "UPDATE " . TABLE_POSTVOTES . " SET vote_rating = %d WHERE vote_rating = %s";
        $h->db->query($h->db->prepare($sql, -999, 'alert'));
        
        // Alter the PostVotes table so the vote rating is an INT
        $sql = "ALTER TABLE " . TABLE_POSTVOTES . " CHANGE vote_rating vote_rating smallint(11) NOT NULL DEFAULT %d";
        $h->db->query($h->db->prepare($sql, 0));
        
        // Update Hotaru version number to the database (referred to when upgrading)
        $sql = "UPDATE " . TABLE_MISCDATA . " SET miscdata_key = %s, miscdata_value = %s, miscdata_default = %s WHERE miscdata_key = %s";
        $h->db->query($h->db->prepare($sql, 'hotaru_version', $h->version, $h->version, 'hotaru_version'));
    
        // check there are default permissions present and add if necessary
        $sql = "SELECT miscdata_id FROM " . TABLE_MISCDATA . " WHERE miscdata_key = %s";
        $result = $h->db->get_var($h->db->prepare($sql, 'permissions'));
        if (!$result) {
            // Default permissions
            $perms['options']['can_access_admin'] = array('yes', 'no');
            $perms['can_access_admin']['admin'] = 'yes';
            $perms['can_access_admin']['supermod'] = 'yes';
            $perms['can_access_admin']['default'] = 'no';
            $perms = serialize($perms);
            
            $sql = "INSERT INTO " . TABLE_MISCDATA . " (miscdata_key, miscdata_value, miscdata_default) VALUES (%s, %s, %s)";
            $h->db->query($h->db->prepare($sql, 'permissions', $perms, $perms));
        }
        
        // check there are default user_settings present and add if necessary
        $sql = "SELECT miscdata_id FROM " . TABLE_MISCDATA . " WHERE miscdata_key = %s";
        $result = $h->db->get_var($h->db->prepare($sql, 'user_settings'));
        if (!$result) {
            // default settings
            $sql = "INSERT INTO " . TABLE_MISCDATA . " (miscdata_key, miscdata_value, miscdata_default) VALUES (%s, %s, %s)";
            $h->db->query($h->db->prepare($sql, 'user_settings', '', ''));
        }
        
    }
}

?>
