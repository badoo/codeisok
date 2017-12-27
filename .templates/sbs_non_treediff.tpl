<div class="SBSTOC">
    <ul class="SBSFileList">
        <li class="listcount">{t count=$diff_source->Count() 1=$diff_source->Count() plural="%1 files changed"}%1 file changed{/t}</li>
        {foreach from=$diff_source item=filediff}
            <li>
                <a href="#{$filediff->GetFromHash()}_{$filediff->GetToHash()}"
                    data-fromHash = "{$filediff->GetFromHash()}"
                    data-fromFile = "{$filediff->GetFromFile()}"
                    data-toHash = "{$filediff->GetToHash()}"
                    data-toFile = "{$filediff->GetToFile()}"
                    class="SBSTOCItem">
                    {if $filediff->GetStatus() == 'A'}
                        {if $filediff->GetToFile()}{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if} {t}(new){/t}
                    {elseif $filediff->GetStatus() == 'D'}
                        {if $filediff->GetFromFile()}{$filediff->GetFromFile()}{else}{$filediff->GetToFile()}{/if} {t}(deleted){/t}
                    {elseif $filediff->GetStatus() == 'M'}
                        {if $filediff->GetFromFile()}
                            {assign var=fromfilename value=$filediff->GetFromFile()}
                        {else}
                            {assign var=fromfilename value=$filediff->GetFromHash()}
                        {/if}
                        {if $filediff->GetToFile()}
                            {assign var=tofilename value=$filediff->GetToFile()}
                        {else}
                            {assign var=tofilename value=$filediff->GetToHash()}
                        {/if}
                        {$fromfilename}{if $fromfilename != $tofilename} -&gt; {$tofilename}{/if}
                    {/if}
                </a>
                <span class="review-comments" name="files_index_{$filediff->GetFromFile()}"></span>
            </li>
        {/foreach}
    </ul>
</div>
<div class="SBSContent">
    {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
</div>