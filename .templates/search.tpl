{*
 *  search.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

{* Nav *}
{include file='nav.tpl' logcommit=$commit treecommit=$commit}

<div class="title compact stretch-evenly">
    {if $page > 0}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}">{t}First{/t}</a>
    {/if}
    {if $page > 0}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}{if $page > 1}&amp;pg={$page-1}{/if}" accesskey="p" title="Alt-p">{t}Prev{/t}</a>
    {/if}
    {if $hasmore}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" accesskey="n" title="Alt-n">{t}Next{/t}</a>
    {/if}
    <div class="page-search-container"></div>
</div>

{include file='title.tpl' titlecommit=$commit}

<table class="git-table">
    {* Print each match *}
    {foreach from=$results item=result}
        <tr>
            <td title="{if $result->GetAge() > 60*60*24*7*2}{$result->GetAge()|agestring}{else}{$result->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em>{if $result->GetAge() > 60*60*24*7*2}{$result->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{$result->GetAge()|agestring}{/if}</em></td>
            <td>
                <em>
                    {if $searchtype == 'author'}
                        {$result->GetAuthorName()|highlight:$search}
                    {elseif $searchtype == 'committer'}
                        {$result->GetCommitterName()|highlight:$search}
                    {else}
                        {$result->GetAuthorName()}
                    {/if}
                </em>
            </td>
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$result->GetHash()}" class="list commitTip" {if strlen($result->GetTitle()) > 50}title="{$result->GetTitle()}"{/if}><strong>{$result->GetTitle(50)}</strong>
                {if $searchtype == 'commit'}
                    {foreach from=$result->SearchComment($search) item=line name=match}
                        <br />{$line|highlight:$search:50}
                    {/foreach}
                {/if}

                {assign var=resulttree value=$result->GetTree()}
                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$result->GetHash()}">{t}Commit{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$result->GetHash()}">{t}Commitdiff{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$resulttree->GetHash()}&amp;hb={$result->GetHash()}">{t}Tree{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$result->GetHash()}" class="snapshotTip">{t}Snapshot{/t}</a>
                </div>
            </td>
        </tr>
    {/foreach}

  {if $hasmore}
    <tr>
      <td colspan="4"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" title="Alt-n">{t}next{/t}</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

