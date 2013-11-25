/**
* Plugin name: Search Engine Keywords
* Admin javascript
*/
;(function($){
    $(function(){
        $('.secondary-submit').click(function(){
            var elem = $(this);
            $('#secondary-submit-target').val(elem.attr('data-target'));
            return true;
        });
        $('#se-name,#se-domain,#se-query').keypress(function(evt){
            code = evt.which || evt.keyCode;
            if ( 13 == code ) {
                $('#add-se').trigger('click');
                return false;
            }
        });
        $('#chars-search,#chars-repl').keypress(function(evt){
            code = evt.which || evt.keyCode;
            if ( 13 == code ) {
                $('#add-repl').trigger('click');
                return false;
            }
        });
        $('#new-pattern').keypress(function(evt){
            code = evt.which || evt.keyCode;
            if ( 13 == code ) {
                $('#add-pattern').trigger('click');
                return false;
            }
        });
    })
})(jQuery);
