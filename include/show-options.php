<?php
/*****************************************************************************************/

/*
 * Admin Panel Processing
 *
 */
   $opts        = $this->get_options();
   $option_list = $this->get_option_list();

/*****************************************************************************************/

   $Action = (isset($_POST[ 'readme-gen-action' ]) && check_admin_referer( 'update-readme-gen-options')) ?
                      $_POST[ 'readme-gen-action' ] : 'No Action';

   // See if the user has posted us some information
   // If they did, the admin Nonce should be set.
   $NotifyUpdate = False;
   if(  $Action == __('Update Options', 'readme-gen') ) {

      // Update settings

      foreach ($option_list as $opt_name => $opt_details) {
         if (isset($opt_details['Name'])) {
            if (!isset($_POST[$opt_name])) $_POST[$opt_name] = NULL;
            // Read their posted value
            $opts[$opt_name] = stripslashes($_POST[$opt_name]);
            }
      }
      $notify_update  = True;
      $update_message = __('Options Updated.', 'readme-gen' );
    } 

/*****************************************************************************************/

   /*
    * If first run need to create a default settings
    */
   foreach ($option_list as $opt_name => $opt_details) {
      if(!isset($opts[$opt_name]) && isset($opt_details['Default']) && isset($opt_details['Name'])) {
         $opts[$opt_name] = $opt_details['Default'];
         $notify_update  = True;
         $update_message = __('Default Options Created.', 'readme-gen' );
      }
   }


/*****************************************************************************************/

   if (!empty($notify_update) && current_user_can('manage_options')) {
      // **********************************************************
      // Put an options updated message on the screen
      $this->save_options($opts);
      echo '<div class="updated"><p><strong>'. $update_message. '</strong></p></div>';
   }

/*****************************************************************************************/

   // **********************************************************
   // Now display the options editing screen

   $this->form->display_form($option_list, $opts);

?>