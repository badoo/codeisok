{foreach from=$diff_source item=filediff}
    {assign var="diff" value=$filediff->GetDiff('', true, true)}
    <div class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower} diffBlob{if $filediff->getDiffTooLarge()} suppressed{/if}" id="{$filediff->GetFromHash()}_{$filediff->GetToHash()}">
        <a class="anchor" name="{$filediff->getToFile()}"></a>
        <div class="diff_info">
            {if ($filediff->GetStatus() == 'D') || ($filediff->GetStatus() == 'M') || ($filediff->GetStatus() == 'R')}
                {assign var=localfromtype value=$filediff->GetFromFileType(1)}
                {* $localfromtype}:{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if *}
                {$localfromtype}: <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$filediff->GetFromHash()}&amp;hb={$commit->GetHash()}{if $filediff->GetFromFile()}&amp;f={$filediff->GetFromFile()}{/if}">{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if}</a>
                {if $filediff->GetStatus() == 'D'}
                    {t}(deleted){/t}
                {/if}
            {/if}

            {if $filediff->GetStatus() == 'M' || $filediff->GetStatus() == 'R'}
                &gt;
            {/if}

            {if ($filediff->GetStatus() == 'A') || ($filediff->GetStatus() == 'M') || ($filediff->GetStatus() == 'R')}
                {assign var=localtotype value=$filediff->GetToFileType(1)}
                {* $localtotype}:{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if *}
                {$localtotype}: <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$filediff->GetToHash()}&amp;hb={$commit->GetHash()}{if $filediff->GetToFile()}&amp;f={$filediff->GetToFile()}{/if}">{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if}</a>

                {if $filediff->GetStatus() == 'A'}
                    {t}(new){/t}
                {/if}
            {/if}
        </div>
        {include file='filediff.tpl' diff=$diff}
    </div>
{/foreach}
