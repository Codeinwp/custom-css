(function($){

    jQuery(document).ready(function($) {

    	var querystring = 'dev7customcss_preview=true';
        $('a').each(function(){
            var href = $(this).attr('href');
            if(href) {
                href += (href.match(/\?/) ? '&' : '?') + querystring;
                $(this).attr('href', href);
            }
        });

    });

})(window.jQuery);