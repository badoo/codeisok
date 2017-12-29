{if $extensions}
    <div class="file_filter">
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