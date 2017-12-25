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

 <div id="compare" class="clearfix"></div>

 <script>
 var id='';
 var cm_mode = 'clike';
 var compare = $('#compare'+id);
 var review;
 var reviewCache = new Object();
 {literal}

 function loadSBS(fromHash, fromFile, toHash, toFile) {
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
            hideLoader();
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
            hideLoader();
        }
    });
 }
 var oneSideLoaded = false;
 function hideLoader(cnt) {
     if (oneSideLoaded) {
        review.setCompareElement(compare);
        compare.mergely('resize');

        var backup_function = compare.mergely('options').updated;
        compare.mergely('options').updated = function () {
            compare.mergely('options').updated = backup_function;
            review.restore();
        };

        compare.mergely('update');
     }

     oneSideLoaded = true;
 }

 $(document).ready(function () {
    compare.mergely({
        cmsettings: { readOnly: 'nocursor', lineNumbers: true, viewportMargin: Infinity },
        resized: function () {
            $('.commitDiffSBS').removeClass('is-loading');
        },
        editor_width: '48%',
        editor_height: 'auto',

        // Toggle performance improving features
        fadein: false,
        autoupdate: false,
        viewport: true,
        autoresize: false
 {/literal}
 {if $ignorewhitespace}
 {literal}
    ,ignorews: true
 {/literal}
 {/if}
 {literal}
    });
 });
 $('.SBSTOC > ul > li > a:first').click();
 $('.SBSTOC > ul > li > a:first').parent().addClass('activeItem');
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
