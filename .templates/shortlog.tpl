{*
 *  shortlog.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

{* Nav *}
{include file='nav.tpl' current=$controller logcommit=$commit treecommit=$commit logmark=$mark}

<div class="title compact stretch-evenly">
    {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$controller}{if $mark}&amp;m={$mark->GetHash()}{/if}">{t}HEAD{/t}</a>
    {/if}

    {if $page > 0}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$controller}&amp;h={$commit->GetHash()}&amp;pg={$page-1}{if $mark}&amp;m={$mark->GetHash()}{/if}" accesskey="p" title="Alt-p">{t}Prev{/t}</a>
    {/if}

    {if $hasmorerevs}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$controller}&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()}{/if}" accesskey="n" title="Alt-n">{t}Next{/t}</a>
    {/if}
    <div class="page-search-container"></div>
</div>

{if $mark}
    <div class="title compact">
        {t}Selected for diff: {/t}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$mark->GetHash()}" class="list commitTip" {if strlen($mark->GetTitle()) > 100}title="{$mark->GetTitle()|htmlspecialchars}"{/if}><strong>{$mark->GetTitle(100)}</strong></a>
        &nbsp;&nbsp;&nbsp;
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$controller}&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}Deselect{/t}</a>
    </div>
{/if}

{include file='title.tpl' target='summary'}

{include file='shortloglist.tpl' source=$controller}

{include file='footer.tpl'}

