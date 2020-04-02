{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

<div class="projectSearch">
    <form method="post" action="index.php" id="projectSearchForm"
            enctype="application/x-www-form-urlencoded">

        <span class="project-filter-container">
            <input type="text"
                id="projectSearchForm"
                name="s"
                {if $searchmode == 1}disabled="disabled"{/if}
                class="text-input projectSearchBox" {if $search}value="{$search|escape}"{/if}
                placeholder="{t}Filter projects{/t}" />

            {if $javascript}
                <img src="/images/loader.gif" class="searchSpinner" style="display: none;"/>
            {/if}

            <a href="index.php?a" class="clearSearch"
                {if !$search}style="display: none;"{/if}>x
            </a>
        </span>

        <span class="project-search-container">
            <input type="text"
                name="t"
                placeholder="Search text in project heads"
                value="{$text}"
                class="text-input"
                onkeydown="keydownSearchField(this);"></input>

            <input class="search" type="button" onclick="submitSearchForm(this);" value="Go" />
        </span>

        <span class="error-text" id="error"></span>

        {if $searchmode == 1}
            <a href="index.php">Cancel</a>
        {/if}
    </form>

    {if $allow_create_projects}
        <a class="simple-button create-project" href="/?a=project_create">New repo</a>
    {/if}
</div>

<table cellspacing="0" class="git-table git-table-expanded project-list">
  {foreach name=projects from=$projectlist item=proj}

    {if $smarty.foreach.projects.first}
      {* Header *}
      <tr class="projectHeader list_header">
        <th class="project-select"><input class="checkbox-input" type='checkbox' id='selectall' onclick='toggleCheckBoxes(this);'/></th>

        {if $order == "project"}
            <th class="project-name">{t}Project{/t} ▼</th>
        {else}
            <th class="project-name"><a class="header" href="{$SCRIPT_NAME}?o=project">{t}Project{/t}</a></th>
        {/if}

        {if $order == "descr"}
            <th class="project-desc">{t}Description{/t} ▼</th>
        {else}
            <th class="project-desc"><a class="header" href="{$SCRIPT_NAME}?o=descr">{t}Description{/t}</a></th>
        {/if}

        {if $order == "owner"}
            <th class="project-owner">{t}Owner{/t} ▼</th>
        {else}
            <th class="project-owner"><a class="header" href="{$SCRIPT_NAME}?o=owner">{t}Owner{/t}</a></th>
        {/if}

        {if $order == "age"}
            <th class="project-age">{t}Last change{/t} ▼</th>
        {else}
            <th class="project-age"><a class="header" href="{$SCRIPT_NAME}?o=age">{t}Last change{/t}</a></th>
        {/if}
      </tr>
    {/if}

    {if $currentcategory != $proj->GetCategory()}
        {assign var=currentcategory value=$proj->GetCategory()}
        {if $currentcategory != ''}
            <tr class="light categoryRow list_header">
                <th class="categoryName" colspan="6">
                    <span class="expander-folder expanded"></span>
                    {$currentcategory}
                </th>
            </tr>
        {/if}
    {/if}

    <tr class="projectRow">
        {assign var=currentproject value=$proj->GetProject()}
        <td><input class="checkbox-input projects_checkbox" type='checkbox' name='projects[{$currentproject}]' value='1' {if isset($projects.$currentproject) }checked="checked"{/if}></td>

        <td class="projectName">
            <a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=summary" class="list {if $currentcategory != ''}indent{/if}">{$currentproject}</a>
        </td>

        <td class="projectDescription">
            <a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=summary" class="list">{$proj->GetDescription()}</a>
        </td>

        <td class="projectOwner"><em>{$proj->GetOwner()|escape:'html'}</em>
            {if $proj->GetNotifyEmail()}
                <i>({$proj->GetNotifyEmail()|escape:'html'})</i>
            {/if}
        </td>

        {assign var=projecthead value=$proj->GetHeadCommit()}
        <td class="projectAge">
            {if $projecthead}
                {if $proj->GetAge() < 7200}   {* 60*60*2, or 2 hours *}
                    <span class="agehighlight"><strong><em>{$proj->GetAge()|agestring}</em></strong></span>
                {elseif $proj->GetAge() < 172800}   {* 60*60*24*2, or 2 days *}
                    <span class="agehighlight"><em>{$proj->GetAge()|agestring}</em></span>
                {else}
                    <em>{$proj->GetAge()|agestring}</em>
                {/if}
	        {else}
	            <em class="empty">{t}No commits{/t}</em>
	        {/if}

            <div class="actions">
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=summary">{t}Summary{/t}</a>
	            {if $projecthead}
	                <a class="simple-button" href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=shortlog">{t}Short Log{/t}</a>
	                <a class="simple-button" href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=log">{t}Log{/t}</a>
	                <a class="simple-button" href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=tree">{t}Tree{/t}</a>
	            {/if}
            </div>
        </td>
    </tr>

    {if $searchmode == 1 && isset($projects.$currentproject)}
        <tr class="{$rowclass}">
            <td colspan="6" class="code-search-results" id="searchresults[{$currentproject}]">
                <img src="/images/loader.gif" class="searchSpinner" onload='getSearchResults("{$text|urlencode}", "{$currentproject}", this);'/> Loading...
            </td>
        </tr>
    {/if}

    {foreachelse}
        {if $search}
            <div class="message">{t 1=$search}No matches found for "%1"{/t}</div>
        {else}
            <div class="message">{t}No projects found{/t}</div>
        {/if}
    {/foreach}
</table>

{include file='footer.tpl'}

