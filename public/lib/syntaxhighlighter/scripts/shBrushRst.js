/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/SyntaxHighlighter
 */
;(function()
{
	// CommonJS
	typeof(require) != 'undefined' ? SyntaxHighlighter = require('shCore').SyntaxHighlighter : null;

	function Brush()
	{
		this.regexList = [
            { regex: SyntaxHighlighter.regexLib.singleLineCComments,            css: 'comments' },
			{ regex: /\.\.\s[A-Za-z-]+::/gm,                               	    css: 'keyword' },
			{ regex: /:[\w\s\-]*:/gm,                     		                css: 'color2' },
			{ regex: /`.*`/gm,                           		                css: 'variable' },
			{ regex: /".*"/gm,                           		                css: 'variable' },
			{ regex: /\s+[A-Z_]*/gm,                           		            css: 'color6' },
			{ regex: /~~.*~~/gm,                           		                css: 'color1 bold' },
			{ regex: /\s\|\s/gm,                           		                css: 'keyword bold' },
			{ regex: /\*\*.*\*\*/gm,                           		            css: 'color8 bold' }
			];

		this.forHtmlScript({
			left	: /(&lt;|<)%[@!=]?/g, 
			right	: /%(&gt;|>)/g 
		});
	};

	Brush.prototype	= new SyntaxHighlighter.Highlighter();
	Brush.aliases	= ['rst'];

	SyntaxHighlighter.brushes.Rst = Brush;

	// CommonJS
	typeof(exports) != 'undefined' ? exports.Brush = Brush : null;
})();
