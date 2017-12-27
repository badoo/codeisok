{*
 * filediffsidebyside
 *
 * File diff with side-by-side changes template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @author Mattias Ulbrich
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
 <script src="/js/sbs_review.js?v={$jsversion}"></script>

{if !$noCompareBlock}
    <div id="compare" class="SBSComparison"></div>
{/if}

 <script>
 var id = '';
 var cm_mode = 'clike';
 var compare = $('#compare');
 var review;
 var reviewCache = new Object();
 {literal}

 $(document).delegate('.SBSFileList a', 'click', function (e) {
    e.preventDefault();

    const link = $(this);
    const data = link.data();

    if (!data.fromhash) {
        return;
    }

    loadSBS(data.fromhash, data.fromfile, data.tohash, data.tofile, function () {
        $('.commitDiffSBS').removeClass('is-loading');
    });

    $('.file-list li').removeClass('is-active');
    link.parent().addClass('is-active is-visited');
 });

 function loadSBS(fromHash, fromFile, toHash, toFile, callback) {
    var review_file = $('#review_file');
    if (!review_file.length) {
        $('.page_body').prepend($('<input type="hidden" id="review_file" value="' + toFile + '">'));
    } else {
        review_file.val(toFile);
    }

    if (review != undefined) {
        review.pause();
    }

    var reviewKey = fromHash + fromFile + toHash + toFile;
    if (reviewCache[reviewKey] != undefined) {
        review = reviewCache[reviewKey];
    } else {
        review = new SideBySideReview();
        reviewCache[reviewKey] = review;
    }

    $('.commitDiffSBS').addClass('is-loading');

    $.ajax({
        type: 'GET', async: true, dataType: 'text',
        {/literal}
        url: '{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob_plain&h=' + fromHash + '&f=' + fromFile,
         {literal}
        success: function (response, textStatus, request) {
            cm_mode = request.getResponseHeader('Cm-mode');
            compare.mergely('lhs', response);
            var resp_length = 0;
            if (response) {
                resp_length = response.split("\n").length;
            }
            $('.page_body').prepend($('<input type="hidden" id="lhs_length" value="' + resp_length + '">'));
            compare.mergely('cm', 'lhs').setOption('mode', cm_mode);
            hideLoader(callback);
        }
    });

    $.ajax({
        type: 'GET', async: true, dataType: 'text',
         {/literal}
        url: '{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob_plain&h=' + toHash + '&f=' + toFile,
         {literal}
        success: function (response, textStatus, request) {
            cm_mode = request.getResponseHeader('Cm-mode');
            compare.mergely('rhs', response);
            var resp_length = 0;
            if (response) {
                resp_length = response.split("\n").length;
            }
            $('.page_body').prepend($('<input type="hidden" id="rhs_length" value="' + resp_length + '">'));
            compare.mergely('cm', 'rhs').setOption('mode', cm_mode);
            hideLoader(callback);
        }
    });

    var oneSideLoaded = false;
    function hideLoader(callback) {
        if (oneSideLoaded) {
            review.setCompareElement(compare);

            var backup_function = compare.mergely('options').updated;
            compare.mergely('options').updated = function () {
                compare.mergely('options').updated = backup_function;
                review.restore();
                compare.mergely('resize');
                callback();
            };

            compare.mergely('update');
        }

        oneSideLoaded = true;
    }
 }

 $(document).ready(function () {
    compare.mergely({
        cmsettings: { readOnly: 'nocursor', lineNumbers: true, viewportMargin: Infinity },
        editor_width: '40%',
        editor_height: ($(window).height()-130)+'px',

 {/literal}
 {if $ignorewhitespace}
 {literal}
    ignorews: true
 {/literal}
 {/if}
 {literal}
    });
 });

 $(function(){
    $('.SBSFileList a:first').click();
 });

 </script>
 {/literal}
{*
<table class="diffTable">
  {if $filediff->GetStatus() == 'D'}
    {assign var=delblob value=$filediff->GetFromBlob()}
    {foreach from=$delblob->GetData(true) item=blobline}
      <tr class="diff-deleted">
        <td class="diff-left">{$blobline|escape}</td>
	<td>&nbsp;</td>
      </tr>
    {/foreach}
  {elseif $filediff->GetStatus() == 'A'}
    {assign var=newblob value=$filediff->GetToBlob()}
    {foreach from=$newblob->GetData(true) item=blobline}
      <tr class="diff-added">
        <td class="diff-left">&nbsp;</td>
	<td>{$blobline|escape}</td>
      </tr>
    {/foreach}
  {else}
    {foreach from=$diffsplit item=lineinfo}
      {if $lineinfo[0]=='added'}
      <tr class="diff-added">
      {elseif $lineinfo[0]=='deleted'}
      <tr class="diff-deleted">
      {elseif $lineinfo[0]=='modified'}
      <tr class="diff-modified">
      {else}
      <tr>
      {/if}
        <td class="diff-left">{if $lineinfo[1]}{$lineinfo[1]|escape}{else}&nbsp;{/if}</td>
        <td>{if $lineinfo[2]}{$lineinfo[2]|escape}{else}&nbsp;{/if}</td>
      </tr>
    {/foreach}
  {/if}
</table>
*}
