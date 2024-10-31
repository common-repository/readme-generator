<?php
/*****************************************************************************************/

/*
 * Post/Page Edit Widget
 *
 */

/*****************************************************************************************/

   $option_list = $this->get_form_option_list();
   $options = $this->get_options();
   $opts = $this->get_settings($post->ID);

/*****************************************************************************************/

   $directory = WP_PLUGIN_DIR .'/'. $opts['rg_File'];
   $file      = $directory .'/readme.txt';

   if (is_file($file) && is_writeable($file)) {
      $option_list['rg_File']['Description'] = __('readme.txt exists, and is writeable.', 'readme-gen');
   } else if (is_file($file)) {
      $option_list['rg_File']['Description'] = __('readme.txt exists, but is NOT writeable.', 'readme-gen');
   } else if (is_dir($directory) && is_writeable($directory)) {
      $option_list['rg_File']['Description'] = __('Directory exists, and is writeable. Currently no readme.txt', 'readme-gen');
   } else if (is_dir($directory)) {
      $option_list['rg_File']['Description'] = __('Directory exists, and is NOT writeable. Currently no readme.txt', 'readme-gen');
   } else {
      $option_list['rg_File']['Description'] = __('Directory does not exists', 'readme-gen');
   }
  
   $option_list['rg_Post']['Value'] = $post->ID;

   $post_template = __("Short Plugin description, goes here.\n\n", 'readme-gen');
   foreach ($this->required_sections as $section) {
      $post_template .= '<'. $options['rg_Header']. '>' . $section . '</' . $options['rg_Header']. ">\n\n";
   }
   $option_list['rg_PostTemplate']['Value'] = $post_template;


   if (!isset($opts['rg_Enabled'])) {
      // Check to see if by default this post is a plugin post

      $parents = explode(',', strtolower($options['rg_ParentPost']));

      // Check the Parent post
      $parent = $post->post_parent;
      while ($parent && !isset($opts['rg_Enabled'])) {
         if (in_array($parent, $parents)) {
            $opts['rg_Enabled'] = '1';
         } else {
            $parent_post = get_post($post->post_parent);
            if (in_array(strtolower($parent_post->post_name), $parents))
               $opts['rg_Enabled'] = '1';
         }
         $parent = $parent->post_parent;
      }
   }
   if (!isset($opts['rg_Enabled'])) {
      $categories = explode(',', strtolower($options['rg_Category']));
      $cats = get_the_category ($post->ID);
      foreach ($cats as $cat) {
         if ((in_array($cat->cat_ID, $categories )) ||
             (in_array($cat->category_nicename, $categories ))) {
            $opts['rg_Enabled'] = '1';
            continue;
         }
      }
   }

   $opts = array_merge($options, $opts);

   // **********************************************************
   // Now display the options editing screen
   $this->form->display_form($option_list, $opts, False, True, False, 'readme-gen');

?>   