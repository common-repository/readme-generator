<?php
ob_start();

   function bfglob($path, $pattern = '*', $flags = 0, $depth = 0) {
        $matches = array();
        $folders = array(rtrim(str_replace("\\", "/", $path), '/'));
        
        while($folder = array_shift($folders)) {
            $match = glob($folder.'/'.$pattern, $flags);
            $matches = is_array($match) ? array_merge($matches, $match) : $matches;
            if($depth != 0) {
                $moreFolders = glob($folder.'/'.'*', GLOB_ONLYDIR);
                $depth   = ($depth <= -1) ? -1: $depth + count($moreFolders) - 2;
                $folders = is_array($moreFolders) ? array_merge($folders, $moreFolders) : $folders;
            }
        }
        return $matches;
    }

   function my_sort($a, $b) {

      return strcmp($a['parse_string'], $b['parse_string']);
   }

   $dir  = WP_PLUGIN_DIR .'/'. $opts['rg_File'];
   $options['Slug'] = str_replace(' ','-',strtolower($opts['rg_Name']));
   $file = $dir . '/'. str_replace('%SLUG%', $options['Slug'], $opts['rg_POTFile']);
   $subdir = dirname($file);
   if ((is_file($file) && !is_writeable($file)) ||
       (!is_writeable($dir)) ||
       (!is_writeable($subdir))) {
      $results['success'] = false;
      $results['content'] .= '<p>'.sprintf(__('Cannot write to file: "%s".', 'readme-gen'), $file).'</p>';
      ob_end_clean();
      print json_encode($results);
      exit();         
   }
 

   $files = bfglob($dir, '*.php',0,-1);
   
   $strings = array();
   $log = '';
   $warnings = '';
   $all_strings =0;
   $lines =0;
   $all_domains = array();
 
   foreach ($files as $php_file) {
      $count=0;
      $domains = array();
      $contents = file($php_file);
      $filename = substr($php_file, strlen($dir)+1);
      $line = 0;
      $file_length = count($contents);
      while ($line < count($contents)) {
         $content = $contents[$line];
         $offset=0;
         $details = array();
         while (preg_match('/_[e_]\((.*)/', $content, $result, PREG_OFFSET_CAPTURE)) {
            $details['start_line']   = $line+1;
            $details['start_offset'] = $result[0][1] + $offset;
            $string = '';
            $content = $result[1][0];
            $end_found = false;
            while(!$end_found) {
               while (!preg_match('/(.*)\)/Us', $content, $result, PREG_OFFSET_CAPTURE)) {
                  $line++;
                  $content .= $contents[$line];
               }
               $string .= $result[1][0];
               if (preg_match('/\(/s', $result[1][0]) == 0) {
                  $end_found = true;
               }
               $offset = strlen($string)+1;
               $content = substr($content, $offset);
            }
            $offset = $offset + $details['start_offset'];

            $details['raw'] = $string;

            $tokens = explode(',', $string);
            if (count($tokens) > 1) {
               $domain = array_pop($tokens);
               $parse_string = implode(',',$tokens);
            } else {
               $parse_string = $tokens[0];
               $domain = 'None';
               $warnings .= 'WARNING: Missing text domain, '.$filename.':line '.$details['start_line'].', '.$parse_string.'.<br>';
            }

            $domain= trim($domain);
            if ($domain[0] == '"') {
               $domain= trim($domain,'"');
            } else {
               $domain= trim($domain,'\'');
            }

            $parse_string = trim($parse_string);
            if ($parse_string[0] == '"') {
               $parse_string = trim($parse_string,'"');
            } else {
               $parse_string = trim($parse_string,'\'');
            }
            $details['parse_string'] = stripslashes($parse_string);
            $details['domain'] = stripslashes($domain);
            $details['file']         = $filename;
            $details['end_line']     = $line+1;
            $details['end_offset']   = $offset;
            if ($domain != 'None') $domains[] = $domain;
            $strings[] = $details;
            $count++;
         }
         $line++;
         $content = $contents[$line];
         $offset = 0;
            
      }
      
      $domains = array_unique($domains);
      if ($count > 0) {
         if (count($domains) > 1) {
            $warnings .= 'WARNING: Multiple text domains? {'. implode(',',$domains). '} in file '.$filename.'.<br>'. "\n";
         }
      }
      $all_strings += $count;
      $lines += $file_length;
      $all_domains = array_unique(array_merge($domains, $all_domains));
   }
  
   $log .= "<p>i18n processing complete: ".count($files)." files, ". count($all_domains)." domains {". implode(',',$all_domains). "}, $lines lines, $all_strings strings. ";

   usort($strings, "my_sort");

   $content = '# This file is distributed under the same license as the ' . $opts['rg_Name']. ' package.
msgid ""
msgstr ""
"Project-Id-Version:  \n"
"Report-Msgid-Bugs-To: http://wordpress.org/tag/'. $options['Slug'].'\n"
"POT-Creation-Date: '. date('Y-m-d H:i:sP').'\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"PO-Revision-Date: 2010-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"

';

   $item = 0;
   $total_strings = count($strings);
   while ($item < $total_strings) {
      while (($item != ($total_strings-1)) &&
             (my_sort($strings[$item], $strings[$item+1]) == 0)) {
         $content .= '#: '. $strings[$item]['file']. ':'. $strings[$item]['start_line']. "\n";
         $item++;
      }
      $content .= '#: '. $strings[$item]['file']. ':'. $strings[$item]['start_line']. "\n";
      $content .= 'msgid "'. $strings[$item]['parse_string'].'"'. "\n";
      $content .= 'msgstr ""'. "\n\n";
      $item++;
   }


   // Write to the file
   $handle = fopen($file, 'w');
   fwrite($handle, $content);
   fclose($handle);

   $log .= "Written i18n strings to $file<br><br>" . $warnings."</p>\n";

   $results['content'] .= $log;

   ob_end_clean();

?>