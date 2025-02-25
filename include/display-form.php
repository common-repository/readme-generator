<?php
/*****************************************************************************************/

/*
 * Admin Panel Supporting Functions
 *

optionList['0'] => array ('Setting' => array( 'Name', 'Type' => [text|       'Size', 'Description', 'Buttons' => array()
                                                        nonce|      'Value'
                                                        hidden|     'Value'
                                                        title|      'Class', 'Value'
                                                        checkbox|   'Description', 'Buttons' => array()
                                                        selection|  'Description', 'Options' => array('Value', 'Name'), 'Buttons' => array()
                                                        buttons|    'Buttons' => ('Value' => ('Action', 'Class')))
 
*/

if (!class_exists('readme_gen_form')) {
   class readme_gen_form {

      /* 
       * Must be called by the client's init function
       */
      function init() {
         $stylesheet = plugins_url("form.css", __FILE__);
         wp_register_style('readme-gen-form', $stylesheet);
         $script     = plugins_url("form.js", __FILE__);
         wp_register_script('readme-gen-form', $script);
      }

      function enqueue_styles() {
         wp_enqueue_style('readme-gen-form');
      }

      function enqueue_scripts() {
         wp_enqueue_script('readme-gen-form');
      }

      function display_form($optionList, $Opts, $Open = True, $Body = True, $Close = True, $Name = 'form0') {

         if ($Open) {
?>
<div class="wrap">
 <form name="<?php echo $Name; ?>" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<?php
         }

         if ($Body) {

            // Loop through the options table, display a row for each.
            foreach ($optionList as $optName => $optDetails) {
               if (!isset($Opts[$optName]) && isset($optDetails['Default']))
                   $Opts[$optName] = $optDetails['Default'];

               if ($optDetails['Type'] == 'checkbox') {

                  // Insert a Check Box Item
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $hint   = isset($optDetails['Hint']) ? $optDetails['Hint'] : '';
                  $id     = isset($optDetails['Id']) ? 'id="'.$optDetails['Id'].'"' : '';
                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_opt_container"' : 'class="al_opt_container"';
                  $script = isset($optDetails['Script']) ? ' onClick="'.$optDetails['Script'].'" ' : '';

?>
   <dl <?php echo $class ?>>
    <dt class="al_label"><label for="<?php echo $optName; ?>"><?php echo $optDetails['Name']; ?></label></dt>
    <dd class="al_opt_details">
      <input style="float:left" <?php echo $id. ' '. $script ?> name="<?php echo $optName; ?>" title="<?php echo stripslashes($hint); ?>" type="checkbox" value="<?php echo ($Opts[$optName] >=1)+1 ?>" <?php checked($Opts[$optName] >= 1) ?>/>&nbsp;
      <?php if (isset($optDetails['Buttons'])) displayButtons($optDetails['Buttons']); ?>
      <?php if (isset($optDetails['Description'])) echo '<div class="al_description">'.$optDetails['Description'].'</div>'; ?>
    </dd>
   </dl>
<?php
               } else if ($optDetails['Type'] == 'selection') {

                  // Insert a Dropdown Box Item
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $id = isset($optDetails['Id']) ? 'id="'.$optDetails['Id'].'"' : '';
                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_opt_container"' : 'class="al_opt_container"';
                  $hint   = isset($optDetails['Hint']) ? ' title = "'.$optDetails['Hint'].'"' : '';

?>
   <dl <?php echo $class ?>>
    <dt class="al_label"><label for="<?php echo $optName; ?>"><?php echo $optDetails['Name']; ?></label></dt>
    <dd class="al_opt_details">
     <div class="al_input">
      <select <?php echo $id. $hint; ?> style="width:200px;" name="<?php echo $optName; ?>" class='postform'>
        <?php
         foreach ($optDetails['Options'] as $Value => $Details) {
            if (is_array($Details)) {
               $Name = $Details['Name'];
            } else {
               $Name = $Details;
               $Value= $Details;
            }
            echo "<option value='$Value' ". selected( $Opts[$optName], $Value, False). " >" . $Name . "</option>";
         }
        ?>
      </select>
     </div>
     <?php if (isset($optDetails['Buttons'])) $this->displayButtons($optDetails['Buttons']); ?>
     <?php if (isset($optDetails['Description'])) echo '<div class="al_description">'.$optDetails['Description'].'</div>'; ?>
    </dd>
   </dl>

<?php
               } else if ($optDetails['Type'] == 'textbox') {

                  // Insert a Text Edit Box Item
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $id = isset($optDetails['Id']) ? ' id="'.$optDetails['Id'].'" ' : '';
                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_opt_container"' : 'class="al_opt_container"';
                  $rows = isset($optDetails['Rows']) ? ' rows="'.$optDetails['Rows'].'" ' : '';

?>
   <dl <?php echo $class ?>>
    <dt class="al_label"><label for="<?php echo $optName; ?>"><?php echo $optName; ?></label></dt>
    <dd class="al_opt_details">
     <div class="al_input">
      <textarea <?php echo $id . $rows ?> style="width:400px;" name="<?php echo $optName; ?>" class='postform'><?php echo $Opts[$optName]; ?></textarea>
     </div>
     <?php if (isset($optDetails['Buttons'])) $this->displayButtons($optDetails['Buttons']); ?>
     <?php if (isset($optDetails['Description'])) echo '<div class="al_description">'.$optDetails['Description'].'</div>'; ?>
    </dd>
   </dl>

<?php
               } else if ($optDetails['Type'] == 'radio') {

                  // Insert a Radio Selection
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_opt_container"' : 'class="al_opt_container"';

?>
   <dl <?php echo $class ?>>
    <dt class="al_label"><label for="<?php echo $optName; ?>"><?php echo $optDetails['Name']; ?></label>
    <dd class="al_opt_details">
       <div class="al_input">
       <ul>
        <?php
         foreach ($optDetails['Options'] as $Value => $Details) {
            if (is_array($Details)) {
               $Name = $Details['Name'];
               $id = isset($Details['Id']) ? 'id="'.$Details['Id'].'"' : '';
            } else {
               $Name = $Details;
               $Value= $Details;
               $id = '';
            }
            echo "<li><input ".$id." name='$optName' type='radio' value='$Value' ". checked( $Opts[$optName], $Value, False). " >" . $Name;
            if (isset($Details['Input'])) $this->displayInput($optionList[$Details['Input']], $Details['Input'], $Opts);
            echo "</li>\n";
         }
        ?>
       </ul>
      </div>
      <?php if (isset($optDetails['Buttons'])) $this->displayButtons($optDetails['Buttons']); ?>
      <?php if (isset($optDetails['Description'])) echo '<div class="al_description">'.$optDetails['Description'].'</div>'; ?>
    </dd>
   </dl>

<?php


               } else if ($optDetails['Type'] == 'buttons') {

                  // Insert a set of Buttons
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_opt_container"' : 'class="al_opt_container"';

?>
    <div <?php echo $class ?>>
       <?php $this->displayButtons($optDetails['Buttons']); ?><br />
      <?php if (isset($optDetails['Description'])) echo '<div style="font-size:80%;clear:both">'.$optDetails['Description'].'</div>'; ?>
    </div>

<?php
               } else if ($optDetails['Type'] == 'hidden') {

                  // Insert a hidden Item
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $Value = isset($optDetails['Value']) ? $optDetails['Value'] : $Opts[$optName];
                  $id = isset($optDetails['Id']) ? 'id="'.$optDetails['Id'].'"' : '';
?>
    <input <?php echo $id ?> name="<?php echo $optName; ?>" type="hidden" value="<?php echo $Value; ?>" />
<?php

               } else if ($optDetails['Type'] == 'text') {

                  // Insert a Text Item
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $size = isset($optDetails['Size']) ? $optDetails['Size'] : '20';
                  $hint = isset($optDetails['Hint']) ? $optDetails['Hint'] : '';
                  $id = isset($optDetails['Id']) ? 'id="'.$optDetails['Id'].'"' : '';
                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_opt_container"' : 'class="al_opt_container"';

?>
   <dl <?php echo $class ?>>
    <dt class="al_label"><span><label for="<?php  echo $optName; ?>"> <?php echo $optDetails['Name']; ?></label></span></dt>
    <dd class="al_opt_details">
     <div class="al_input">
      <input style="width:200px" <?php  echo $id ?> name="<?php echo $optName; ?>" title="<?php echo stripslashes($hint); ?>" type="text" value="<?php echo $Opts[$optName]; ?>" size="<?php echo $size ?>" />
     </div>
     <?php if (isset($optDetails['Buttons'])) $this->displayButtons($optDetails['Buttons']); ?>
     <?php if (isset($optDetails['Description'])) echo '<div class="al_description">'.$optDetails['Description'].'</div>'; ?>
    </dd>
   </dl>

<?php
               } else if ($optDetails['Type'] == 'nonce') {

                  // Insert a Nonce Item
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  if (isset($optDetails['Action'])) {
                     $action = $optDetails['Action'];
                     $name = $optDetails['Name'];
                  }  else {
                     $action = $optDetails['Name'];
                     $name = '_wpnonce';
                  }
                  $referer = isset($optDetails['Referer']) ? $optDetails['Referer'] : true;
                  $echo = isset($optDetails['Echo']) ? $optDetails['Echo'] : true;

                  wp_nonce_field($action, $name, $referer, $echo);

               } else if ($optDetails['Type'] == 'title') {
                  $id = isset($optDetails['Id']) ? 'id="'.$optDetails['Id'].'"' : '';

                  // Insert a Title Item
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_opt_container"' : 'class="al_opt_container"';

                  if (isset($optDetails['Title_Class'])) {
                     $Title = '<div '.$id.' class="' . $optDetails['Title_Class'] . '">'. $optDetails['Value'] . '</div>';
                  } else {
                     $Title = '<h2 '.$id.'>'. $optDetails['Value'] . '</h2>';
                  }
?>
    <div <?php echo $class ?>>
      <?php if (isset($optDetails['Icon'])) screen_icon($optDetails['Icon']); ?>
      <?php echo $Title ?>
      <?php if (isset($optDetails['Description'])) echo '<div class="al_description">'.$optDetails['Description'].'</div>'; ?>
      <?php if (isset($optDetails['Buttons'])) $this->displayButtons($optDetails['Buttons']); ?>
    </div>
<?php
               } else if ($optDetails['Type'] == 'section') {
                  $id = isset($optDetails['Id']) ? 'id="'.$optDetails['Id'].'"' : '';

                  // Insert a Section
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                  $Title = '<h4 '.$id.'>'. $optDetails['Value'] . '</h4>';
                  $class  = isset($optDetails['Class']) ? 'class="'.$optDetails['Class'].' al_options"' : 'class="al_options"';

?>
    <div class="al_section">
     <div class="<?php echo $optDetails['Section_Class']; ?>"><?php echo $Title ?></div>
      <div <?php echo $class ?>>
<?php
               } else if ($optDetails['Type'] == 'end') {

                  // End a Section
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////
                  echo "</div></div>";
               } else {

                  // Unknown
                  //////////////////////////////////////////////////////////////////////////////////////////////////////////////
                 // echo "<pre>UNKNOWN:"; print_r ($optDetails); echo "</pre>";
               }
            }
         }

         if ($Close) {
?>

 </form>
</div>
<?php

         }
      }

      function displayButtons ($buttons) {

         foreach ($buttons as $Value => $details) {
            $type = isset($details['Type']) ? $details['Type'] : 'submit';
            $script = isset($details['Script']) ? ' onClick="'.$details['Script'].'" ' : '';
            $id = isset($details['Id']) ? 'id="'.$details['Id'].'"' : '';
            $value = isset($details['Value']) ? 'value="'.$details['Value'].'"' : '';
?>
   <input <?php echo $id;?> <?php echo $value;?> type="<?php echo $type;?>" <?php echo $script; ?> class="<?php echo $details['Class']; ?>" name="<?php echo $details['Action'] ?>" value="<?php echo $Value; ?>" />
<?php
         }
      }

      function displayInput ($optDetails, $optName, $Opts) {
         $size = isset($optDetails['Size']) ? $optDetails['Size'] : '20';
?>
     <input name="<?php echo $optName; ?>" type="text" value="<?php echo $Opts[$optName]; ?>" size="<?php echo $size ?>" />
     <?php if (isset($optDetails['Buttons'])) $this->displayButtons($optDetails['Buttons']); ?>
     <?php echo $optDetails['Description']; ?>
<?php
      }

   }
}
?>