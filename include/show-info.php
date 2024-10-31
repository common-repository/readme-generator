<?php
   $info = get_plugin_data($this->filename);
?>
<div class="amazon-link-info">
<div class="amazon-link-info-heading"><?php echo $info['Name']?></div>

<p><?php echo $info['Description']?></p>
<dl>
<dt>Author:</dt>
<dd><?php echo $info['Author']?></dd>
</dl>
<dl>
<dt>Documentation:</dt>
<dd><a href="http://wordpress.org/extend/plugins/<?php echo $info['TextDomain']?>/">Wordpress Plugin Page</a></dd>
</dl>
<dl>
<dt>Homepage:</dt>
<dd><?php echo $info['Title']?></dd>
</dl>
<dl>
<dt>Version:</dt>
<dd><?php echo $info['Version']?></dd>
</dl>
</div>

<div class="readme-gen-info">
<div class="readme-gen-info-heading">Readme Generator Plugin</div>

<p>A simple plugin to convert an existing Wordpress post into a valid Plugin readme.txt</p>
<dl>
<dt>Author:</dt>
<dd><a href="http://profiles.wordpress.org/users/paulstuttard/">Paul Stuttard</a></dd>
<dl>
<dt>Documentation:</dt>
<dd><a href="http://wordpress.org/extend/plugins/readme-generator/">Wordpress Plugin Page</a></dd>
<dl>
<dt>Homepage:</dt>
<dd><a href="http://www.houseindorset.co.uk/plugins/readme-gen/">My Plugin Page</a></dd>
<dl>
<dt>Version:</dt>
<dd><?php echo $this->plugin_version;?></dd>

</div>