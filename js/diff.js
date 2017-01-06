var SmartFilter = {

    onInvertClick : function ($this, classname_prefix, css_class, hidden_prefix) {
        var $objects = null;
        var selected = false;
        if ($this.hasClass('selected')) {
            $objects = $this.siblings();
            selected = true;
        } else {
            $objects = $this.parent().children().filter('.selected').add($this);
        }

        $objects = $objects.filter('.' + css_class);
        if (selected && $objects.hasClass('selected')) {
            $objects = $objects.filter('.selected');
        }

        $objects.each(function() {
            SmartFilter.onFilterClick(null, $(this), classname_prefix, css_class, hidden_prefix);
        });
    },

    onFilterClick : function(event, $object, classname_prefix, css_class, hidden_prefix) {
        if (event && event.shiftKey) {
            SmartFilter.onInvertClick($object, classname_prefix, css_class, hidden_prefix);
            return;
        }
        $('.' + classname_prefix + '-' + $object.data(css_class)).toggleClass(hidden_prefix + '-hidden');
        $object.toggleClass('selected');
    }
};

$(function(){
    $('#diff-context')
        .keyup(function(e){
            if (e.keyCode == 13) {
                $.cookie('diff_context', $(this).val(), {expires: 365});
                e.preventDefault();
                location.reload();
            }
        })
        .change(function(e){
            $.cookie('diff_context', $(this).val(), {expires: 365});
            location.reload();
        });
    $('#diff-ignore-whitespace').change(function(){
        $.cookie('ignore_whitespace', $(this).is(':checked'), {expires: 365});
        location.reload();
    });
    $('#diff-ignore-format').change(function(){
        $.cookie('ignore_format', $(this).is(':checked'), {expires: 365});
        location.reload();
    });

    $('.file_filter .extension').click(function(event) {
        SmartFilter.onFilterClick(event, $(this), 'filetype', 'extension', 'ext');
    });

    $('.file_filter .status').click(function(event) {
        SmartFilter.onFilterClick(event, $(this), 'status', 'status', 'st');
    });

    $('.file_filter .folder').click(function(event) {
        SmartFilter.onFilterClick(event, $(this), 'folder', 'folder', 'fldr');
    });
});
