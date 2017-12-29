{*
 *  history.tpl
 *  gitphp: A PHP git repository browser
 *  Component: History view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

{* Page header *}
<div class="page_nav">
    {include file='nav.tpl' treecommit=$commit}
</div>

{include file='title.tpl' titlecommit=$commit}

{include file='path.tpl' pathobject=$blob target='blob'}

<table class="git-table">
    {* Display each history line *}
    {foreach from=$blob->GetHistory() item=historyitem}
        {assign var=historycommit value=$historyitem->GetCommit()}
        <tr>
            <td width="10%" title="{if $historycommit->GetAge() > 60*60*24*7*2}{$historycommit->GetAge()|agestring}{else}{$historycommit->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em>{if $historycommit->GetAge() > 60*60*24*7*2}{$historycommit->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{$historycommit->GetAge()|agestring}{/if}</em></td>
            <td width="10%"><em>{$historycommit->GetAuthorName()}</em></td>
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$historycommit->GetHash()}" class="list commitTip" {if strlen($historycommit->GetTitle()) > 100}title="{$historycommit->GetTitle()}"{/if}><strong>{$historycommit->GetTitle(100)}</strong></a>
                {include file='refbadges.tpl' commit=$historycommit}

                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$historycommit->GetHash()}">{t}Commit{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$historycommit->GetHash()}">{t}Commitdiff{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;hb={$historycommit->GetHash()}&amp;f={$blob->GetPath()}">{t}Blob{/t}</a>
                    {if $blob->GetHash() != $historyitem->GetToHash()}
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$historyitem->GetToHash()}&amp;hb={$historycommit->GetHash()}&amp;f={$blob->GetPath()}">{t}Diff to current{/t}</a>
                    {/if}
                </div>
            </td>
        </tr>
    {/foreach}
</table>

{include file='footer.tpl'}
