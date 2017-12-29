{*
 *  searchfiles.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search files template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

{* Nav *}
{include file='nav.tpl' logcommit=$commit treecommit=$commit current=''}

<div class="title compact stretch-evenly">
    {if $page > 0}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}">{t}First{/t}</a>
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}{if $page > 1}&amp;pg={$page-1}{/if}" accesskey="p" title="Alt-p">{t}Prev{/t}</a>
    {/if}
    {if $hasmore}
        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" accesskey="n" title="Alt-n">{t}Next{/t}</a>
    {/if}
    <div class="page-search-container"></div>
</div>

<div class="title">
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$commit->GetHash()}" class="title">{$commit->GetTitle()}</a>
</div>

<table class="git-table">
    {* Print each match *}
    {foreach from=$results item=result key=path}
        <tr>
            {assign var=resultobject value=$result.object}
            {if $resultobject instanceof GitPHP_Tree}
	            <td>
		            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}" class="list"><strong>{$path}</strong></a>
                    <div class="actions">
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}">{t}Tree{/t}</a>
                    </div>
	            </td>
            {else}
	            <td>
		            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$result.object->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}" class="list"><strong>{$path|highlight:$search}</strong></a>
		            {foreach from=$result.lines item=line name=match key=lineno}
		                {if $smarty.foreach.match.first}<br />{/if}<span class="matchline">{$lineno}. {$line|highlight:$search:100:true}</span><br />
		            {/foreach}

                    <div class="actions">
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}">{t}Blob{/t}</a>
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$path}">{t}History{/t}</a>
                    </div>
	            </td>
            {/if}
        </tr>
  {/foreach}

  {if $hasmore}
    <tr>
        <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" title="Alt-n">{t}Next{/t}</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

