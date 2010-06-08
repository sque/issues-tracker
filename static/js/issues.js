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
    
    // More button on forms
    $('.ui-form-more').click(function(){
        $(this).parents('dt').nextAll('dt').toggle('fast');
        $(this).toggleClass('pressed');
    })
    .parents('dt').nextAll('dt').hide();


    // Hide attachments
    $('.ui-form-attachments-start').parents('dt').append(
        $('<a class="ui-form-more-attachments">Upload another file ...</a>').click(function(){
            var cur_name = $(this).prev().attr('name');
            var next = $(this).parents('div')
                .find('input[name=attachment' + (parseInt(cur_name.replace(/attachment/, ' ')) + 1) + ']');
            $(this).detach().appendTo(next.parent());
            next.show(80);
        })
    ).nextAll().find('input[type=file]').hide();
    

});
