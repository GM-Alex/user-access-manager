jQuery(document).ready(function() {
    //Functions for the setting page

    if(jQuery('.uam_hide_page:checked').val() == 'true'){
        jQuery('#uam_page_settings').css('display', 'none');
        jQuery(this).toggleClass('active');
    }

    jQuery('.uam_hide_page').change(function() {
        jQuery('#uam_page_settings').toggle();
        jQuery(this).toggleClass('active');
    });

    if(jQuery('.uam_hide_post:checked').val() == 'true'){
        jQuery('#uam_post_settings').css('display', 'none');
        jQuery(this).toggleClass('active');
    }

    jQuery('.uam_hide_post').change(function() {
        jQuery('#uam_post_settings').toggle();
        jQuery(this).toggleClass('active');
    });

    if(jQuery('.uam_lock_file:checked').val() == 'false'){
        jQuery('#uam_file_settings').css('display', 'none');
        jQuery(this).toggleClass('active');
    }

    jQuery('.uam_lock_file').change(function() {
        jQuery('#uam_file_settings').toggle();
        jQuery(this).toggleClass('active');
    });
    
    jQuery('#uam_reset_confirm').on('change paste keyup', function () {
        var $button = jQuery('#uam_reset_submit');
        
        if (jQuery(this).val() === 'reset') {
            $button.removeAttr('disabled');
        } else if (typeof $button.attr('disabled') === 'undefined') {
            $button.attr('disabled', 'disabled');
        }
    })
});