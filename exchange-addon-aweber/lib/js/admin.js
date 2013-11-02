jQuery(document).ready(function($){
    var doing_ajax = false;
    $(document).on('change', 'input#tgm-exchange-aweber-auth', function(e){
        var data = {
            action: 'tgm_exchange_aweber_update_lists',
            auth:   $('#tgm-exchange-aweber-auth').val(),
        };

        if ( ! doing_ajax ) {
            doing_ajax = true;
            $('.tgm-exchange-loading').css('display', 'inline');
            $.post(ajaxurl, data, function(res){
                $('.tgm-exchange-aweber-list-output').html(res);
                $('.tgm-exchange-loading').hide();
                doing_ajax = false;
            });
        }
    });
    $(document).on('click', '#tgm-exchange-aweber-auth-code', function(e){
        e.preventDefault();
        return window.open('https://auth.aweber.com/1.0/oauth/authorize_app/471e2ae2','','resizable=yes,location=no,width=750,height=600,top=0,left=0');
    });
});