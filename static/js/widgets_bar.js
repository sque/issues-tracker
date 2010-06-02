
jQuery.fn.widgets_bar = function(el) {
	return this.each(function() {
        widget_bar = $(this);
        waiting_effect = false;
        
        sidebar_move_to_optimal_position = function(el)
        {
            // Fixed to documents rigth
            optim_top = $(window).scrollTop() + 10;
            if (optim_top < 50)
                optim_top = 50;
                
            if (optim_top == el.top)
                return;

            if (waiting_effect)
                clearTimeout(waiting_effect);

            waiting_effect = setTimeout(function()
            {
                el.stop(true, false).animate({
                    top: optim_top
                }, 400);
                waiting_effect = false;
            }, 200);
        }
            
        // Initial position
        sidebar_move_to_optimal_position(widget_bar);
            
        // Reposition on scroll
        $(window).scroll(function (event) { 
            sidebar_move_to_optimal_position(widget_bar);
        });
            
        $(window).resize(function () { 
            sidebar_move_to_optimal_position(widget_bar);
        });
	});
};

