/**
 * Handle: readme_gen
 * Version: 0.0.1
 * Deps: jquery
 * Enqueue: true
 */

var readme_gen_class = function () {}

readme_gen_class.prototype = {
    options           : {},

    toggleAdvanced : function(event) {
        var collection = jQuery(event).find("#readme-gen-options");
        var defaults   = jQuery(event).find("input[name='rg_Enabled']:checked").length;
        if (defaults) {
           jQuery(collection).parent().parent().show();
        } else {
           jQuery(collection).parent().parent().hide();
        }
    },

   readme_done: function (response, status){
      jQuery('#readme-gen-results-content').empty();
      if( response["success"] == false ) {
         // Re-enable Generate button
         jQuery('#generate-button').attr("disabled", false);
         jQuery('#readme-gen-status').addClass('ajax-feedback');

         jQuery('#readme-gen-results').show();
         jQuery('#readme-gen-results-content').append(response["content"]);

      } else {
         // Hide Upload button, Show Delete button
         jQuery('#generate-button').attr("disabled", false);
         jQuery('#readme-gen-status').addClass('ajax-feedback');

         jQuery('#readme-gen-results').show();
         jQuery('#readme-gen-results-content').append(response["content"]);
      }
   },

    insert_template: function(f, options) {
        var link_options = jQuery("input[id^=readme-gen-opt], select[id^=readme-gen-opt]");
        var $this = this;
        $this['options'] = {};
        link_options.each(function () {
            if (this.type == 'checkbox') {
               $this['options'][this.name] = this.checked ? "1" : "0";
            } else if (this.type == "select-one") {
               $this['options'][this.name] = this[this.selectedIndex].value;
            } else {
               $this['options'][this.name] = this.value;
            }
        });

        send_to_editor($this['options']['rg_PostTemplate']);

        return false;
    },

    generate_readme : function(f, options) {
        var link_options = jQuery("input[id^=readme-gen-opt], select[id^=readme-gen-opt]");
        var $this = this;
        $this['options'] = {};
        link_options.each(function () {
            if (this.type == 'checkbox') {
               $this['options'][this.name] = this.checked ? "1" : "0";
            } else if (this.type == "select-one") {
               $this['options'][this.name] = this[this.selectedIndex].value;
            } else {
               $this['options'][this.name] = this.value;
            }
        });

        $this['options']['rg_Content'] = jQuery('textarea#content[name="content"]').val();

        if (options != undefined) {
           jQuery.extend($this['options'], options);
        }

        $this['options']['action'] = 'readme_gen_generate';

        jQuery('#generate-button').attr("disabled", true);
        jQuery('#readme-gen-results-content').empty();
        jQuery('#readme-gen-error').hide();
        jQuery('#readme-gen-results').show();
        jQuery('#readme-gen-status').removeClass('ajax-feedback');
        jQuery.post('admin-ajax.php', $this['options'] , $this.readme_done, 'json');

        return false;
    }
}

var readme_gen = new readme_gen_class();


jQuery(document).ready( function() {
    readme_gen.toggleAdvanced(jQuery('#readme-gen-id'));
});