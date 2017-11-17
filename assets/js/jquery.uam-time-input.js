(function($) {
    var delay = (function() {
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();

    var convertRawChunksToTime = function(chunks) {
        var caps = [999, 24, 60, 60];
        chunks = chunks.map(function(num) { return parseInt(num); });

        for (var i = 4; i >= 0; i--) {
            if (chunks[i] >= caps[i]) {
                if (i - 1 >= 0) {
                    var times = Math.floor(chunks[i] / caps[i]);
                    chunks[i-1] += times;
                    chunks[i] = chunks[i] - (times * caps[i]);
                } else {
                    chunks[i] = caps[i];
                }
            }
        }

        return chunks;
    };

    var formatChunks = function(chunks) {
        return chunks[0].toString().padStart(3, 0) + '-'
            + chunks.slice(1, 4).map(function (element) {
                return element.toString().padStart(2, 0);
            }).join(':');
    };

    var convertChunksToSeconds = function(chunks) {
        var multiples = [24, 60, 60, 1];

        return chunks.reduce(function (sum, value, index) {
            var multiplier = multiples.slice(index, multiples.length).reduce(function (product, value) {
                return product * value;
            });

            return sum + value * multiplier;
        }, 0);
    };

    $.uamTimeInput = function(targetInput) {
        var $input = $(targetInput);
        var $newInput = $input.clone().attr('name', '');
        $input.hide().before($newInput);

        var existingValue = $input.val();

        if (existingValue !== '') {
            $newInput.val(formatChunks(convertRawChunksToTime([0, 0, 0, existingValue * 1])));
        }

        $newInput.on('keyup', function (event) {
            var $this = $(this);
            var selection = window.getSelection().toString();
            var rawInput = $this.val();

            if (selection !== ''
                || rawInput === ''
                || $.inArray(event.keyCode, [38, 40, 37, 39]) !== -1
            ) {
                return;
            }

            var domElement = $this.get(0);
            var cursorStart = domElement.selectionStart;
            var cursorEnd = domElement.selectionEnd;
            var inputRegex = /(\d{3})(\d{2})(\d{2})(\d{2})/g;
            var inputValue = rawInput.replace(/^\D+|:|-/g, '')
                .substr(0, 9)
                .padStart(9, 0);
            var chunks = inputRegex.exec(inputValue);
            chunks = convertRawChunksToTime([chunks[1], chunks[2], chunks[3], chunks[4]]);
            $input.val(convertChunksToSeconds(chunks));

            delay(function () {
                $this.val(formatChunks(chunks));
                domElement.setSelectionRange(cursorStart, cursorEnd);
            }, 1000);
        });
    };

    $.fn.uamTimeInput = function () {
        this.each(function() {
            $.uamTimeInput(this);
        });

        return this;
    }
})(jQuery);