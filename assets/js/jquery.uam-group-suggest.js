(function($) {
    $.uamGroupSuggest = function(input, options) {
        var $input = $(input).attr("autocomplete", "off");
        var $list = $(options.listClass);
        var $results = $("<ul>");

        var timeout = false;		// hold timeout ID for suggestion results to appear
        var prevLength = 0;			// last recorded length of $input.val()
        var cache = [];				// cache MRU list
        var cacheSize = 0;			// size of cache in chars (bytes?)

        $results.addClass(options.resultsClass).appendTo('body');
        resetPosition();

        $(window).on('load', resetPosition) // just in case user is changing size of page while loading
            .on('resize', resetPosition);

        $input.blur(function() {
            setTimeout(function() {
                $results.hide()
            }, 200);
        });

        $input.keydown(processKey);

        function resetPosition() {
            // requires jquery.dimension plugin
            var offset = $input.offset();
            $results.css({
                top: (offset.top + input.offsetHeight) + 'px',
                left: offset.left + 'px'
            });
        }

        function processKey(e) {
            // handling up/down/escape requires results to be visible
            // handling enter/tab requires that AND a result to be selected
            if ((/27$|38$|40$/.test(e.keyCode) && $results.is(':visible')) ||
                (/^13$|^9$/.test(e.keyCode) && getCurrentResult())
            ) {
                if (e.preventDefault) {
                    e.preventDefault();
                }

                if (e.stopPropagation) {
                    e.stopPropagation();
                }

                e.cancelBubble = true;
                e.returnValue = false;

                switch(e.keyCode) {
                    case 38: // up
                        prevResult();
                        break;
                    case 40: // down
                        nextResult();
                        break;
                    case 9:  // tab
                    case 13: // return
                        selectCurrentResult();
                        break;
                    case 27: //	escape
                        $results.hide();
                        break;
                    default:
                        break;
                }
            } else if ($input.val().length !== prevLength) {
                if (timeout !== false) {
                    clearTimeout(parseInt(timeout));
                }

                timeout = setTimeout(suggest, options.delay);
                prevLength = $input.val().length;
            }
        }

        function suggest() {
            var q = $.trim($input.val()), items;

            if (q.length >= options.minchars) {
                var cached = checkCache(q);

                if (cached) {
                    displayItems(cached['items']);
                } else {
                    $.get(options.source, {q: q}, function(response) {
                        $results.hide();
                        items = parseResponse(response, q);
                        displayItems(items);
                        addToCache(q, items, response.length);
                    });
                }
            } else {
                $results.hide();
            }
        }

        function checkCache(q) {
            for (var i = 0; i < cache.length; i++) {
                if (cache[i]['q'] === q) {
                    cache.unshift(cache.splice(i, 1)[0]);
                    return cache[0];
                }
            }

            return false;
        }

        function addToCache(q, items, size) {
            var cached;
            while (cache.length && (cacheSize + size > options.maxCacheSize)) {
                cached = cache.pop();
                cacheSize -= cached['size'];
            }

            cache.push({
                q: q,
                size: size,
                items: items
            });

            cacheSize += size;
        }

        function displayItems(items) {
            if (!items) {
                return;
            }

            if (!items.length) {
                $results.hide();
                return;
            }

            resetPosition(); // when the form moves after the page has loaded

            var html = '';

            for (var i = 0; i < items.length; i++) {
                var data = $(items[i]).data();
                var elementSelector = 'input[value="' + data['dgType'] + '|' + data['dgId'] + '"]';

                if ($(elementSelector, $list).length <= 0) {
                    html += '<li>' + items[i] + '</li>';
                }
            }

            $results.html(html).show();

            $results
                .children('li')
                .mouseover(function() {
                    $results.children('li').removeClass(options.selectClass);
                    $(this).addClass(options.selectClass);
                })
                .click(function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectCurrentResult();
                });
        }

        function parseResponse(response, q) {
            var items = [];
            var results = JSON.parse(response);

            // parse returned data for non-empty items
            for (var i = 0; i < results.length; i++) {
                var result = results[i];

                if (result) {
                    var element = '<span data-dg-id="' + result.id + '" data-dg-type="' + result.type + '" >';
                    element += result.name.replace(
                        new RegExp(q, 'ig'),
                        function(q) {
                            return '<span class="' + options.matchClass + '" >' + q + '</span>';
                        }
                    );
                    element += '</span>';

                    items[items.length] = element;
                }
            }

            return items;
        }

        function getCurrentResult() {
            if (!$results.is(':visible')) {
                return false;
            }

            var $currentResult = $results.children('li.' + options.selectClass);
            return (!$currentResult.length) ? false :  $currentResult;
        }

        function getDatetimeInput(formName, id, type) {
            var $label = $('<label>').attr({
                "for": formName + '-' + 'id' + '-' + type
            }).html(type);

            var $input = $('<input>').attr({
                "type": 'datetime-local',
                "id": formName + '-' + 'id' + '-' + type,
                "name": formName + '[' + id + '][' + type + ']'
            });

            return $('<div>').append($label).append($input);
        }

        function selectCurrentResult() {
            var $currentResult = getCurrentResult();

            if ($currentResult) {
                $currentResult = $('span:first-child', $currentResult);

                var formName = 'uam_dynamic_user_groups';
                var dgType = $currentResult.data('dg-type');
                var dgId = $currentResult.data('dg-id');
                var id = dgType + '|' + dgId;
                var elementId = 'uam_user_groups-' + id;

                var $elementInput = $('<input>').attr({
                    "id": elementId,
                    "type": 'checkbox',
                    "value": id,
                    "name": formName + '[' + id + '][id]',
                    "checked": "checked"
                });

                var $elementLabel = $('<label>').attr({
                    "for": elementId,
                    "class": 'selectit'
                }).html($currentResult.html().replace(/(<([^>]+)>)/ig, ''));

                var $dateButton = $('<span>').attr({
                    "class": 'uam_group_date'
                }).html('Setup time based group assignment');

                var $dateContainer = $('<div>').attr({
                    "class": 'uam_group_date_form'
                });

                $dateContainer.append(getDatetimeInput(formName, id, 'fromDate'))
                    .append(getDatetimeInput(formName, id, 'toDate'));

                var $element = $('<li>')
                    .append($elementInput)
                    .append('\n')
                    .append($elementLabel)
                    .append($dateButton)
                    .append($dateContainer);

                $list.append($element);

                $results.hide();
                $input.trigger('change');
                $input.val('');

                if (options.onSelect) {
                    options.onSelect.apply($input[0]);
                }
            }
        }

        function nextResult() {
            var $currentResult = getCurrentResult();

            if ($currentResult) {
                $currentResult.removeClass(options.selectClass)
                    .next()
                    .addClass(options.selectClass);
            } else {
                $results.children('li:first-child').addClass(options.selectClass);
            }

        }

        function prevResult() {
            var $currentResult = getCurrentResult();

            if ($currentResult) {
                $currentResult
                    .removeClass(options.selectClass)
                    .prev()
                    .addClass(options.selectClass);
            } else {
                $results.children('li:last-child').addClass(options.selectClass);
            }
        }
    };

    $.fn.uamGroupSuggest = function(source, options) {
        if (!source) {
            return;
        }

        options = options || {};
        options.source = source;
        options.delay = options.delay || 100;
        options.listClass = options.listClass || '.uam_group_selection';
        options.resultsClass = options.resultsClass || 'ac_results';
        options.selectClass = options.selectClass || 'ac_over';
        options.matchClass = options.matchClass || 'ac_match';
        options.minchars = options.minchars || 2;
        options.onSelect = options.onSelect || false;
        options.maxCacheSize = options.maxCacheSize || 65536;

        this.each(function() {
            $.uamGroupSuggest(this, options);
        });

        return this;
    };
})(jQuery);