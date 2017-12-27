{*
 * Shortlog List
 *
 * Shortlog list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

<table cellspacing="0" class="git-table shortlog">
{foreach from=$revlist item=rev}
    <tr>
        <td title="{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetAge()|agestring}{else}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}">
            {if $rev->GetAge() > 60*60*24*7*2}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{$rev->GetAge()|agestring}{/if}
        </td>

        <td>
            {$rev->GetAuthorName()}
        </td>

        <td>
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}" class="list commitTip" {if strlen($rev->GetTitle()) > 150}title="{$rev->GetTitle()|htmlspecialchars}"{/if}>
                {$rev->GetTitle(150)|escape}
            </a>

            {include file='refbadges.tpl' commit=$rev}

            <div class="actions">
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}&amp;retbranch={$branch_name}">{t}commit{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$rev->GetHash()}&amp;retbranch={$branch_name}">{t}commitdiff{/t}</a>
                <a class="simple-button" ref="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$rev->GetHash()}&amp;hb={$rev->GetHash()}">{t}tree{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$rev->GetHash()}" class="snapshotTip">{t}snapshot{/t}</a>
                {if $source == 'shortlog' || $source == 'branchlog'}
                    {if $mark}
                        {if $mark->GetHash() == $rev->GetHash()}
                            <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$source}&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}deselect{/t}</a>
                        {else}
                            {if $mark->GetCommitterEpoch() > $rev->GetCommitterEpoch()}
                                {assign var=markbase value=$mark}
                                {assign var=markparent value=$rev}
                            {else}
                                {assign var=markbase value=$rev}
                                {assign var=markparent value=$mark}
                            {/if}
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$markbase->GetHash()}&amp;hp={$markparent->GetHash()}">{t}diff with selected{/t}</a>
                        {/if}
                    {else}
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$source}&amp;h={$commit->GetHash()}&amp;pg={$page}&amp;m={$rev->GetHash()}">{t}select for diff{/t}</a>
                    {/if}
                {/if}
            </div>
        </td>
    </tr>
{foreachelse}
    <tr><td><em>{t}No commits{/t}</em></td></tr>
{/foreach}

{if $hasmorerevs}
<tr>
{if $source == 'summary'}
<td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog">&hellip;</a></td>
{elseif $source == 'shortlog'}
<td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()}{/if}" title="Alt-n">{t}next{/t}</a></td>
{elseif $source == 'branchlog'}
<td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchlog&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()}{/if}" title="Alt-n">{t}next{/t}</a></td>
{/if}
</tr>
{/if}
</table>

