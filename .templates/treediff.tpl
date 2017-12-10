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

<div class="two-panes">
    {* This is rendered for non-JS support *}
    <div class="left-pane">

        {if $extensions}
        <div class="file_filter">
            <strong>Filter:</strong>
            {foreach from=$statuses item=st}
                <span class="status selected" data-status="{$st|lower}">{$st}</span>
            {/foreach}
            {foreach from=$extensions item=ext}
                <span class="extension selected" data-extension="{$ext}">{$ext}</span>
            {/foreach}
            {foreach from=$folders item=folder}
                <span class="folder selected" data-folder="{$folder|lower}">{$folder}</span>
            {/foreach}
            <span class="hint">(+Shift for single select)</span>
        </div>
        {/if}

        <ul class="file-list">
            {foreach from=$diff_source item=filediff}
                <li class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower}">
                <a href="#{$filediff->getToFile()}">{$filediff->getToFile()}</a>
                <a name="files_index_{$filediff->getToFile()}"></a>
                </li>
            {/foreach}
        </ul>
    </div>

    <div class="right-pane">
        {foreach from=$diff_source item=filediff}
        {assign var="diff" value=$filediff->GetDiff('', true, true)}

        <div class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower} diffBlob{if $filediff->getDiffTooLarge()} suppressed{/if}" id="{$filediff->GetFromHash()}_{$filediff->GetToHash()}">
            <a name="{$filediff->GetToFile()}" class="anchor"></a>

            <div class="diff_info">
                {assign var=localfromtype value=$filediff->GetFromFileType(1)}
                {$localfromtype}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$filediff->GetFromHash()}&amp;hb={$commit->GetHash()}{if $filediff->GetFromFile()}&amp;f={$filediff->GetFromFile()}{/if}">{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if}</a>

                {if $filediff->GetStatus() == 'D'}
                    {t}(deleted){/t}
                {/if}

                {if $filediff->GetStatus() == 'R'}
                -&gt;
                {/if}

                {if ($filediff->GetStatus() == 'R')}
                    {assign var=localtotype value=$filediff->GetToFileType(1)}
                    {$localtotype}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$filediff->GetToHash()}&amp;hb={$commit->GetHash()}{if $filediff->GetToFile()}&amp;f={$filediff->GetToFile()}{/if}">{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if}</a>
                    {if $filediff->GetStatus() == 'A'}
                        {t}(new){/t}
                    {/if}
                {/if}
            </div>

            {include file='filediff.tpl' diff=$diff}
        </div>

        {/foreach}
    </div>
</div>