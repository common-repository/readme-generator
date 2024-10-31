<?php

/*
Plugin Name: Readme Generator
Plugin URI: http://www.houseindorset.co.uk/plugins/readme-generator
Description: Generate a plugins Readme.txt file based on a Wordpress Page
Version: 1.0.2
Text Domain: readme-gen
Author: Paul Stuttard
Author URI: http://www.houseindorset.co.uk
License: GPL2
*/

/*
Copyright 2011-2012 Paul Stuttard (email : wordpress_readmegen @ redtom.co.uk)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*


*/
require_once('include/display-form.php');

if (!class_exists('readme_gen_for_wordpress')) {
   class readme_gen_for_wordpress{

/*****************************************************************************************/
      /// Settings:
/*****************************************************************************************/

      var $option_name     = 'readme-gen-options';
      var $settings_slug   = 'readme-gen-options';
      var $opts            = null;
      var $options_version = '1';
      var $plugin_version  = '1.0.1';
/*****************************************************************************************/
      // Constructor for the Plugin

      function __construct() {
         $this->url_root   = plugins_url("", __FILE__);
         $this->base_name  = plugin_basename( __FILE__ );
         $this->plugin_dir = dirname( $this->base_name );
         $this->form       = new readme_gen_form;
         $this->filename  = __FILE__;

         add_action( 'admin_init', array($this, 'init'));                               // Load i18n and initialise translatable vars
         add_filter( 'plugin_row_meta', array($this, 'register_plugin_links'),10,2);    // Add extra links to plugins page
         add_action( 'admin_menu', array($this, 'setup_options'));                      // Add options page hooks
         add_action( 'save_post', array($this, 'save_form') );                          // Add post form save hook
      }

/*****************************************************************************************/

      // Functions for the above hooks
 
      // On wordpress initialisation - load text domain and register styles & scripts
      function init() {

         /* load localisation  */
         load_plugin_textdomain('readme-gen', $this->plugin_dir . '/i18n', $this->plugin_dir . '/i18n');

         // Initialise dependent classes
         $this->form->init($this);

         $this->required_sections = array('Description', 'Installation', 'Frequently Asked Questions', 'Screenshots', 'Changelog', 'Upgrade Notice');

         // Register our styles and scripts
         $script = plugins_url("readme-gen.js", __FILE__);
         // Allow the user to override our default styles. 
         if (file_exists(dirname (__FILE__).'/user_styles.css')) {
            $stylesheet = plugins_url("user_styles.css", __FILE__); 
         } else {
            $stylesheet = plugins_url("readme-gen.css", __FILE__);
         }

         wp_register_style('readme-gen-style', $stylesheet, false, $this->plugin_version);
         wp_register_script('readme-gen-script', $script, false, $this->plugin_version);

         add_action('wp_ajax_readme_gen_generate', array($this, 'generate_readme'));      // Handle ajax requests
      }

      // If in admin section then register options page and required styles & metaboxes
      function setup_options() {

         // Add plugin options page, with load hook to bring in meta boxes and scripts and styles
         $this->opts_page = add_options_page( __('Manage Readme Gen Options', 'readme-gen'), __('Readme Gen', 'readme-gen'), 'manage_options', $this->settings_slug, array($this, 'show_options_page'));
         add_action('load-'.$this->opts_page, array(&$this, 'options_load'));
         add_action( "admin_print_styles-" . $this->opts_page, array($this,'form_styles') );
         add_action( "admin_print_scripts-" . $this->opts_page, array($this,'form_scripts') );

         // Add support for Post/Page edit metabox, this requires our styles and post edit AJAX scripts.
         add_meta_box('readme-gen-id', __('Generate Readme File', 'readme-gen'), array($this,'insert_form'), 'post', 'normal');
         add_meta_box('readme-gen-id', __('Generate Readme File', 'readme-gen'), array($this,'insert_form'), 'page', 'normal');

         add_action( "admin_print_scripts-post.php", array($this,'form_scripts') );
         add_action( "admin_print_scripts-post-new.php", array($this,'form_scripts') );
         add_action( "admin_print_styles-post-new.php", array($this,'form_styles') );
         add_action( "admin_print_styles-post.php", array($this,'form_styles') );


      }

      // Hooks required to bring up options page with meta boxes:
      function options_load () {

         add_filter('screen_layout_columns', array(&$this, 'admin_columns'), 10, 2);

         wp_enqueue_script('common');
         wp_enqueue_script('wp-lists');
         wp_enqueue_script('postbox');

         add_meta_box( 'readme-gen-options', __( 'Options', 'readme-gen' ), array (&$this, 'show_options' ), $this->opts_page, 'advanced', 'core' );
         add_meta_box( 'readme-gen-info', __( 'About', 'readme-gen' ), array (&$this, 'show_info' ), $this->opts_page, 'side', 'low' );
      }

      function admin_columns($columns, $screen) {
         if ($screen == $this->opts_page) {
            $columns[$this->opts_page] = 2;
         }
   return $columns;
      }

      /// We only need the styles and scripts on post/page and options screens

      function form_styles() {
         wp_enqueue_style('readme-gen-style');
         $this->form->enqueue_styles();
      }

      function form_scripts() {
         wp_enqueue_script('readme-gen-script');
         $this->form->enqueue_scripts();
      }

      function register_plugin_links($links, $file) {
         if ($file == $this->base_name) {
            $links[] = '<a href="options-general.php?page=' . $this->settings_slug .'">' . __('Settings','readme-gen') . '</a>';
         }
         return $links;
      }


/*****************************************************************************************/
      /// Options & Templates Handling
/*****************************************************************************************/

      function get_form_option_list() {
     
         /* This defines the options table shown in the Readme.txt widget */
         if (!isset($this->form_option_list)) {
            $results_html = '<img style="float:right" alt="" title="" id="readme-gen-status" class="ajax-feedback " src="images/wpspin_light.gif" />'.
                            '<div style="clear:both" id="readme-gen-results-content"></div>';
            $this->form_option_list = array(
               'nonce'             => array ( 'Type' => 'nonce', 'Action' => $this->base_name, 'Name' => 'update-readme-gen-form' ),
               'rg_Post'           => array ( 'Id' => 'readme-gen-opt', 'Type' => 'hidden'  ),
               'rg_PostTemplate'   => array ( 'Id' => 'readme-gen-opt', 'Type' => 'hidden'  ),
               'rg_Enabled'        => array ( 'Id' => 'readme-gen-opt', 'Type' => 'checkbox', 'Name' => __('Enabled', 'readme-gen'), 'Hint' => __('Select to enable Readme Generator Options', 'readme-gen'), 'Default' => '0', 
                                              'Script' => 'return readme_gen.toggleAdvanced(this.form);'),
               'starts1'           => array ( 'Type' => 'section', 'Value' => '', 'Id' => 'readme-gen-options', 'Section_Class' => 'hidden'),
               'subhd1'            => array ( 'Type' => 'title', 'Value' => __('Plugin Header Information', 'readme-gen'), 'Id' => 'readme-gen-options', 'Title_Class' => 'sub-head'),
               'rg_GenerateReadMe' => array ( 'Id' => 'readme-gen-opt', 'Type' => 'checkbox', 'Name' => __('Generate Read Me', 'readme-gen'), 'Hint' => __('Enable this to create the readme.txt file.', 'readme-gen'), 'Default' => '1' ),
               'rg_Name'           => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Plugin Name', 'readme-gen'), 'Hint' => __('Name of the plugin', 'readme-gen'), 'Default' => ''),
               'rg_Contributors'   => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Contributors', 'readme-gen'), 'Hint' => __('Plugin contributors', 'readme-gen')),
               'rg_Donate'         => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Donate Link', 'readme-gen'), 'Hint' => __('Link to Donation Page', 'readme-gen')),
               'rg_Tags'           => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Tags', 'readme-gen'), 'Hint' => __('Comma separated list of tags', 'readme-gen')),
               'rg_Requires'       => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Required Version', 'readme-gen'), 'Hint' => __('Minimum Version of Wordpress required to work', 'readme-gen')),
               'rg_Tested'         => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Tested Version', 'readme-gen'), 'Hint' => __('Version of Wordpress tested against.', 'readme-gen')),
               'rg_Stable'         => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Stable Version', 'readme-gen'), 'Hint' => __('Stable version of the Plugin', 'readme-gen'), 'Default' => ''),
               'subhd2'            => array ( 'Type' => 'title', 'Value' => __('Settings to Modify Generator Behaviour', 'readme-gen'), 'Title_Class' => 'sub-head'),
               'rg_File'           => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Readme Location', 'readme-gen'), 'Hint' => __('Location to place readme.txt, relative to the plugin directory', 'readme-gen'), 'Default' => '' ),
               'rg_GenerateHelp'   => array ( 'Id' => 'readme-gen-opt', 'Type' => 'checkbox', 'Name' => __('Generate Help Files', 'readme-gen'), 'Hint' => __('Enable this to create the help files.', 'readme-gen'), 'Default' => '0' ),
               'rg_help_filename'  => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Context Help File Location', 'readme-gen'), 'Hint' => __('Location to place contextual help files relative to the plugin directory', 'readme-gen'), 'Default' => '' ),
               'rg_Help'           => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Help Sections', 'readme-gen'), 'Hint' => __('Sections in this post that should be included in the Contextual Help File', 'readme-gen'), 'Default' => '' ),
               'rg_Remove'         => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Ignore Sections', 'readme-gen'), 'Hint' => __('Sections in this post that should not be included in the readme.txt', 'readme-gen'), 'Default' => '' ),
               'rg_Subpages'       => array ( 'Id' => 'readme-gen-opt', 'Type' => 'checkbox', 'Name' => __('Parse Sub-pages', 'readme-gen'), 'Hint' => __('Parse any child pages to this one and add to the readme file', 'readme-gen'), 'Default' => '1' ),
               'rg_CreatePOT'      => array ( 'Id' => 'readme-gen-opt', 'Type' => 'checkbox', 'Name' => __('Create POT', 'readme-gen'), 'Hint' => __('Enable this to find all i18n strings and Generate a POT file', 'readme-gen'), 'Default' => '0' ),
               'rg_POTFile'        => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('POT Location', 'readme-gen'), 'Hint' => __('Location to place POT file, relative to the plugin directory', 'readme-gen'), 'Default' => '' ),
               'button'            => array ( 'Type' => 'buttons', 'Buttons' => array( 
                                             __('Generate', 'readme-gen' ) => array( 'Type' => 'button', 'Id' => 'generate-button', 'Class' => 'button-secondary', 'Script' => 'return readme_gen.generate_readme();'),
                                             __('Template', 'readme-gen' ) => array( 'Type' => 'button', 'Id' => 'template-button', 'Class' => 'button-secondary', 'Script' => 'return readme_gen.insert_template();')
                                            )),
               'results'           => array ( 'Id' => 'readme-gen-results', 'Type' => 'title', 'Value' => $results_html, 'Title_Class' => 'hide-if-js'),
               'ends1'             => array ( 'Type' => 'end' ),
            );
         }
         return $this->form_option_list;
      }

      function get_option_list() {
     
         if (!isset($this->option_list)) {

            $this->option_list = array(

            /* Hidden Options - not saved in Settings */

            'nonce' => array ( 'Type' => 'nonce', 'Name' => 'update-readme-gen-options' ),

            /* Options that change how the items are displayed */
            'hd1s' => array ( 'Type' => 'section', 'Value' => __('Default Plugin Options', 'readme-gen'), 'Section_Class' => 'al_subhead1'),
            'rg_Contributors'   => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Contributors', 'readme-gen'), 'Description' => __('Default Plugin contributors', 'readme-gen'), 'Default' => '', 'Class' => 'al_border'),
            'rg_Donate'         => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Donate Link', 'readme-gen'), 'Description' => __('Default Link to Donation Page', 'readme-gen'), 'Default' => home_url(), 'Class' => 'alternate al_border'),
            'rg_Tags'           => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Tags', 'readme-gen'), 'Description' => __('Tags that are common to alot of your plugins.', 'readme-gen'), 'Default' => '', 'Class' => 'al_border'),
            'rg_Requires'       => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Required Version', 'readme-gen'), 'Description' => __('Default minimum version of Wordpress required to work', 'readme-gen'), 'Default' => '', 'Class' => 'al_border alternate'),
            'rg_Tested'         => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Tested Version', 'readme-gen'), 'Description' => __('Default version of Wordpress tested against.', 'readme-gen'), 'Default' => '', 'Class' => 'al_border'),
            'rg_Header'         => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Heading Type', 'readme-gen'), 'Description' => __('Type of HTML element for Top Level Heading', 'readme-gen'), 'Default' => 'h3', 'Class' => 'alternate al_border' ),
            'rg_SubHeader'      => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Sub-Heading Type', 'readme-gen'), 'Description' => __('Type of HTML element for Sub Headings', 'readme-gen'), 'Default' => 'h5', 'Class' => 'al_border' ),
 
            'hd1e' => array ( 'Type' => 'end'),
   
            /* Options related to the backend */

            'hd2s' => array ( 'Type' => 'section', 'Value' => __('Advanced Options','readme-gen'), 'Section_Class' => 'al_subhead1'),
            'rg_Category'       => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Plugin Category', 'readme-gen'), 'Description' => __('Category of all posts/pages that by default are about your plugins', 'readme-gen'), 'Default' => '', 'Class' => 'al_border'),
            'rg_ParentPost'     => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Parent Page', 'readme-gen'), 'Description' => __('Parent Post/Page to all your plugin pages', 'readme-gen'), 'Default' => 'Plugins', 'Class' => 'alternate al_border' ),
            'rg_Filename'       => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('Readme Filename', 'readme-gen'), 'Description' => __('The name of the file to generate, change to avoid overwriting the original', 'readme-gen'), 'Default' => 'readme.txt', 'Class' => 'alternate al_border' ),
            'rg_CreatePOT'      => array ( 'Id' => 'readme-gen-opt', 'Type' => 'checkbox', 'Name' => __('Create POT', 'readme-gen'), 'Description' => __('Default setting for Generation of POT file', 'readme-gen'), 'Default' => '0' ),
            'rg_POTFile'        => array ( 'Id' => 'readme-gen-opt', 'Type' => 'text', 'Name' => __('POT Location', 'readme-gen'), 'Description' => __('Default Location of POT file, relative to the readme.txt location %SLUG% is replaced with plugin-name', 'readme-gen'), 'Default' => 'i18n/%SLUG%.pot' ),
            'hd2e' => array ( 'Type' => 'end'),

            'button' => array( 'Type' => 'buttons', 'Buttons' => array( __('Update Options', 'readme-gen' ) => array( 'Class' => 'button-primary', 'Action' => 'readme-gen-action'))));

         }
         return $this->option_list;
      }

      function get_options() {
         $option_list = $this->get_option_list();
         if (null === $this->opts) {
            $this->opts = get_option($this->option_name, array());
            // Ensure hidden items are not stored in the database
            foreach ( $option_list as $opt_name => $opt_details ) {
               if ($opt_details['Type'] == 'hidden') unset($this->opts[$opt_name]);
               if (!isset($this->opts[$opt_name]) &&
                   isset($opt_details['Default']))
               {
                  $this->opts[$opt_name] = $opt_details['Default'];
               }
            }
         }
         return $this->opts;
      }

      function save_options($opts) {
         if (!is_array($opts)) {
            return;
         }
         update_option($this->option_name, $opts);
         $this->opts = $opts;
      }

      function delete_options() {
         delete_option($this->option_name);
      }

      /*
       * Parse the arguments passed in.
       */
      function parse_args($arguments) {

         $optionList = $this->get_option_list();

         $args = array();
         parse_str(html_entity_decode($arguments), $args);

         $Opts = $this->get_options();
         unset($this->settings);
         /*
          * Check for each setting, local overides saved option, otherwise fallback to default.
          */
         foreach ($optionList as $key => $details) {
            if (isset($args[$key])) {
               $this->settings[$key] = trim(stripslashes($args[$key]),"\x22\x27");              // Local setting
            } else if (isset($Opts[$key])) {
               $this->settings[$key] = $Opts[$key];   // Global setting
            } else if (isset ($details['Default'])) {
               $this->settings[$key] = $details['Default'];      // Use default
            }
         }
      }

      /*
       * Normally Settings are populated from parsing user arguments, however some
       * external calls do not cause argument parsing. So this ensures we have the defaults.
       */
      function get_settings($post_id) {
         $option_list = $this->get_form_option_list();
         if (!isset($this->settings)) {
            $this->settings = get_post_meta($post_id, $this->option_name, True);
            if (!is_array($this->settings))
               $this->settings = array();
            foreach ($option_list as $key => $details) {
               if (!isset($this->settings[$key]) && isset($details['Default'])) {
                  $this->settings[$key] = $details['Default'];      // Use default
               }
            }
         }
         return $this->settings;
      }


      function save_settings($post_id, $opts) {
         if (!is_array($opts)) {
            return;
         }
         update_post_meta($post_id, $this->option_name, $opts);
         $this->opts = $opts;
      }
