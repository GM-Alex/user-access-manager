jQuery(document).ready(function ($) {
    jQuery('.uam_group_selection').on('click', '.uam_group_date', function () {
        var $element = jQuery(this);
        var $next = $element.next('.uam_group_date_form');
        $next.toggle();
        $element.hide();
    });

    //Functions for the setting page
    var toggleGroup = function (group, elementIndex, hiddenFlag, elementsToHide) {
        hiddenFlag = hiddenFlag || 'true';
        var $group = jQuery(group);
        var $inputs = jQuery('tr:eq('+elementIndex+') input', $group);

        var toggleElement = function (element) {
            var $element = jQuery(element);
            var $subElements = jQuery('tr:gt('+elementIndex+')', $group);
            var currentState = ($element.val() === hiddenFlag);
            elementsToHide = elementsToHide || $subElements.length;

            for (var index = 0; index < $subElements.length; index++) {
                var $subElement = jQuery($subElements[index]);
                var data = $subElement.data('hidden') || {};
                data[elementIndex] = (index < elementsToHide) ? currentState : !currentState;
                $subElement.data('hidden', data);

                var showElement = true;

                jQuery.each(data, function(key, value) {
                    if (value === true) {
                        showElement = false;
                        return null;
                    }
                });

                $subElement.toggle(showElement);
            }
        };

        $inputs.on('change', function () {
            toggleElement(this);
        });

        toggleElement($inputs.filter(':checked'));
    };

    toggleGroup('.uam_settings_group_post_type', 0);
    toggleGroup('.uam_settings_group_post_type:not(.default)', 1);
    toggleGroup('.uam_settings_group_taxonomies:not(.default)', 0);
    toggleGroup('.uam_settings_group_file:not(.default)', 1);
    toggleGroup('#uam_settings_group_file', 0, 'false');
    toggleGroup('#uam_settings_group_file', 4, 'false', 1);

    // Functions for the setup page
    jQuery('#uam_reset_confirm').on('change paste keyup', function () {
        var $button = jQuery('#uam_reset_submit');

        if (jQuery(this).val() === 'reset') {
            $button.removeAttr('disabled');
        } else if (typeof $button.attr('disabled') === 'undefined') {
            $button.attr('disabled', 'disabled');
        }
    });

    jQuery('#uam_settings_group_section').on('change', function () {
        var $selected = jQuery(this).find(':selected');
        window.location.href = $selected.data('link');
    });

    /** global: ajaxurl */
    $('#uam_dynamic_groups').uamGroupSuggest(ajaxurl + '?action=uam-get-dynamic-group', { delay: 500, multiple: true });
    $('.uam_time_input').uamTimeInput();
});