/*
 * GitPHP javascript tree
 *
 * Load subtree data into tree page asynchronously
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */
function initTree() {
	$('span.expander').live('click', function() {
		var expandUrl = $(this).data('expand-url');

		if (!expandUrl) {
			return;
		}

		var treeHash = expandUrl.match(/h=([0-9a-fA-F]{40}|HEAD)/);

		if (!treeHash) {
			return;
		}

		treeHash = treeHash[1];

		var cell = $(this).parent();
		var row = cell.parent();

		var treeRows = $('.' + treeHash);

		if (treeRows && treeRows.size() > 0) {
			if (treeRows.is(':visible')) {
				treeRows.hide();
				treeRows.each(function() {
					if ($(this).data('parent') == treeHash) {
						$(this).data('expanded', false);
					}
				});
				row.find('.expander').removeClass('expanded');
			} else {
				treeRows.each(function() {
					if (($(this).data('parent') == treeHash) || ($(this).data('expanded') == true)) {
						$(this).show();
						$(this).data('expanded', true);
					}
				});
				row.find('.expander').addClass('expanded');
			}
		} else {
			var depth = row.data('depth');

			if (depth == null) {
				depth = 0;
			}

			depth++;

			row.addClass('row-loading');

			$.get(expandUrl, { o: 'js' }, function(data) {
				var subRows = jQuery(data)
					.filter(function () { return this.nodeName === 'TR' });

				subRows.addClass(treeHash);

				// Add the hash from parent back to the child rows so they can be toggled together
				var classList = row.attr('class').split(/\s+/);
 				$.each(classList, function(index, item) {
 					if (item.match(/[0-9a-fA-F]{40}/)) {
 						subRows.addClass(item);
 					}
 				});

				subRows.each(function() {
					$(this).data('parent', treeHash);
					$(this).data('expanded', true);
					$(this).data('depth', depth);

					var fileCell = $(this).find('.expander');
					fileCell.css('margin-left', depth * 15);
				});

				row.after(subRows);
				row.find('.expander').addClass('expanded');
				row.removeClass('row-loading');
				subRows.addClass('is-loaded');
			});
		}

		return false;
	});
};

$(document).ready(function() {
	initTree();
});
