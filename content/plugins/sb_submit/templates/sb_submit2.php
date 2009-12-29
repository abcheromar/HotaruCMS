<?php
/**
 * Template for Submit: Submit Step 2
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

$h->pluginHook('submit_2_assign');

?>
    <?php $h->showMessages(); ?>
    
    <?php echo $h->lang["submit_instructions_2"]; ?>

    <form name='submit_2' action='<?php echo BASEURL; ?>index.php?page=submit2' method='post'>
    <table>
    
    <?php if (!$h->vars['submit_editorial']) { // only show if posting a link ?>
        <tr>
            <td><?php echo $h->lang["submit_url"]; ?>&nbsp; </td>
            <td><?php echo $h->vars['submit_orig_url']; ?></td>
            <td>&nbsp;</td>
        </tr>
    <?php } ?>
    
    <tr>
        <td><?php echo $h->lang["submit_title"]; ?>&nbsp; </td>
        <td><input type='text' id='post_title' name='post_title' value='<?php echo $h->vars['submit_title']; ?>'></td>
        <td id='ajax_loader'>&nbsp;</td>
    </tr>
    
    <?php if ($h->vars['submit_use_content']) { ?>
    <tr>
        <td style='vertical-align: top;'><?php echo $h->lang["submit_content"]; ?>&nbsp; </td>
        <td colspan='2'>
            <textarea id='post_content' name='post_content' rows='6' maxlength='<?php echo $h->vars['submit_content_length']; ?>'><?php echo $h->vars['submit_content']; ?></textarea>
        </td>
    </tr>
    
    <tr>
        <td>&nbsp;</td>
        <td colspan=2 style='vertical-align: top;' class="submit_instructions">
            <?php echo $h->lang['submit_allowable_tags']; ?>
            <?php echo $h->vars['submit_allowable_tags']; ?>
        </td>
    </tr>
    <?php } ?>
    
    
    <?php if ($h->vars['submit_use_categories']) { ?>
    <tr>
        <td style='vertical-align: top;'><?php echo $h->lang["submit_category"]; ?>&nbsp; </td>
        <td><select name='post_category'>
            <?php
                $sql = "SELECT category_name, category_safe_name FROM " . TABLE_CATEGORIES . " WHERE category_id = %d";
                $result = $h->db->get_row($h->db->prepare($sql, $h->vars['submit_category']));
                
                $category_safe_name = stripslashes(htmlentities(urldecode($result->category_safe_name), ENT_QUOTES,'UTF-8'));
                
                if ($category_safe_name == 'all') { 
                    echo "<option value='1' selected>" . $h->lang['submit_category_select'] . "</option>\n";
                } else {
                    echo "<option value=" . $h->vars['submit_category'] . " selected>" . $result->category_name . "</option>\n";
                }
                
                $sql = "SELECT category_id, category_name FROM " . TABLE_CATEGORIES . " ORDER BY category_order ASC";
                $cats = $h->db->get_results($h->db->prepare($sql));
                
                if ($cats) {
                    foreach ($cats as $cat) {
                        if ($cat->category_id != 1) { 
                            $cat_name = stripslashes(htmlentities(urldecode($cat->category_name), ENT_QUOTES,'UTF-8'));
                            echo "<option value=" . $cat->category_id . ">" . $cat_name . "</option>\n";
                        }
                    }
                }
            ?>
        </select></td>
    </tr>
    <?php } ?>
    
    <?php
        if ($h->vars['submit_use_tags']) { 
            echo "<tr>";
                echo "<td>" . $h->lang["submit_tags"] . "&nbsp; </td>";
                echo "<td><input type='text' id='post_tags' name='post_tags' value='" . $h->vars['submit_tags'] . "'></td>";
                echo "<td>&nbsp;</td>";
            echo "</tr>";
        }
    ?>
    
    <?php $h->pluginHook('submit_2_fields'); ?>
            
    <input type='hidden' name='submit_orig_url' value='<?php echo $h->vars['submit_orig_url']; ?>' />
    <input type='hidden' name='submit_post_id' value='<?php echo $h->vars['submit_post_id']; ?>' />
    <input type='hidden' name='submit2' value='true' />
    <input type='hidden' name='submit_key' value='<?php echo $h->vars['submit_key']; ?>' />
    <input type='hidden' name='csrf' value='<?php echo $h->csrfToken; ?>' />
    
    <tr><td>&nbsp; </td><td>&nbsp; </td><td style='text-align:right;'><input type='submit' onclick="javascript:safeExit=true;" class='submit' name='submit' value='<?php echo $h->lang['main_form_next']; ?>' /></td></tr>    
    </table>
    </form>
