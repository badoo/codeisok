{include file='header.tpl'}
{if $commit}
<input type="hidden" id="review_hash_head" value="{$commit->GetHash()}" />
<input type="hidden" id="review_hash_base" value="{$branchdiff->GetFromHash()}" />
{/if}

{* Nav *}
 <div class="page_nav">
   {if $commit}
   {assign var=tree value=$commit->GetTree()}
   {/if}
   {include file='nav.tpl' current='branchdiff' logcommit=$commit treecommit=$commit}
   <br />

    <div class="diff_modes">
        <strong>Change diff mode:</strong>

        {if $sidebyside}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}{if $review}&amp;review={$review}{/if}{if $base}&amp;base={$base}{/if}&amp;o=unified">{t}unified{/t}</a>
        {/if}

        {if $unified}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}{if $review}&amp;review={$review}{/if}{if $base}&amp;base={$base}{/if}&amp;o=sidebyside">{t}side by side{/t}</a>
        {/if}

        | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff_plain&amp;branch={$branch}">{t}plain{/t}</a>

        <strong>{t}TreeDiff: {/t}</strong>
        {if $treediff}
            <div class="switcher checked">
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}{if $review}&amp;review={$review}{/if}{if $base}&amp;base={$base}{/if}&amp;treediff=0"></a>
            </div>
        {else}
            <div class="switcher">
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}{if $review}&amp;review={$review}{/if}{if $base}&amp;base={$base}{/if}&amp;treediff=1"></a>
            </div>
        {/if}
    </div>
 </div>

 {include file='title.tpl'}

 <div class="page_body">

    <h2 class="only-comments-warning">Showing review comments only</h2>

    {if $branchdiff}
        {*
            UNIFIED
        *}
        {if $unified}
            <div class="diff_summary">
                {if $treediff}
                    {include file='unified_treediff.tpl' diff_source=$branchdiff}
                {else}
                    {include file='extensions_filter.tpl' stasuses=$statuses extensions=$extensions folders=$folders}

                    <table style="float: left; border: 0; padding: 0; margin: 0;">
                        {if $branchdiff->hasHidden()}
                        <tr>
                            <td>
                                <a class="warning_hidden_files" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}&amp;show_hidden=1">Some files were hidden. Click here to show them.</a>
                            </td>
                        </tr>
                        {/if}
                        {foreach from=$branchdiff item=filediff}
                            <tr class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower}">
                                <td>
                                    {$filediff->getStatus()}&nbsp;&nbsp;&nbsp;&nbsp;<a href="#{$filediff->getToFile()}">{$filediff->getToFile()}</a>
                                </td>
                                <td name="files_index_{$filediff->getToFile()}"></td>
                            </tr>
                        {/foreach}
                    </table>

                    <br style="clear: both;" />
                    <br style="clear: both;" />

                    {include file='unified_diff_contents.tpl' diff_source=$branchdiff}
                </div>
            {/if}
        {*
            SIDE BY SIDE
        *}
        {else}
            <script type="text/javascript" src="/lib/mergely/codemirror.min.js?v={$libVersion}"></script>
            <link type="text/css" rel="stylesheet" href="/lib/mergely/codemirror.css?v={$libVersion}" />
            <script type="text/javascript" src="/lib/mergely/mergely.js?v={$libVersion}"></script>
            <link type="text/css" rel="stylesheet" href="/lib/mergely/mergely.css?v={$libVersion}" />

            <div class="commitDiffSBS">
                {if $treediff}
                    {include file='sbs_treediff.tpl' diff_source=$branchdiff cssversion=$cssversion}
                {else}
                    {include file='sbs_non_treediff.tpl' diff_source=$branchdiff}
                {/if}
            </div>
            <div class="SBSFooter">
            </div>
        {/if}
    {else}
        <div style='color:red'>Branch is not found</div>
    {/if}

   {if $sexy}
       {include file="sexy_highlighter.tpl"}
   {/if}

 </div>

 {include file='footer.tpl'}

