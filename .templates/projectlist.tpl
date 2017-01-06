{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

<div class="index_header">
{if file_exists('templates/hometext.tpl') }
{include file='hometext.tpl'}
{else}
{* default header *}
<p>
git source code archive
</p>
{/if}
</div>

<div class="projectSearch">
<form method="post" action="index.php" id="projectSearchForm" enctype="application/x-www-form-urlencoded">
{t}Filter projects{/t}: <input type="text" name="s" {if $searchmode == 1}disabled="disabled"{/if} class="projectSearchBox" {if $search}value="{$search|escape}"{/if} /> <a href="index.php?a" class="clearSearch" {if !$search}style="display: none;"{/if}>X</a> {if $javascript}<img src="images/search-loader.gif" class="searchSpinner" style="display: none;" />{/if}
<span style="padding-left:50px;" disabled="true">Search text in project heads: <input name="t" value="{$text}" class="projectSearchBox" onkeydown="keydownSearchField(this);"></input><input type="button" onclick="submitSearchForm(this);" value="Go"></span>
{if $searchmode == 1}
<a href="index.php">Cancel</a>
{/if}
<span style="color:red !important;" id="error"></span>
</form>
</div>

<table cellspacing="0" class="projectList">
  {foreach name=projects from=$projectlist item=proj}
    {if $smarty.foreach.projects.first}
      {* Header *}
      <tr class="projectHeader" style="background-color:#d9d8d1;">
          <th><input type='checkbox' id='selectall' onclick='toggleCheckBoxes(this);'/></th>
        {if $order == "project"}
          <th>{t}Project{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=project">{t}Project{/t}</a></th>
        {/if}
        {if $order == "descr"}
          <th>{t}Description{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=descr">{t}Description{/t}</a></th>
        {/if}
        {if $order == "owner"}
          <th>{t}Owner{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=owner">{t}Owner{/t}</a></th>
        {/if}
        {if $order == "age"}
          <th>{t}Last Change{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=age">{t}Last Change{/t}</a></th>
        {/if}
        <th>{t}Actions{/t}</th>
      </tr>
    {/if}

    {if $currentcategory != $proj->GetCategory()}
      {assign var=currentcategory value=$proj->GetCategory()}
      {if $currentcategory != ''}
        <tr class="light categoryRow" style="background-color:#d9d8d1;">
          <th class="categoryName" colspan="6">{$currentcategory}</th>
        </tr>
      {/if}
    {/if}
    {cycle values="light,dark" assign="rowclass"}
    <tr class="{$rowclass} projectRow">
      {assign var=currentproject value=$proj->GetProject()}
      <td><input type='checkbox' name='projects[{$currentproject}]' class="projects_checkbox" value='1' {if isset($projects.$currentproject) }checked="checked"{/if}></td>
      <td class="projectName">
        <a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=summary" class="list {if $currentcategory != ''}indent{/if}">{$currentproject}</a>
      </td>
      <td class="projectDescription"><a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=summary" class="list">{$proj->GetDescription()}</a></td>
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
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=summary">{t}summary{/t}</a>
	{if $projecthead}
	| 
	<a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=shortlog">{t}shortlog{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=log">{t}log{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$currentproject|urlencode}&amp;a=tree">{t}tree{/t}</a> 
	{/if}
      </td>
    </tr>
  {if $searchmode == 1 && isset($projects.$currentproject)}
    <tr class="{$rowclass}">
    <td colspan="6" id="searchresults[{$currentproject}]">
    <img src="images/search-loader.gif" class="searchSpinner" onload='getSearchResults("{$text|urlencode}", "{$currentproject}", this);'/> Loading...
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

