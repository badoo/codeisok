{*
 *  blobdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

{include file='nav.tpl' treecommit=$commit}

<div class="diff-controls">
    <div class="diff-controls__options">
        <div class="diff-controls__item">
            <div class="diff_modes">
                <a class="{if $unified}is-active{/if}" class="" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}&amp;o=unified">{t}Unified{/t}</a>
                <a class="{if $sidebyside}is-active{/if}" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}&amp;o=sidebyside">{t}Side by side{/t}</a>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff_plain&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;f={$file}">{t}Plain{/t}</a>
            </div>
        </div>
    </div>
    <div class="diff-controls__options page-search-container"></div>
</div>

{include file='title.tpl' titlecommit=$commit}

{include file='path.tpl' pathobject=$blobparent target='blob'}

<div class="page_body">
    <div class="diff_info">
        {* Display the from -> to diff header *}
        {t}Blob{/t}: <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}a/{$file}{else}{$blobparent->GetHash()}{/if}</a>
        &gt;
        {t}Blob{/t}: <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$blob->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}b/{$file}{else}{$blob->GetHash()}{/if}</a>
    </div>

    {if $sidebyside}
        {* Display the sidebysidediff *}
        <script type="text/javascript" src="/lib/mergely/codemirror.min.js?v={$libVersion}"></script>
        <link type="text/css" rel="stylesheet" href="/lib/mergely/codemirror.css?v={$libVersion}" />
        <script type="text/javascript" src="/lib/mergely/mergely.js?v={$libVersion}"></script>
        <link type="text/css" rel="stylesheet" href="/lib/mergely/mergely.css?v={$libVersion}" />

        <div class="file-list">
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
                {elseif ($filediff->GetStatus() == 'M') || ($filediff->GetStatus() == 'R')}
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
        </div>

        {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
    {else}
        {* Display the diff *}
        {include file='filediff.tpl' diff=$filediff->GetDiff($file, false, true)}
    {/if}
</div>

{include file='footer.tpl'}

