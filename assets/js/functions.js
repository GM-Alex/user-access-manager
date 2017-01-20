jQuery(document).ready(function() {
    //Functions for the setting page
    var toggleGroup = function (groupName) {
        var $group = jQuery(groupName);
        var $firstElement = jQuery('tr:first input', $group);
        var toggleElements = function (element) {
            var $element = jQuery(element);

            if ($element.val() === 'true') {
                jQuery('tr:not(:first)', $group).hide();
            } else {
                jQuery('tr:not(:first)', $group).show();
            }
        };

        $firstElement.change(function() {
            toggleElements(this);
        });
        toggleElements($firstElement);
    };

    toggleGroup('#uam_settings_group_post');
    toggleGroup('#uam_settings_group_page');
    toggleGroup('#uam_settings_group_file');

    // Functions for the setup page
    jQuery('#uam_reset_confirm').on('change paste keyup', function () {
        var $button = jQuery('#uam_reset_submit');
        
        if (jQuery(this).val() === 'reset') {
            $button.removeAttr('disabled');
        } else if (typeof $button.attr('disabled') === 'undefined') {
            $button.attr('disabled', 'disabled');
        }
    })
});