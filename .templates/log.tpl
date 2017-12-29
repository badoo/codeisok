{*
*  log.tpl
*  gitphp: A PHP git repository browser
*  Component: Log view template
*
*  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
*}
{include file='header.tpl'}

{* Nav *}
{include file='nav.tpl' current='log' logcommit=$commit treecommit=$commit logmark=$mark}

<div class="title compact stretch-evenly">
    {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log{if $mark}&amp;m={$mark->GetHash()}{/if}">{t}HEAD{/t}</a>
    {/if}

    {if $page > 0}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h={$commit->GetHash()}&amp;pg={$page-1}{if $mark}&amp;m={$mark->GetHash()}{/if}" accesskey="p" title="Alt-p">{t}Prev{/t}</a>
    {/if}

    {if $hasmorerevs}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()}{/if}" accesskey="n" title="Alt-n">{t}Next{/t}</a>
    {/if}

    <div class="page-search-container"></div>
</div>

{if $mark}
    <div class="title compact">
        {t}Selected for diff: {/t}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$mark->GetHash()}" class="list commitTip" {if strlen($mark->GetTitle()) > 100}title="{$mark->GetTitle()}"{/if}><strong>{$mark->GetTitle(100)}</strong></a>
        &nbsp;&nbsp;&nbsp;
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}Deselect{/t}</a>
    </div>
{/if}

<table class="git-table">
    {foreach from=$revlist item=rev name=revlist}
        <tr class="commit-head {if $mark && $mark->GetHash() == $rev->GetHash()}selected{/if}">
            <td width="10%">{$rev->GetAge()|agestring}</td>
            <td width="10%">{$rev->GetAuthorName()}</td>
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}">{$rev->GetTitle()}</a>
                {include file='refbadges.tpl' commit=$rev}
                {if $ticket && $smarty.foreach.revlist.first}
                    <div class="title-right"><a href="{$ticket_href}">{$ticket}</a></div>
                {/if}

                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}">{t}Commit{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$rev->GetHash()}">{t}Commitdiff{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$rev->GetHash()}&amp;hb={$rev->GetHash()}">{t}Tree{/t}</a>

                    {if $mark}
                        {if $mark->GetHash() == $rev->GetHash()}
                            <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}Deselect{/t}</a>
                        {else}
                            {if $mark->GetCommitterEpoch() > $rev->GetCommitterEpoch()}
                                {assign var=markbase value=$mark}
                                {assign var=markparent value=$rev}
                            {else}
                                {assign var=markbase value=$rev}
                                {assign var=markparent value=$mark}
                            {/if}
                            <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$markbase->GetHash()}&amp;hp={$markparent->GetHash()}">{t}Diff with selected{/t}</a>
                        {/if}
                    {else}
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h={$commit->GetHash()}&amp;pg={$page}&amp;m={$rev->GetHash()}">{t}Select for diff{/t}</a>
                    {/if}
                </div>
            </td>
        </tr>

        <tr class="commit-body">
            <td width="10%"></td>
            <td width="10%"></td>
            <td>
                {assign var=bugpattern value=$project->GetBugPattern()}
                {assign var=bugurl value=$project->GetBugUrl()}

                {foreach from=$rev->GetComment() item=line}
                    <div>{$line|htmlspecialchars|buglink:$bugpattern:$bugurl|strip}</div>
                {/foreach}
            </td>
        </tr>

    {foreachelse}
        <tr>
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary" class="title">&nbsp</a>
            </td>
            <td>
                {if $commit}
                    {assign var=commitage value=$commit->GetAge()|agestring}
                    {t 1=$commitage}Last change %1{/t}
                {else}
                    <em>{t}No commits{/t}</em>
                {/if}
            </td>
        </tr>
    {/foreach}
</table>

{include file='footer.tpl'}
