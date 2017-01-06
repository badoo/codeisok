function show_suppressed_diff($node) {
	var isJquery = $node instanceof jQuery;

	if (!isJquery) {
		$node = $(this);
	}

	$node.parent().find('a').hide();
	$node.parent().find('img').show();

	var pre = $('<pre/>');

	pre.appendTo($node.parent().parent());

	pre.load(
		$node.attr('href'),
		function() {
            $brush = $node.data().brush;
            $(this).addClass("brush: " + $brush);
			$(this).parent().find('p').remove();
            SyntaxHighlighter.vars.discoveredBrushes = null;
			SyntaxHighlighterApply();
		}
	);

	return false;
}

$('.show_suppressed_diff').live('click', show_suppressed_diff);
