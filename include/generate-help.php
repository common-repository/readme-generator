<?php

   $opts = array_merge($this->get_options(), $opts);

   $dir = WP_PLUGIN_DIR . '/' . $opts['rg_File'] . '/' . $opts['rg_help_filename'];

   if (!is_writeable($dir)) {
      $results = array('success' => false, 
                       'content'   => sprintf(__('Cannot write to directory: "%s".', 'readme-gen'), $dir));
      print json_encode($results);
      exit();         
   }

   $HD = $opts['rg_Header'];
   $SH = $opts['rg_SubHeader'];
   // Parse the Content of the post
   $text = wpautop(stripslashes($opts['rg_Content']) );

   if ($opts['rg_Subpages']) {
      // Append any child pages to the end of the content
      $page_id = $opts['rg_Post'];
      $pages   = get_pages( array( 'child_of' => $page_id, 'sort_column' => 'post_menu_order', 'sort_order' => 'asc' ) );
      foreach( $pages as $page ) {		
         $raw = wpautop($page->post_content );
         if ( $raw ) {
            $text .= stripslashes("\n\r<$HD>". $page->post_title. "</$HD>\n\r" . $raw);
         }
      }
   }

   // Only process requested Sections
   if (strpos($opts['rg_Help'], '=')) {
      $help = array();
      parse_str(strtolower($opts['rg_Help']),$help_args);
      $pages = array_keys($help_args);

      foreach ($help_args as $id => $args) {
         $help_sections = explode(',', $args);
         foreach ($help_sections as $title) {
            $help[$title] = array( 'page' => trim($id));
         }
      }


   } else {
      $help = array_map('trim', explode(',', $opts['rg_Help']));
      $pages = 'settings';
   }

   $sections = preg_split('/(< *(?:'.$HD.'|'.$SH.').*>.*< *\/(?:'.$HD.'|'.$SH.') *>)/Ui', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
 
   /* 
    * For each contextual help section we need to allocate it to a Admin Page
    * Default 'settings'.
    * Each section has a title (no default)
    * Each section has an ID
    * ID = plugin-page-title
    */

   /* Generate plugin id (first directory in path with all non letters replaced with -): */
   $plugin_dir = strpos($opts['rg_File'], '/') ? (substr($opts['rg_File'], 1, strpos($opts['rg_File'], '/')-1)) : $opts['rg_File'];
   $plugin = preg_replace('/[^\p{L}]+/', '-', $plugin_dir );

   $help_sections = array();

   for($i=1; $i< count($sections); $i+=2) {

      if ((preg_match('/< *'.$HD.'.*>(.*)< *\/'.$HD.' *>/Ui', $sections[$i], $title)) ||
          (preg_match('/< *'.$SH.'.*>(.*)< *\/'.$SH.' *>/Ui', $sections[$i], $title)) ) {

         $title = trim($title[1]);
         $title_l = strtolower($title);
         if (is_array($help) && array_key_exists($title_l, $help)) {

            $page = strtolower(isset($help[$title_l]['page']) ? $help[$title_l]['page'] : 'settings');
            $id = strtolower(preg_replace('/[^\p{L}]+/', '-', $plugin.'-'.$page.'-'.$title_l ));
            $help_sections[strtolower($title)] = array( 'id' => $id, 'page' => $page, 'title' => $title, 'content' => $sections[$i+1]);
         }
      }
   }

   foreach ($pages as $page) {
      $sections = array();
      foreach ($help as $title => $data) {
         if ($help_sections[$title]['page'] == $page) {
            $sections[$title] = $help_sections[$title];
         }
      }
      if (!empty($sections))
      {

         // Write to the file
         $file = $dir . '/' . $page . '.php';
         $content = '<?php return '.  var_export($sections, true) . ';?>';
         $handle = fopen($file, 'w');
         fwrite($handle, $content);
         fclose($handle);
      }
   }

   $results['content'] .= '<p>'. count($pages). ' help file(s) for '.$plugin.' written to '.$dir.'</p>'. "\n";

?>