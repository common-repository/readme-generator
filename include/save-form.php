<?php
/*****************************************************************************************/

/*
 * Post/Page Edit Widget Save
 *
 */
   // verify this came from the our screen and with proper authorization,
   // because save_post can be triggered at other times
   // Check permissions
   if ( (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || !isset($_POST['update-readme-gen-form']) ||
        (!wp_verify_nonce( $_POST['update-readme-gen-form'], $this->base_name ) ) ||
        (( 'page' == $_POST['post_type'] ) && !current_user_can( 'edit_page', $post ) ) ||
        (( 'post' == $_POST['post_type'] ) && !current_user_can( 'edit_post', $post ) ))
      return;
 
/*****************************************************************************************/

   if (!empty($_POST['rg_Enabled'])) {
      $option_list = $this->get_form_option_list();

      // Update settings

      foreach ($option_list as $opt_name => $opt_details) {
         if (isset($opt_details['Name'])) {
            if (!isset($_POST[$opt_name])) $_POST[$opt_name] = NULL;
            // Read their posted value
            $opts[$opt_name] = stripslashes($_POST[$opt_name]);
         }
      }
   } else {
     // Purge options?
     $opts = $this->get_settings($post);
     unset($opts['rg_Enabled']);
   }

   $this->save_settings($post, $opts);
   return $opts;
?>