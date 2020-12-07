/*
 * GitPHP Javascript language selector
 * 
 * Changes the language as soon as it's selected,
 * rather than requiring a submit
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

function initLangSelector() {
	$("#selLang").change(function() {
		$("#frmLangSelect").submit();
	});
	$("#btnLangSet").remove();
};

$(document).ready(function() {
	initLangSelector();
});
