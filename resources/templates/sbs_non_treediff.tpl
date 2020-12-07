<div class="SBSTOC">
    <table class="diff-file-list">
        {foreach from=$diff_source item=filediff}
        <tr class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower}">
            <td>
                <span>{$filediff->getStatus()}</span>
            </td>
            <td class="file-name">
                <a class="SBSTOCItem" href="#{$filediff->GetFromHash()}_{$filediff->GetToHash()}" data-fromFile="{$filediff->GetFromFile()}" data-toFile="{$filediff->GetToFile()}" data-fromHash="{$filediff->GetFromHash()}" data-toHash="{$filediff->GetToHash()}">{$filediff->getToFile()}</a>
            </td>
            <td class="review-comments" data-fromFile="{$filediff->GetFromFile()}" data-toFile="{$filediff->GetToFile()}"></td>
        </tr>
        {/foreach}
    </table>
</div>

<div class="SBSContent">
    {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
</div>