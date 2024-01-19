<?php

/*
    Plugin Name: Word Count
    Description: A truly amazing plugin.
    Version: 1.0
    Author: Nikola Crvenkov
    Author URI: https://github.com/nCrvenkov
    Text Domain: wcpdomain
    Domain Path: /languages
*/

class WordCountAndTimePlugin {
    function __construct(){
        add_action('admin_menu', array($this, 'adminPage')); // to create menu page
        add_action('admin_init', array($this, 'settings')); // to add settings - options
        add_filter('the_content', array($this, 'ifWrap')); // to manipulate the content
        add_action('init', array($this, 'languages')); // to add support for languages

        /* $this is used when we don't want to call the method at that moment, we are just passing a reference.
        therefore, WordPress will call the method at the right time, when it's needed. */
    }

    function languages(){
        load_plugin_textdomain('wcpdomain', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    // function that will execute on posts
    function ifWrap($content){
        if((is_main_query() AND is_single()) AND 
        (
            get_option('wcp_wordcount', '1') OR 
            get_option('wcp_charactercount', '1') OR 
            get_option('wcp_readtime', '1')
            )){
                return $this->createHTML($content);
                // here we wanted to call the method to execute immediately. Unlike when $this is used.
        }
        return $content;
    }

    // to add to HTML content
    function createHTML($content){
        $html = "<h3>". esc_html(get_option('wcp_headline', 'Post Statistics')) ."</h3><p>";

        // get word count once because both wordcound and read time will need it.
        if(get_option('wcp_wordcount', '1') OR get_option('wcp_readtime', '1')){
            $wordCount = str_word_count(strip_tags($content));
        }

        if(get_option('wcp_wordcount', '1')){
            $html .= esc_html__('This post has', 'wcpdomain') . ' ' . $wordCount . ' ' . esc_html__('words', 'wcpdomain') . '.<br/>';
        }

        if(get_option('wcp_charactercount', '1')){
            $html .= 'This post has ' . strlen(strip_tags($content)) . ' characters.<br/>';
        }

        if(get_option('wcp_readtime', '1')){
            $readtime = $wordCount / 225;
            $html .= 'This post will take about ' . round($readtime, 1) . ' minute(s) to read.<br/>';
        }

        $html .= "</p>";

        if(get_option('wcp_location', '0') == '0'){
            return $html . $content;
        }
        else{
            return $content . $html;
        }
    }

    function settings(){
        // register section
        add_settings_section('wcp_first_section', null, null, 'word-count-settings-page'); // name of the section; sub-title; some content? ; slug for location

        // build HTML input field for form
        add_settings_field('wcp_location', 'Display Location', array($this, 'locationHTML'), 'word-count-settings-page', 'wcp_first_section');  // actual name of setting; label; FUNCTION with HTML; slug for location; registered section location
        register_setting('wordcountplugin', 'wcp_location', array('sanitize_callback' => array($this, 'sanitizeLocation'), 'default' => '0')); // name of the group; actual name of setting; values

        add_settings_field('wcp_headline', 'Headline Text', array($this, 'headlineHTML'), 'word-count-settings-page', 'wcp_first_section');  
        register_setting('wordcountplugin', 'wcp_headline', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'Post Statistics')); 

        add_settings_field('wcp_wordcount', 'Word Count', array($this, 'checkboxHTML'), 'word-count-settings-page', 'wcp_first_section', array("theName" => 'wcp_wordcount'));  // last property is an array - args
        register_setting('wordcountplugin', 'wcp_wordcount', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1')); 

        add_settings_field('wcp_charactercount', 'Character count', array($this, 'checkboxHTML'), 'word-count-settings-page', 'wcp_first_section', array("theName" => 'wcp_charactercount'));   
        register_setting('wordcountplugin', 'wcp_charactercount', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1')); 

        add_settings_field('wcp_readtime', 'Read time', array($this, 'checkboxHTML'), 'word-count-settings-page', 'wcp_first_section', array("theName" => 'wcp_readtime'));  
        register_setting('wordcountplugin', 'wcp_readtime', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1')); 
    }

    function sanitizeLocation($input){
        if($input != '0' AND $input != '1'){
            add_settings_error('wcp_location', 'wcp_location_error', 'Display location must be either beginning or end.');
            return get_option('wcp_location');
        }
        else{
            return $input;
        }
    }

    function locationHTML(){?>
        <select name="wcp_location">
            <option value="0" <?php selected(get_option('wcp_location'), '0'); ?>>Beginning of post</option> 
            <option value="1" <?php selected(get_option('wcp_location'), '1'); ?>>End of post</option>
        </select>
    <?php }

    function headlineHTML($args){?>
        <input type="text" name="wcp_headline" value="<?php echo esc_attr(get_option('wcp_headline')); ?>">
    <?php }

    function checkboxHTML($args){?>
        <input type="checkbox" name="<?= $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']), '1') ?> >
    <?php }

    // HOOK for the plugin settings page content
    function adminPage(){
        add_options_page('Word Count Settins', __('Word Count', 'wcpdomain'), 'manage_options', 'word-count-settings-page', array($this, 'ourHTML')); // title ; menu title ; user capabilities ; slug ; function for content
    }
    
    // function for HTML of the plugin settings page
    function ourHTML(){ ?>
        <div class="wrap">
            <h1>Word Count Settings</h1>
            <form action="options.php" method="post">
                <?php
                    // to handle everything
                    settings_fields('wordcountplugin');
                    // to call the created section on the slug location
                    do_settings_sections('word-count-settings-page');
                    // wordpress submit
                    submit_button();
                ?>
            </form>
        </div>
    <?php }
}

$wordCountAndTimePlugin = new WordCountAndTimePlugin();

