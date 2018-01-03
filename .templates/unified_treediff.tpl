<link rel="stylesheet" href="/css/treediff.css?v={$cssversion}" type="text/css" />

<script>
var _file_list = [
    {foreach from=$diff_source item=filediff}
        {ldelim}
        path: '{$filediff->getToFile()}',
        status: '{$filediff->getStatus()|lower}',
        fileType: '{$filediff->getToFileExtension()}'
        {rdelim},
    {/foreach}
];
</script>

<div class="two-panes is-loading">
    {* This is rendered for non-JS support *}
    <div class="js-left-pane left-pane">

        {include file='extensions_filter.tpl' stasuses=$statuses extensions=$extensions folders=$folders}

        <ul class="file-list">
            {foreach from=$diff_source item=filediff}
                <li class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower}">
                <a href="#{$filediff->getToFile()}">{$filediff->getToFile()}</a>
                <a name="files_index_{$filediff->getToFile()}"></a>
                </li>
            {/foreach}
        </ul>
    </div>

    <div class="js-pane-dragger pane-dragger"></div>

    <div class="right-pane">
        {include file='unified_diff_contents.tpl' diff_source=$diff_source}
    </div>
</div>