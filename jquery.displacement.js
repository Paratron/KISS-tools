/**
 * jQuery Displacement Plugin
 * ==========================
 * This Plugin creates a 3D depth effect for you, by moving elements on a different speed when the page is scrolled.
 * @author: Christian Engel <hello@wearekiss.com>
 */
(function($) {
    var initialized = false,
        displayHeight,
        documentHeight,
        position_elements = [],
        background_elements = [];

    $(window).scroll(function(e) {
        var scrollPos = window.pageYOffset;

        $.each(position_elements, function(key, entry) {
            var $this = entry[0];
            var dta = entry[1];

            if (dta.originalPosition < scrollPos || dta.originalPosition > scrollPos + displayHeight) return;

            var multiplier = ((2 / displayHeight) * (dta.originalPosition - scrollPos)) - 1;

            $this.css({
                top: dta.originalTop + (dta.value * multiplier)
            });
        });

        $.each(background_elements, function(key, entry){
            var $this = entry[0];
            var dta = entry[1],
                positions = [],
                i = 0;
            $.each(dta.originalPosition, function(key, item){
                if(item === undefined) return;
                var put = [item[0], item[1]],
                    myValue = dta.value[i];
                if(myValue === undefined) myValue = dta.value[0];

                if(typeof item[1] === 'number'){
                    put[1] = item[1] - Math.floor((scrollPos / 100) * myValue);
                }
                positions.push(put);
                i+=1;
            });

            $this.css({
                'background-position': fold(positions)
            });
        });
    });

    function initialize(){
        initialized = true;
        displayHeight = $(window).height();
        documentHeight = $('html').height();
    }

    /**
     * Converts an array of background position data back into a string to be passed to the DOM.
     * @param positions
     */
    function fold(positions){
        var output = '';
        $.each(positions, function(key, item){
            output += ', ';
            output += item[0] + ' ';
            if(typeof item[1] === 'number') output += item[1]+'px'; else output += item[1];
        });
        return output.substr(2);
    }

    /**
     * Takes a string of css background positions and converts them into an array.
     * Percentual values should be kept and only pixel based y-positions should be changed.
     * @param positions
     */
    function unfold(positions){
        var elms = positions.split(', '),
            output = [];
        $.each(elms, function(key, item){
            if(item == '0% 0%') item = '0% 0px';
            var x = item.split(' '),
                result;
            if(x[1].substr(-1) == 'x'){
                result = [x[0], parseInt(x[1])];
            }
            output.push(result);
        });
        return output;
    }

    $.fn.position_displacement = function(value) {
        if (!initialized) initialize();

        return this.each(function() {
            var inValue = null,
                $this = $(this);
            if(value){
                inValue = value;
            } else {
                inValue = $this.attr('data-displacement');
            }
            if(!inValue){
                return;
            }
            var dta = {
                originalPosition: $this.offset().top,
                originalTop: parseInt($this.css('top')),
                value: inValue
            }

            position_elements.push([$this, dta]);
        });
    };

    $.fn.background_displacement = function(value){
        if (!initialized) initialize();
        if(typeof value === 'number'){
            value = [value];
        }

        return this.each(function(){
            $this = $(this);
            var bgPos = $this.css('background-position');

            var dta = {
                originalPosition: unfold(bgPos),
                value: value
            }
            background_elements.push([$this, dta]);
        });
    }
})(jQuery);