$(document).ready(function() {

    // Enable widget bar
    $('#widgets').widgets_bar();
    
    // flash issue buttons
    /*start_color = $(".action-issue a").css('background-color');
    $(".action-issue a").animate(
        { 'background-color': '#ffa3a3'}, 500,
        function(){
            $(this).animate({ 'background-color': start_color}, 400, function(){ $(this).css({ 'background-color': null});});
    });*/
    
    $('.ui-form-more').click(function(){
        $(this).parents('dt').nextAll('dt').toggle('fast');
        $(this).toggleClass('pressed');
    })
    .parents('dt').nextAll('dt').hide();

});