/*****************************************************************************************/
      /// 
/*****************************************************************************************/

/*****************************************************************************************/
      /// Display Content, Widgets and Pages
/*****************************************************************************************/

      // Top level function to display options
      function show_options_page() {
         global $screen_layout_columns;
?>
<div class="wrap">
      <?php screen_icon('options-general'); ?>
      <h2><?php echo __('Readme Generator Options', 'readme-gen') ?></h2>
   <div id="poststuff" class="metabox-holder">
      <?php do_meta_boxes($this->opts_page, 'normal',0); ?>
   </div>
         <div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
            <div id="post-body" class="has-sidebar" >
               <div id="post-body-content" class="has-sidebar-content">
                  <?php do_meta_boxes($this->opts_page, 'advanced',0); ?>
               </div>
            </div>
            <div id="side-info-column" class="inner-sidebar">
               <?php do_meta_boxes($this->opts_page, 'side',0); ?>
            </div>
            <br class="clear"/>
         </div>   
      </div>
   <script type="text/javascript">
      //<![CDATA[
      jQuery(document).ready( function($) {
         // close postboxes that should be closed
         $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
         // postboxes setup
         postboxes.add_postbox_toggles('<?php echo $this->opts_page; ?>');
      });
      //]]>
   </script>
<?php
      }

/*****************************************************************************************/

      // Main Options Box
      function show_options() {
         include('include/show-options.php');
      }

/*****************************************************************************************/

      // Plugin Info Box
      function show_info() {
         include('include/show-info.php');
      }

/*****************************************************************************************/


/*****************************************************************************************/

      // Page/Post Edit Screen Widget
      function insert_form($post) {
         include('include/insert-form.php');
      }

      function save_form($post) {
         include('include/save-form.php');
      }

/*****************************************************************************************/
      /// Generate the Readme.txt
/*****************************************************************************************/

      function generate_readme() {
         $opts = $_POST;
         $options = $this->get_options();
         $results = array('success' => false,
                          'content'   => __('No actions requested.', 'readme-gen'));
         
         if (!empty($opts['rg_GenerateReadMe'])) {
            include('include/generate-readme.php');
         }

         if (!empty($opts['rg_CreatePOT'])) {
            include('include/generate-pot.php');
         }

         if (!empty($opts['rg_GenerateHelp'])) {
            include('include/generate-help.php');
         }

         print json_encode($results);
         exit();         

      }

/////////////////////////////////////////////////////////////////////


   } // End Class

   $rgfwp = new readme_gen_for_wordpress();

} // End if exists


?>