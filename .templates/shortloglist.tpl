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
    <tr {if !empty($mark) && $mark->GetHash() == $rev->GetHash()}class="selected"{/if}>
        <td width="15%" title="{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetAge()|agestring}{else}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d %H:%M:%S"}{/if}">
            {if $rev->GetAge() > 60*60*24*7*2}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{$rev->GetAge()|agestring}{/if}
        </td>

        <td width="10%">
            {$rev->GetAuthorName()}
        </td>

        <td>
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}" class="list commitTip" {if strlen($rev->GetTitle()) > 150}title="{$rev->GetTitle()|htmlspecialchars}"{/if}>
                {$rev->GetTitle(150)|escape}
            </a>

            {include file='refbadges.tpl' commit=$rev}

            <div class="actions">
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}&amp;retbranch={$branch_name}">{t}Commit{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$rev->GetHash()}&amp;retbranch={$branch_name}">{t}Commitdiff{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$rev->GetHash()}&amp;hb={$rev->GetHash()}">{t}Tree{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$rev->GetHash()}" class="snapshotTip">{t}Snapshot{/t}</a>
                {if $source == 'shortlog' || $source == 'branchlog'}
                    {if !empty($mark)}
                        {if $mark->GetHash() == $rev->GetHash()}
                            <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$source}&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}Deselect{/t}</a>
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
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a={$source}&amp;h={$commit->GetHash()}&amp;pg={$page}&amp;m={$rev->GetHash()}">{t}Select for diff{/t}</a>
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
            <td colspan="3"><a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog">Show More</a></td>
        {elseif $source == 'shortlog'}
            <td colspan="3"><a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if !empty($mark)}&amp;m={$mark->GetHash()}{/if}" title="Alt-n">{t}Next{/t}</a></td>
        {elseif $source == 'branchlog'}
            <td colspan="3"><a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchlog&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if !empty($mark)}&amp;m={$mark->GetHash()}{/if}" title="Alt-n">{t}Next{/t}</a></td>
        {/if}
    </tr>
{/if}
</table>

