<?php

   $opts = array_merge($this->get_options(), $opts);

   $dir         = WP_PLUGIN_DIR .'/'. $opts['rg_File'];
   $readme_file = $dir . '/'.$options['rg_Filename'];

   if ((is_file($readme_file) && !is_writeable($readme_file)) ||
       (!is_writeable($dir))) {
      $results = array('success' => false, 
                       'content'   => sprintf(__('Cannot write to file: "%s".', 'readme-gen'), $readme_file));
      print json_encode($results);
      exit();         
   }

   // Generate the header
   $content =  '';
   $content .= "=== " . $opts['rg_Name'] . " ===\n";
   $content .= "Contributors: " . $opts['rg_Contributors'] . "\n";
   $content .= "Donate link: " . $opts['rg_Donate'] . "\n";
   $content .= "Tags: " . $opts['rg_Tags'] . "\n";
   $content .= "Requires at least: " . $opts['rg_Requires'] . "\n";
   $content .= "Tested up to: " . $opts['rg_Tested'] . "\n";
   $content .= "Stable tag: " . $opts['rg_Stable'] . "\n";
   $content .= "License: GPLv2 or later\n";
   $content .= "License URI: http://www.gnu.org/licenses/gpl-2.0.html\n";
   $content .= "\n\n";

   $HD = $opts['rg_Header'];
   $SH = $opts['rg_SubHeader'];
   // Parse the Content of the post
   $text = stripslashes($opts['rg_Content']);

   if ($opts['rg_Subpages']) {
      // Append any child pages to the end of the content
      $page_id = $opts['rg_Post'];
      $pages   = get_pages( array( 'child_of' => $page_id, 'sort_column' => 'post_menu_order', 'sort_order' => 'asc' ) );
      foreach( $pages as $page ) {		
         $raw = $page->post_content;
         if ( $raw ) {
            $text .= stripslashes("\n\r<$HD>". $page->post_title. "</$HD>\n\r" . $raw);
         }
      }
   }

   // Check all required sections are here, and remove requested sections
   $required = array_map('strtolower', $this->required_sections);
   $remove   = array_map('trim', explode(',', strtolower($opts['rg_Remove'])) );

   $sections = preg_split('/(< *(?:'.$HD.'|'.$SH.').*>.*< *\/(?:'.$HD.'|'.$SH.') *>)/Ui', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
 
   // Short Description
   $text = $sections[0];

   for($i=1; $i< count($sections); $i+=2) {
      if (preg_match('/< *'.$HD.'.*>(.*)< *\/'.$HD.' *>/Ui', $sections[$i], $title)) {
         $title = trim($title[1]);
         if (($index = array_search(strtolower($title), $required)) !== FALSE) 
            unset($required[$index]);
         if (!is_array($remove) || !in_array(strtolower($title), $remove)) {
            $text .= "== $title ==\n";
            $text .= $sections[$i+1];
         }
      }
      if (preg_match('/< *'.$SH.'.*>(.*)< *\/'.$SH.' *>/Ui', $sections[$i], $title)) {
         $title = trim($title[1]);
         if (!is_array($remove) || !in_array(strtolower($title), $remove)) {
            $text .= "= $title =\n";
            $text .= $sections[$i+1];
         }
      }
   }
   

   // Convert to markdown

   // Code
   while (preg_match('/< *code.*>(.*)< *\/code *>/Ui', $text, $code)){
      $code = preg_replace ('/`/Ui', '&#96;', $code[1]);                   // Escape '`'
      $text = preg_replace ('/< *code.*>(.*)< *\/code *>/Uis', '`'.$code.'`', $text,1);
   }

   // Ordered Lists
   while (preg_match('/< *ol.*>(.*)< *\/ol *>/isU', $text, $list)){
      $list = preg_replace ('/([^\s]*)[\s]*< *li.*>/Ui', "$1\n<li>", $list[1]);   // Each item on a newline
      $list = preg_replace ('/^(.*)< *li.*>/i', '<li>', $list);                   // Strip whitespace
      $list = preg_replace ('/< *li.*>(.*)< *\/li *>/Uis', "1. $1", $list);
      $text = preg_replace ('/< *ol.*>(.*)< *\/ol *>/isU', "\n".$list, $text,1);
   }

   // Unordered Lists
   while (preg_match('/< *ul.*>(.*)< *\/ul *>/isU', $text, $list)){
      $list = preg_replace ('/([^\s]*)[\s]*< *li.*>/Ui', "$1\n<li>", $list[1]);   // Each item on a newline
      $list = preg_replace ('/^(.*)< *li.*>/i', '<li>', $list);                   // Strip whitespace
      $list = preg_replace ('/< *li.*>(.*)< *\/li *>/Uis', "* $1", $list);
      $text = preg_replace ('/< *ul.*>(.*)< *\/ul *>/isU', "\n".$list, $text,1);
   }

   // Remaining assume are ordered lists
   $text =preg_replace('/< *li.*>(.*)< *\/li *>/Ui', "* $1", $text);

   $text =preg_replace('/< *em.*>(.*)< *\/em *>/Ui', "*$1*", $text);
   $text =preg_replace('/< *strong.*>(.*)< *\/strong *>/Ui', "**$1**", $text);
   $text =preg_replace('/< *h[1-6].*>(.*)< *\/h[1-6] *>/Ui', "**$1**", $text);

   $text =preg_replace('/\[.*\]/iU', '', $text);

   $text =preg_replace('/([^`])\[/Ui', '$1\[', $text);
   $text =preg_replace('/< *a.*href *= *[\'"]([^"\']*)[\'"][^>]*>(.*)< *\/a *>/Ui', '[$2]($1)', $text);

   $text =preg_replace('/<.*>/iU', '', $text);
   $text =preg_replace(array('/&#91;/i','/&lt;/i'), array('[','<'), $text);

   $content .= $text;

   // Write to the file
   $handle = fopen($readme_file, 'w');
   fwrite($handle, $content);
   fclose($handle);

   $comments = '<p>Readme.txt content written to '.$readme_file.'</p>'. "\n";
   foreach ($required as $i => $section) {
      $comments .= '<p>Missing section: '. $section . '</p>'. "\n";
   }

   $results = array('success' => true, 'content' => $comments);

?>