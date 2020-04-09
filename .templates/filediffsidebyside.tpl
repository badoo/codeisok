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

 $(document).delegate('.SBSTOC a', 'click', function (e) {
    e.preventDefault();

    const link = $(this);
    const data = link.data();

    if (!data.fromhash) {
        return;
    }

    loadSBS(data.fromhash, data.fromfile, data.tohash, data.tofile, function () {
        $('.SBSTOC').removeClass('is-loading');
    });

    // to account for both treediff and non-treediff modes
    $('.SBSTOC a, .SBSTOC li').removeClass('is-active');

    link.addClass('is-active is-visited');
    link.parent('li').addClass('is-active is-visited');
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

    $('.SBSTOC').addClass('is-loading');

    var modes = {'lhs':{"file":fromFile, "hash":fromHash}, 'rhs':{"file":toFile, "hash":toHash}};
    function request(mode) {
        $.ajax({
            type: 'GET', async: true, dataType: 'text',
            {/literal}
            url: '{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob_plain&h=' + modes[mode].hash + '&f=' + modes[mode].file,
                {literal}
            success: function (response, textStatus, request) {
                content_type = request.getResponseHeader('Content-Type');
                if (content_type.indexOf("text") === -1 && content_type.indexOf("xml") === -1) {
                    response = "Binary data...";
                }
                cm_mode = request.getResponseHeader('Cm-mode');
                compare.mergely(mode, response);
                var resp_length = 0;
                if (response) {
                    resp_length = response.split("\n").length;
                }
                $('.page_body').prepend($('<input type="hidden" id="' + mode + '_length" value="' + resp_length + '">'));
                compare.mergely('cm', mode).setOption('mode', cm_mode);
                hideLoader(callback);
            }
        });
    }
    for (mode in modes) {
        request(mode);
    }

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
    const bodyStyle = getComputedStyle(document.body);
    const editorHeight = `${document.body.offsetHeight - compare.offset().top - parseInt(bodyStyle.paddingBottom) - 20}px`;

    if (window.sbsTreeDiff) {
        $('.SBSTOC').height(editorHeight);
        $('.js-left-pane').height(editorHeight);
    }

    compare.mergely({
        cmsettings: { readOnly: 'nocursor', lineNumbers: true, viewportMargin: Infinity },
        editor_width: '40%',
        editor_height: window.sbsTreeDiff ? editorHeight : `${window.innerHeight - 150}px`,

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
    $('.SBSTOC a:first').click();
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
