jQuery(document).ready(function ($) {
    /**
     * Click triggered flyouts, modelled on the popovers WordPress uses for the
     * publish date. Only one is open at a time; escape and outside clicks close
     * it and return focus to the trigger it belongs to.
     */
    var flyoutIdCounter = 0;
    var $openToggle = null;

    var getPanel = function ($toggle) {
        return $toggle.next('.uam_flyout_panel');
    };

    var positionPanel = function ($toggle, $panel) {
        $panel.removeClass('uam_flyout_panel_above').css('left', 0);

        var rect = $panel[0].getBoundingClientRect();
        var overflowRight = rect.right - (window.innerWidth - 8);

        if (overflowRight > 0) {
            $panel.css('left', -overflowRight + 'px');
        }

        var left = $panel[0].getBoundingClientRect().left;

        if (left < 8) {
            $panel.css('left', (parseFloat($panel.css('left')) || 0) + (8 - left) + 'px');
        }

        // Flip above the trigger when there is no room below, the way the
        // publish date popover does.
        var panelRect = $panel[0].getBoundingClientRect();
        var toggleTop = $toggle[0].getBoundingClientRect().top;

        if (panelRect.bottom > window.innerHeight - 8 && toggleTop > panelRect.height + 8) {
            $panel.addClass('uam_flyout_panel_above');
        }
    };

    var closeFlyout = function (returnFocus) {
        if ($openToggle === null) {
            return;
        }

        getPanel($openToggle).hide();
        $openToggle.attr('aria-expanded', 'false');

        if (returnFocus === true) {
            $openToggle.trigger('focus');
        }

        $openToggle = null;
    };

    var openFlyout = function ($toggle) {
        var $panel = getPanel($toggle);

        if ($panel.length === 0) {
            return;
        }

        if (!$panel.attr('id')) {
            flyoutIdCounter++;
            $panel.attr('id', 'uam_flyout_panel_' + flyoutIdCounter);
        }

        $toggle.attr('aria-controls', $panel.attr('id'));
        $toggle.attr('aria-expanded', 'true');
        $panel.show();
        positionPanel($toggle, $panel);
        $openToggle = $toggle;
    };

    jQuery(document).on('click', '.uam_flyout_toggle', function (event) {
        var $toggle = jQuery(this);
        var wasOpen = $toggle.attr('aria-expanded') === 'true';

        event.preventDefault();
        event.stopPropagation();
        closeFlyout(false);

        if (wasOpen === false) {
            openFlyout($toggle);
        }
    });

    // Clearing both fields removes the time restriction on save, because an
    // empty value makes the server store no date at all.
    jQuery(document).on('click', '.uam_clear_dates', function () {
        var $panel = jQuery(this).closest('.uam_flyout_panel');
        var $toggle = $panel.prev('.uam_flyout_toggle');
        var emptyLabel = $toggle.data('empty-label');

        $panel.find('input').val('');

        if (emptyLabel) {
            $toggle.text(emptyLabel);
        }

        closeFlyout(true);
    });

    jQuery(document).on('click', function (event) {
        if ($openToggle !== null && jQuery(event.target).closest('.uam_flyout_panel').length === 0) {
            closeFlyout(false);
        }
    });

    jQuery(document).on('keydown', function (event) {
        if (event.key === 'Escape' && $openToggle !== null) {
            closeFlyout(true);
        }
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