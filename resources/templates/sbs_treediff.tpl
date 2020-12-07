<link rel="stylesheet" href="/css/treediff.css?v={$cssversion}" type="text/css" />

<script>
window.sbsTreeDiff = true;

var _file_list = [
    {foreach from=$diff_source item=filediff}
        {ldelim}
        path: '{$filediff->getToFile()}',
        status: '{$filediff->getStatus()|lower}',
        fileType: '{$filediff->getToFileExtension()}',
        data: {ldelim}
            fromhash: "{$filediff->GetFromHash()}",
            fromfile: "{$filediff->GetFromFile()}",
            tohash: "{$filediff->GetToHash()}",
            tofile: "{$filediff->GetToFile()}"
        {rdelim}
        {rdelim},
    {/foreach}
];
</script>

<div class="two-panes SBSTOC is-loading">
    <div class="js-left-pane left-pane">
        <ul class="file-list">
        </ul>
    </div>

    <div class="js-pane-dragger pane-dragger"></div>

    <div id="compare" class="right-pane SBSComparison SBSContent">
        {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit() noCompareBlock=true}
    </div>
</div>