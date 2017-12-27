{*
 *  commitdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

<input type="hidden" id="review_hash_head" value="{$commit->GetHash()}" />
<input type="hidden" id="review_hash_base" value="" />


 {* Nav *}
 <div class="page_nav">
   {if $commit}
      {assign var=tree value=$commit->GetTree()}
   {/if}
   {include file='nav.tpl' current='commitdiff' logcommit=$commit treecommit=$commit}
   <br />

    <div class="diff_modes">
        <strong>Change diff mode:</strong>

        {if $sidebyside}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}{if $hashparent}&amp;hp={$hashparent}{/if}&amp;{if $review}review={$review}{/if}&amp;o=unified">{t}unified{/t}</a>
        {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}{if $hashparent}&amp;hp={$hashparent}{/if}&amp;{if $review}review={$review}{/if}&amp;o=sidebyside">{t}side by side{/t}</a>
        {/if}

    | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff_plain&amp;h={$commit->GetHash()}{if $hashparent}&amp;hp={$hashparent}{/if}">{t}plain{/t}</a>

        <strong>{t}TreeDiff: {/t}</strong>
        {if $treediff}
            <div class="switcher checked">
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}{if $hashparent}&amp;hp={$hashparent}{/if}&amp;{if $review}review={$review}{/if}&amp;treediff=0"></a>
            </div>
        {else}
            <div class="switcher">
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}{if $hashparent}&amp;hp={$hashparent}{/if}&amp;{if $review}review={$review}{/if}&amp;treediff=1"></a>
            </div>
        {/if}
    </div>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 <div class="page_body">
   <div class="diff_summary">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   {foreach from=$commit->GetComment() item=line}
     {$line|htmlspecialchars|buglink:$bugpattern:$bugurl}<br />
   {/foreach}

   <h2 class="only-comments-warning">Showing review comments only</h2>

    {*
        UNIFIED
    *}
    {if $unified}
        {if $treediff}
            {include file='unified_treediff.tpl' diff_source=$commit_tree_diff}
        {else}
            <hr>

            {include file='extensions_filter.tpl' stasuses=$statuses extensions=$extensions folders=$folders}

            <table style="float: left; border: 0; padding: 0; margin: 0;">
                {foreach from=$commit_tree_diff item=filediff}
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

            {include file='unified_diff_contents.tpl' diff_source=$commit_tree_diff}

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
                {include file='sbs_treediff.tpl' diff_source=$commit_tree_diff cssversion=$cssversion}
            {else}
                {include file='sbs_non_treediff.tpl' diff_source=$commit_tree_diff}
            {/if}
        </div>
        <div class="SBSFooter">
        </div>
    {/if}
   </div>

   {if $sexy}
       {include file="sexy_highlighter.tpl"}
   {/if}

 </div>

 {include file='footer.tpl'}

