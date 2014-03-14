(function($){

    jQuery(document).ready(function($) {

    	if($('#dev7_custom_css_content').length){
    		var codeMirror = CodeMirror.fromTextArea($('#dev7_custom_css_content')[0], {
                mode: 'css',
    			//lineNumbers: true,
				lineWrapping: true,
				matchBrackets: true,
				autoCloseBrackets: true,
				highlightSelectionMatches: true,
				//gutters: ["CodeMirror-lint-markers"],
    			lint: true
    		});
            setCodeMirrorHeight(codeMirror);
            $(window).on('resize', function(){
                setCodeMirrorHeight(codeMirror);
            });

            function setCodeMirrorHeight(codeMirror){
                var height = $('html').height() - $(codeMirror.getWrapperElement()).offset().top - 50;
                codeMirror.setSize( null, height );
            }
    	}

        if($('#dev7-custom-css-preview').length){
            $('#dev7-custom-css-preview').on('click', function(e){
                e.preventDefault();
                var link = $(this),
                    origText = link.text();

                link.text('Working...');
                $.post(dev7_custom_css.ajax_url, {
                    action: 'dev7_custom_css_preview',
                    nonce: dev7_custom_css.nonce,
                    css: codeMirror.getValue() }, function(data){
                    link.text(origText);
                    if(data == 'success'){
                        window.open(link.attr('href'), link.attr('target'));
                    }
                });
            });
        }

    });

})(window.jQuery);