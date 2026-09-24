/**
 * This is the javascript file for the module.
 *
 * @package UltimaKit_
 */

(function ( $ ) {
  'use strict';

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  jQuery('#change_user').click(function(){
    
    var username = jQuery('.ultimakit_change_admin #change_username').val();
    var current_userid = jQuery('.ultimakit_change_admin #change_user').attr('data-id');
    jQuery('.ultimakit_change_admin .user_validation').css('color', '#d63638');
    if(username == null || username == ''){
      jQuery('.ultimakit_change_admin #change_username').addClass('invalid-name');
      return false;
    }
    /*Ajax Start*/
       jQuery.ajax({
        url:change_ajax_obj.ajax_url,
        type: 'post',
        data: {
          'action': 'ultimakit_change_admin_name',
          'username': username,
          'userid': current_userid,
          'nonce' : change_ajax_obj.ajax_nonce },
           success: function (response) {
            if(response.status != null){
              jQuery('.ultimakit_change_admin').removeClass('notice-error');
              jQuery('.ultimakit_change_admin').addClass('notice-success');
              jQuery('.ultimakit_change_admin p').text(response.status);
              jQuery('.ultimakit_change_admin input').remove();
              setTimeout(function() {
                  jQuery('.ultimakit_change_admin').remove();
              }, 3000);
            }
            if(response.usertext == 'admin'){
              jQuery('#change_username').addClass('invalid-name'); 
               jQuery('.user_validation').text(response.message);   
              return false;
            }else{
              jQuery('.user_validation').text(response.invalid); 

            }
          }
        });
       /*Ajax end here*/   
  });
  jQuery('.ultimakit_change_admin #change_username').keypress(function(){
    jQuery('.ultimakit_change_admin #change_username').removeClass('invalid-name');
  });

})( jQuery );

