var ComponentsKnobDials = function () {

    return {
        //main function to initiate the module

        init: function () {
            //knob does not support ie8 so skip it
            if (!jQuery().knob || App.isIE8()) {
                return;
            }

            // general knob
            $(".knob").knob({
                'dynamicDraw': true,
                'thickness': 0.4,
                'tickColorizeValues': true,
                'skin': 'tron',
                'format' : function (value) {
                   return value + '%';
                },
                'draw': function() {
                  $(this.i).css('font-size', '30px');
                  $(this.i).css('font-weight', 'normal');
                  $(this.i).css('font-family', '"Open Sans",sans-serif');
				  $('#loading-knob').hide();
				  $('#disk-home').show();
                }
            });
        }

    };

}();

jQuery(document).ready(function() {
   ComponentsKnobDials.init();
});
