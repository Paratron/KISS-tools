/**
 * jQuery ScrollEvents Plugin
 * ==========================
 * This plugin adds events which you can add to specific elements and then listen to them.
 * Example:
 *     $('#element').scrollEvents();    //Activates the scrollEvents on this element(s)
 *     $('#element')                    //After the activation, you can listen to the following events:
 *          .bind('scrollin', function(e){...})         //Scroll-In gets fired when the page is scrolled and the element(s) appear on the screen.
 *          .bind('scrollout', function(e){...})        //Scroll-Out gets fired when the page is scrolled and the element(s) disappear from the screen.
 *          .bind('scrollover', function(e){...})   //Triggered when elements are on screen and get scrolled.
 *
 * @author: Christian Engel <hello@wearekiss.com>
 */
(function($) {

     observed = [];
       var displayHeight = $(window).height();

    $(window).resize(function(e){
        displayHeight = $(window).height();
    });

    $(window).scroll(function(e){
        var scrollPos = window.pageYOffset,
            cnt = 0;

        $.each(observed, function(){
            var value = observed[cnt],
                $this = $(value[0]),
                beenOnScreen = value[1],
                dta = $this.offset();
            cnt++;

            dta.height = $this.height();

            console.log(dta, scrollPos);
            if(dta.top + dta.height < scrollPos || dta.top > scrollPos + displayHeight){
                if(beenOnScreen){
                    this[1] = false;
                    $this.trigger('scrollout');
                    console.log('<', this[0]);
                }
                return;
            }

            if(!beenOnScreen){
                this[1] = true;
                $this.trigger('scrollin');
                console.log('>', this[0]);
            }

            $this.trigger('scrollover');
        });
    });
    

    $.fn.scrollEvents = function(){
        return this.each(function(){
            observed.push([$(this), false]);
        });
    }
})(jQuery);