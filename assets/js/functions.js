jQuery(document).ready(function () {
    //Functions for the setting page
    var toggleGroup = function (groupName) {
        var $groups = jQuery(groupName);

        jQuery.each($groups, function (key, group) {
            var $group = jQuery(group);
            var $firstElements = jQuery('tr:first input', $group);

            var toggleElements = function (element) {
                var $element = jQuery(element);

                if ($element.val() === 'true') {
                    jQuery('tr:not(:first)', $group).hide();
                } else {
                    jQuery('tr:not(:first)', $group).show();
                }
            };

            $firstElements.change(function () {
                toggleElements(this);
            });

            toggleElements(jQuery('tr:first input:checked', $group));
        });
    };

    toggleGroup('.uam_settings_group_post_type');

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