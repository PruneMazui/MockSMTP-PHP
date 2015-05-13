Waiting = {

    wrap : null,

    start : function(){
        this.wrap = $('<div style="position:fixed; top:0; left:0; bottom:0; right:0; cursor:wait; ">')
            .css({
                'display' : 'none',
                '-ms-filter' : '"alpha(opacity=1)"',
                'filter': 'alpha(opacity=1)',
                'opacity': '0.01',
                'background-color': '#000',
                'zoom' : '1',
                'z-index' : 2000000000,
            })
            .appendTo('body')
            ;

        this.wrap.show();
    },

    stop : function(){
        if(typeof(this.wrap.remove) == 'function'){
            this.wrap.remove();
        }
    }
};

$(function(){
    $(".datepicker").datepicker({
        dateFormat: "yy/mm/dd"
    });
    
    $('.show_detail').on('click', function(){
        Waiting.start();
        
        $('#modal-label').text($(this).closest('tr').find('.mail_subject').text());
        
        var mail_id = $(this).attr('data-id');
        
        $.ajax({
            url : 'detail',
            type : 'GET',
            dataType : 'html',
            aync : true,
            data : {
                'mail_id' : mail_id
            }
        }).done(function(data){
            $('#mail_detail').find('.modal-body').html(data);
            
            $('#mail_detail').modal('show');
        }).always(function(){
            Waiting.stop();
        })
        ;
        
        return false;
    });
    
    $('input[name="error_flg"]').on('change', function(){
        if($(this).filter(':checked').length > 0) {
            $('.error_disable').attr('disabled', 'disabled');
        }
        else {
            $('.error_disable').removeAttr('disabled');
        }
    });
    $('input[name="error_flg"]').trigger('change');
});