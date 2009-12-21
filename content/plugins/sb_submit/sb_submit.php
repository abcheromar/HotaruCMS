<?php
/**
 * name: SB Submit
 * description: Social Bookmarking submit - Enables post submission
 * version: 0.1
 * folder: sb_submit
 * class: SbSubmit
 * type: post
 * hooks: install_plugin, admin_theme_index_top, theme_index_top, header_include, header_include_raw, navigation, admin_header_include_raw, breadcrumbs, theme_index_main, admin_plugin_settings, admin_sidebar_plugin_settings
 * requires: sb_base 0.1
 * author: Nick Ramsay
 * authorurl: http://hotarucms.org/member.php?1-Nick
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

class SbSubmit
{
    /**
     * Install Submit settings if they don't already exist
     */
    public function install_plugin($hotaru)
    {
        // Permissions
        $site_perms = $hotaru->getDefaultPermissions('all');
        if (!isset($site_perms['can_submit'])) { 
            $perms['options']['can_submit'] = array('yes', 'no', 'mod');
            $perms['options']['can_edit_posts'] = array('yes', 'no', 'own');
            $perms['options']['can_delete_posts'] = array('yes', 'no');
            $perms['options']['can_post_without_link'] = array('yes', 'no');
            
            $perms['can_submit']['admin'] = 'yes';
            $perms['can_submit']['supermod'] = 'yes';
            $perms['can_submit']['moderator'] = 'yes';
            $perms['can_submit']['member'] = 'yes';
            $perms['can_submit']['undermod'] = 'mod';
            $perms['can_submit']['default'] = 'no';
            
            $perms['can_edit_posts']['admin'] = 'yes';
            $perms['can_edit_posts']['supermod'] = 'yes';
            $perms['can_edit_posts']['moderator'] = 'yes';
            $perms['can_edit_posts']['member'] = 'own';
            $perms['can_edit_posts']['undermod'] = 'own';
            $perms['can_edit_posts']['default'] = 'no';
            
            $perms['can_delete_posts']['admin'] = 'yes';
            $perms['can_delete_posts']['supermod'] = 'yes';
            $perms['can_delete_posts']['default'] = 'no';
            
            $perms['can_post_without_link']['admin'] = 'yes';
            $perms['can_post_without_link']['supermod'] = 'yes';
            $perms['can_post_without_link']['default'] = 'no';
            
            $hotaru->updateDefaultPermissions($perms);
        }
        

        // Default settings 
        $submit_settings = $hotaru->getSerializedSettings();
        
        if (!isset($submit_settings['enabled'])) { $submit_settings['enabled'] = "checked"; }
        if (!isset($submit_settings['content'])) { $submit_settings['content'] = "checked"; }
        if (!isset($submit_settings['content_length'])) { $submit_settings['content_length'] = 50; }
        if (!isset($submit_settings['summary'])) { $submit_settings['summary'] = "checked"; }
        if (!isset($submit_settings['summary_length'])) { $submit_settings['summary_length'] = 200; }
        if (!isset($submit_settings['allowable_tags'])) { $submit_settings['allowable_tags'] = "<b><i><u><a><blockquote><strike>"; }
        if (!isset($submit_settings['url_limit'])) { $submit_settings['url_limit'] = 0; }
        if (!isset($submit_settings['daily_limit'])) { $submit_settings['daily_limit'] = 0; }
        if (!isset($submit_settings['freq_limit'])) { $submit_settings['freq_limit'] = 0; }
        if (!isset($submit_settings['set_pending'])) { $submit_settings['set_pending'] = ""; } // sets all new posts to pending 
        if (!isset($submit_settings['x_posts'])) { $submit_settings['x_posts'] = 1; }
        if (!isset($submit_settings['email_notify'])) { $submit_settings['email_notify'] = ""; }
        if (!isset($submit_settings['email_notify_mods'])) { $submit_settings['email_notify_mods'] = array(); }
        
        $hotaru->updateSetting('sb_submit_settings', serialize($submit_settings));
    }
    
    
    /**
     * Determine whether or not to show "Submit" in the admin navigation bar
     */
    public function admin_theme_index_top($hotaru)
    {
        /* get submit settings - so we can show or hide "Submit" in the Admin navigation bar. */
        $hotaru->vars['submit_settings'] = $hotaru->getSerializedSettings('sb_submit');
        $hotaru->vars['submission_closed'] = false;
        if (!$hotaru->vars['submit_settings']['enabled']) { $hotaru->vars['submission_closed'] = true; }
    }
    
    
    /**
     * Determine the submission step and perform necessary actions
     */
    public function theme_index_top($hotaru)
    {
        /* get submit settings - available to all because we need to know if submission is 
           open or closed so we can show or hide the navigation bar "Submit" link. */
        $hotaru->vars['submit_settings'] = $hotaru->getSerializedSettings('sb_submit');
        $hotaru->vars['submission_closed'] = false;
        if (!$hotaru->vars['submit_settings']['enabled']) { $hotaru->vars['submission_closed'] = true; }
        
        // Exit if this page name does not contain 'submit' and isn't edit_post
        if ((strpos($hotaru->pageName, 'submit') === false) && ($hotaru->pageName != 'edit_post'))
        {
            return false;
        }
        
        // check user has permission to post. Exit if not.
        $hotaru->vars['posting_denied'] = false;
        if ($hotaru->currentUser->getPermission('can_submit') == 'no') {
            // No permission to submit
            $hotaru->messages[$hotaru->lang['submit_no_post_permission']] = "red";
            $hotaru->vars['posting_denied'] = true;
            return false;
        }
        
        // redirect to log in page if not logged in
        if (!$hotaru->currentUser->loggedIn) { 
            $return = urlencode($hotaru->url(array('page'=>'submit'))); // return user here after login
            header("Location: " . $hotaru->url(array('page'=>'login', 'return'=>$return)));
            return false; 
        }
        
        // return false if submission is closed
        if ($hotaru->vars['submission_closed']) {
            // Submission is closed
            $hotaru->messages[$hotaru->lang["submit_posting_closed"]] = "red";
            return false;
        }
        
        // Include SbSubmitFunctions
        include_once(PLUGINS . 'sb_submit/libs/SbSubmitFunctions.php'); // used for submit functions

        // get functions
        $funcs = new SbSubmitFunctions();

        switch ($hotaru->pageName)
        {
            // SUBMIT STEP 1
            case 'submit':
            case 'submit1':
            
                // set properties
                $hotaru->pageName = 'submit1';
                $hotaru->pageType = 'submit';
                $hotaru->pageTitle = $hotaru->lang["submit_step1"];
                
                // check if data has been submitted
                $submitted = $funcs->checkSubmitted($hotaru, 'submit1');
                
                // save/reload data, then go to step 2 when no more errors
                if ($submitted) {
                    $key = $funcs->processSubmitted($hotaru, 'submit1');
                    $errors = $funcs->checkErrors($hotaru, 'submit1', $key);
                    if (!$errors) {
                        $redirect = htmlspecialchars_decode($hotaru->url(array('page'=>'submit2', 'key'=>$key)));
                        header("Location: " . $redirect);
                        exit;
                    }
                }
                break;
                
            // SUBMIT STEP 2 
            case 'submit2':
            
                // set properties
                $hotaru->pageType = 'submit';
                $hotaru->pageTitle = $hotaru->lang["submit_step2"];
                
                // check if data has been submitted
                $submitted = $funcs->checkSubmitted($hotaru, 'submit2');
                
                // not submitted so reload data from step 1 (or step 2 if editing)
                if (!$submitted) {
                    // if coming from step 1, get the key from the url
                    $key = $hotaru->cage->get->testAlnum('key');
                    
                    // use the key in the step 2 form
                    $hotaru->vars['submit_key'] = $key; 
                    
                    // load submitted data:
                    $submitted_data = $funcs->loadSubmitData($hotaru, $key);
                    
                    // merge defaults from "checkSubmitted" with $submitted_data...
                    $merged_data = array_merge($hotaru->vars['submitted_data'], $submitted_data);
                    $hotaru->vars['submitted_data'] = $merged_data;
                }
                
                // submitted so save data and proceed to step 3 when no more errors
                if ($submitted) {
                    $key = $funcs->processSubmitted($hotaru, 'submit2');
                    $errors = $funcs->checkErrors($hotaru, 'submit2', $key);
                    if (!$errors) {
                        $funcs->processSubmission($hotaru, $key);
                        $postid = $hotaru->post->id; // got this from addPost in Post.php
                        $link = $hotaru->url(array('page'=>'submit3', 'postid'=>$postid,'key'=>$key));
                        $redirect = htmlspecialchars_decode($link);
                        header("Location: " . $redirect);
                        exit;
                    }
                    $hotaru->vars['submit_key'] = $key; // used in the step 2 form
                }
                break;
                
            // SUBMIT STEP 3
            case 'submit3':
            
                $hotaru->pageType = 'submit';
                $hotaru->pageTitle = $hotaru->lang["submit_step3"];
                
                // Check if the Edit button has been clicked
                $funcs = new SbSubmitFunctions();
                $submitted = $funcs->checkSubmitted($hotaru, 'submit3');
                
                // Edit button pressed so save data with newly assigned post id and go back to step 2
                if ($submitted) {
                    $key = $funcs->processSubmitted($hotaru, 'submit3');
                    $funcs->processSubmission($hotaru, $key);
                    $link = $hotaru->url(array('page'=>'submit2', 'key'=>$key));
                    $redirect = htmlspecialchars_decode($link);
                    header("Location: " . $redirect);
                    exit;
                }
                
                // get key from the url for the submit 3 form
                $key = $hotaru->cage->get->testAlnum('key');
                $hotaru->vars['submit_key'] = $key; 
                
                // get post id from the url and read the post for the preview
                $hotaru->post->id = $hotaru->cage->get->testInt('postid');
                $hotaru->readPost();

                break;
                
            // SUBMIT CONFIRM
            case 'submit_confirm':
            
                $post_id = $hotaru->cage->post->testInt('submit_post_id');
                $hotaru->readPost($post_id);
                $hotaru->changePostStatus('new');
                
                $return = 0; // will return false later if set to 1.
                
                $hotaru->pluginHook('submit_step_3_pre_trackback'); // Akismet uses this to change the status
                
                // set to pending?
                $set_pending = $submit_settings['set_pending'];

                if ($set_pending == 'some_pending') {
                    $posts_approved = $hotaru->postsApproved();
                    $x_posts_needed = $submit_settings['x_posts'];
                }

                
                // Set to pending is the user's permissions for "can_submit" are "mod" OR
                // if "Put all new posts in moderation" has been checked in Admin->Submit
                if (   ($hotaru->currentUser->getPermission('can_submit') == 'mod')
                    || ($set_pending == 'all_pending')
                    || (($set_pending == 'some_pending') && ($posts_approved <= $x_posts_needed)))
                {
                // Submitted posts given 'pending' for this user
                    $hotaru->changePostStatus('pending');
                    $hotaru->messages[$hotaru->lang['submit_form_moderation']] = 'green';
                    $return = 1; // will return false just after we notify admins of the post (see about 10 lines down)
                }

                // notify chosen mods of new post by email if enabled and UserFunctions file exists
                /*
                if (($submit_settings['email_notify']) && (file_exists(PLUGINS . 'users/libs/UserFunctions.php')))
                {
                    require_once(PLUGINS . 'users/libs/UserFunctions.php');
                    $uf = new UserFunctions($hotaru);
                    $uf->notifyMods('post', $hotaru->post->status, $hotaru->post->id);
                }
                */
                
                if ($return == 1) { return false; } // post is pending so we don't want to send a trackback. Return now.
                
                $hotaru->sendTrackback();
                
                header("Location: " . $hotaru->url(array('page'=>'latest')));    // Go to the Latest page
                break;
                
            // EDIT POST (after submission)
            case 'edit_post':

                $hotaru->pageType = 'submit';
                $hotaru->pageTitle = $hotaru->lang["submit_edit_title"];
                
                // get the post id and read in the data
                if ($hotaru->cage->get->keyExists('post_id')) {
                    $hotaru->post->id = $hotaru->cage->get->testInt('post_id');
                    $hotaru->readPost();

                    // authenticate...
                    $can_edit = false;
                    if ($hotaru->currentUser->getPermission('can_edit_posts') == 'yes') { $can_edit = true; }
                    if (($hotaru->currentUser->getPermission('can_edit_posts') == 'own') && ($hotaru->currentUser->id == $hotaru->post->author)) { $can_edit = true; }
                    $hotaru->vars['can_edit'] = $can_edit; // used in theme_index_main()
                    
                    if (!$can_edit) {
                        $hotaru->messages[$hotaru->lang["submit_no_edit_permission"]] = "red";
                        return false;
                        exit;
                    }
                }
                
                // check if data has been submitted
                $submitted = $funcs->checkSubmitted($hotaru, 'edit_post');
                
                // if being deleted...
                $hotaru->vars['post_deleted'] = false;
                if ($hotaru->cage->get->getAlpha('action') == 'delete') {
                    if ($hotaru->currentUser->getPermission('can_delete_posts') == 'yes') { // double-checking
                        $post_id = $hotaru->cage->get->testInt('post_id');
                        $hotaru->readPost($post_id); 
                        $hotaru->pluginHook('sb_submit_edit_delete'); // Akismet uses this to report the post as spam
                        $hotaru->deletePost(); 
                        $hotaru->messages[$hotaru->lang["submit_edit_deleted"]] = 'red';
                        $hotaru->vars['post_deleted'] = true;
                        break;
                    }
                }
                
                // if form has been submitted...
                if ($submitted) {
                    $key = $funcs->processSubmitted($hotaru, 'edit_post');
                    $errors = $funcs->checkErrors($hotaru, 'edit_post', $key);
                    if (!$errors) {
                        $funcs->processSubmission($hotaru, $key);
                        if ($hotaru->cage->post->testAlnumLines('from') == 'post_man')
                        {
                            // Build the redirect link to send us back to Post Manager
                            
                            $redirect = BASEURL . "admin_index.php?page=plugin_settings&plugin=post_manager";
                            if ($hotaru->cage->post->testAlnumLines('post_status_filter')) {
                                $redirect .= "&type=filter";
                                $redirect .= "&post_status_filter=" . $hotaru->cage->post->testAlnumLines('post_status_filter');
                            }
                            if ($hotaru->cage->post->getMixedString2('search_value')) {
                                $redirect .= "&type=search";
                                $redirect .= "&search_value=" . $hotaru->cage->post->getMixedString2('search_value');
                            }
                            $redirect .= "&pg=" . $hotaru->cage->post->testInt('pg');
                            header("Location: " . $redirect);    // Go back to where we were in Post Manager
                            exit;
                        }
                        else 
                        {
                            $redirect = htmlspecialchars_decode($hotaru->url(array('page'=>$hotaru->post->id)));
                            header("Location: " . $redirect);
                            exit;
                        }
                    }
                    
                    // load submitted data:
                    $submitted_data = $funcs->loadSubmitData($hotaru, $key);
                }
                
            break;
        }
    }


    /**
     * Include jQuery for hiding and showing email options in plugin settings
     */
    public function admin_header_include_raw($hotaru)
    {
        if ($hotaru->isSettingsPage('sb_submit')) {
            echo "<script type='text/javascript'>\n";
            echo "$(document).ready(function(){\n";
                echo "$('#email_notify').click(function () {\n";
                echo "$('#email_notify_options').slideToggle();\n";
                echo "});\n";
            echo "});\n";
            echo "</script>\n";
        }
    }
    
    
    /**
     * Output raw javascript directly to the header (instead of caching a .js file)
     */
    public function header_include_raw($hotaru)
    {
        /* This code (courtesy of Pligg.com and SocialWebCMS.com) pops up a 
           box asking the user of they are sure they want to leave the page
           without submitting their post. */
           
        if ($hotaru->pageName == 'submit2' || $hotaru->pageName == 'submit3') {
            echo '
                <script type="text/javascript">
        
                var safeExit = false;
            
                window.onbeforeunload = function (event) 
                {
                    if (safeExit)
                        return;
        
                    if (!event && window.event) 
                              event = window.event;
                              
                       event.returnValue = "' . $hotaru->lang['submit_accidental_click'] . '";
                }
                
                </script>
            ';
        }
    }
    
    
    /**
     * Add "Submit" to the navigation bar
     */
    public function navigation($hotaru)
    {
        // return false if not logged in or submission disabled
        if (!$hotaru->currentUser->loggedIn) { return false; }
        if (isset($hotaru->vars['submission_closed']) && $hotaru->vars['submission_closed'] == true) { return false; }
        
        // highlight "Submit" as active tab
        if ($hotaru->pageType == 'submit') { $status = "id='navigation_active'"; } else { $status = ""; }
        
        // display the link in the navigation bar
        echo "<li><a  " . $status . " href='" . $hotaru->url(array('page'=>'submit')) . "'>" . $hotaru->lang['submit_submit_a_story'] . "</a></li>\n";
    }
    
    
    /**
     * Replace the default breadcrumbs in Edit Post
     */
    public function breadcrumbs($hotaru)
    {
        if ($hotaru->pageName == 'edit_post') {
            $post_link = "<a href='" . $hotaru->url(array('page'=>$hotaru->post->id)) . "'>";
            $post_link .= $hotaru->post->title . "</a>";
            $hotaru->pageTitle = $hotaru->pageTitle . " &raquo; " . $post_link;
        }
    }
    
    
    /**
     * Determine which template to show and do preparation of variables, etc.
     */
    public function theme_index_main($hotaru)
    {
        // show message and exit if posting denied (determined in theme_index_top)
        if ($hotaru->pageType == 'submit' && $hotaru->vars['posting_denied']) {
            $hotaru->showMessages();
            return true;
        }
        
        switch ($hotaru->pageName)
        {
            // Submit Step 1
            case 'submit':
            case 'submit1':
            
                if ($hotaru->vars['submission_closed'] || $hotaru->vars['posting_denied']) {
                    $hotaru->showMessages();
                    return true;
                }
            
                // display template
                $hotaru->displayTemplate('sb_submit1');
                return true;
                break;
                
            // Submit Step 2
            case 'submit2':
            
                if ($hotaru->vars['submission_closed'] || $hotaru->vars['posting_denied']) {
                    $hotaru->showMessages();
                    return true;
                }
            
                // settings
                $hotaru->vars['submit_use_content'] = $hotaru->vars['submit_settings']['content'];
                $hotaru->vars['submit_content_length'] = $hotaru->vars['submit_settings']['content_length'];
                $allowable_tags = $hotaru->vars['submit_settings']['allowable_tags'];
                $hotaru->vars['submit_allowable_tags'] = htmlentities($allowable_tags);
                
                // submitted data
                $hotaru->vars['submit_editorial'] = $hotaru->vars['submitted_data']['submit_editorial'];
                $hotaru->vars['submit_orig_url'] = urldecode($hotaru->vars['submitted_data']['submit_orig_url']);
                $hotaru->vars['submit_title'] = sanitize($hotaru->vars['submitted_data']['submit_title'], 1);
                $hotaru->vars['submit_content'] = sanitize($hotaru->vars['submitted_data']['submit_content'], 1);
                $hotaru->vars['submit_post_id'] = $hotaru->vars['submitted_data']['submit_id'];
                
                // strip htmlentities before showing in the form:
                $hotaru->vars['submit_title'] = html_entity_decode($hotaru->vars['submit_title']);
                $hotaru->vars['submit_content'] = html_entity_decode($hotaru->vars['submit_content']);
                
                // display template
                $hotaru->displayTemplate('sb_submit2');
                return true;
                break;
                
            // Submit Step 3
            case 'submit3':
            
                if ($hotaru->vars['submission_closed'] || $hotaru->vars['posting_denied']) {
                    $hotaru->showMessages();
                    return true;
                }
            
                // need these for the post preview (which uses SB Base's sb_post.php template)
                $hotaru->vars['use_content'] = $hotaru->vars['submit_settings']['content'];
                $hotaru->vars['summary_length'] = $hotaru->vars['submit_settings']['summary_length'];
                $hotaru->vars['editorial'] = true; // this makes the link unclickable
                
                // display template
                $hotaru->displayTemplate('sb_submit3');
                return true;
                break;
                
            // Edit Post
            case 'edit_post':
            
                if ($hotaru->vars['post_deleted'] || !$hotaru->vars['can_edit']) {
                    $hotaru->showMessages();
                    return true;
                }
                
                // settings
                $hotaru->vars['submit_use_content'] = $hotaru->vars['submit_settings']['content'];
                $hotaru->vars['submit_content_length'] = $hotaru->vars['submit_settings']['content_length'];
                $allowable_tags = $hotaru->vars['submit_settings']['allowable_tags'];
                $hotaru->vars['submit_allowable_tags'] = htmlentities($allowable_tags);
                
                $hotaru->vars['submit_orig_url'] = $hotaru->post->origUrl;
                $hotaru->vars['submit_title'] = $hotaru->post->title;
                $hotaru->vars['submit_content'] = $hotaru->post->content;
                $hotaru->vars['submit_post_id'] = $hotaru->post->id;
                $hotaru->vars['submit_status'] = $hotaru->post->status;
                
                $hotaru->vars['submit_editorial'] = $hotaru->vars['submitted_data']['submit_editorial'];
                $hotaru->vars['submit_pm_from'] = $hotaru->vars['submitted_data']['submit_pm_from'];
                $hotaru->vars['submit_pm_search'] = $hotaru->vars['submitted_data']['submit_pm_search']; 
                $hotaru->vars['submit_pm_filter'] = $hotaru->vars['submitted_data']['submit_pm_filter'];
                $hotaru->vars['submit_pm_page'] = $hotaru->vars['submitted_data']['submit_pm_page'];
                
                // strip htmlentities before showing in the form:
                $hotaru->vars['submit_title'] = html_entity_decode($hotaru->vars['submit_title']);
                $hotaru->vars['submit_content'] = html_entity_decode($hotaru->vars['submit_content']);
                
                // get status options for admin section
                $hotaru->vars['submit_status_options'] = '';
                if ($hotaru->currentUser->getPermission('can_edit_posts') == 'yes') {
                    $statuses = $hotaru->post->getUniqueStatuses($hotaru); 
                    if ($statuses) {
                        foreach ($statuses as $status) {
                            if ($status != 'unsaved' && $status != 'processing' && $status != $hotaru->vars['submit_status']) { 
                                $hotaru->vars['submit_status_options'] .= "<option value=" . $status . ">" . $status . "</option>\n";
                            }
                        }
                    }
                }
                
                // display template
                $hotaru->displayTemplate('sb_submit_edit');
                return true;
                break;
        }
    }
}
?>