{*
 *  header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <title>{if $project}{$project->GetProject()}{if $actionlocal}/{$actionlocal}{/if}{else}gitphp{/if}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    {if $project}
      <link rel="alternate" title="{$project->GetProject()} log (Atom)" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=atom" type="application/atom+xml" />
      <link rel="alternate" title="{$project->GetProject()} log (RSS)" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=rss" type="application/rss+xml" />
    {/if}
    <link rel="stylesheet" href="/css/gitphp.css?v={$cssversion}" type="text/css" />
    <link rel="stylesheet" href="/css/{$stylesheet}?v={$cssversion}" type="text/css" />
    {if $extracss}
    <style type="text/css">
    {$extracss}
    </style>
    {/if}
    {if $extracss_files}
    {foreach from=$extracss_files item=css_file}
    <link rel="stylesheet" href="/{$css_file}?v={$cssversion}" type="text/css" />
    {/foreach}
    {/if}
    {if $javascript}
    <script type="text/javascript">
      var GITPHP_RES_LOADING="{t escape='js'}Loading…{/t}";
      var GITPHP_RES_LOADING_BLAME_DATA="{t escape='js'}Loading blame data…{/t}";
      var GITPHP_RES_SNAPSHOT="{t escape='js'}snapshot{/t}";
      var GITPHP_RES_NO_MATCHES_FOUND='{t escape=no}No matches found for "%1"{/t}';
      var GITPHP_SNAPSHOT_FORMATS = {ldelim}
      {foreach from=$snapshotformats key=format item=extension name=formats}
        "{$format}": "{$extension}"{if !$smarty.foreach.formats.last},{/if}
      {/foreach}
      {rdelim}
    </script>
    <link rel="stylesheet" href="/css/ext/jquery.qtip.css" type="text/css" />
    <script type="text/javascript" src="/js/ext/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="/js/utils.js?v={$jsversion}"></script>
    <script type="text/javascript" src="/js/review.js?v={$jsversion}"></script>
    <script type="text/javascript" src="/js/suppresseddiff.js?v={$jsversion}"></script>
    <script type="text/javascript" src="/js/ext/jquery.qtip.min.js"></script>
    <script type="text/javascript" src="/js/ext/jquery.cookie.js"></script>
    <script type="text/javascript" src="/js/diff.js?v={$jsversion}"></script>
    <script type="text/javascript" src="/js/session_checker.js?v={$jsversion}"></script>
    {if file_exists('js/tooltips.min.js')}
    <script type="text/javascript" src="/js/tooltips.min.js?v={$jsversion}"></script>
    {else}
    <script type="text/javascript" src="/js/tooltips.js?v={$jsversion}"></script>
    {/if}
    {if file_exists('js/lang.min.js')}
    <script type="text/javascript" src="/js/lang.min.js?v={$jsversion}"></script>
    {else}
    <script type="text/javascript" src="/js/lang.js?v={$jsversion}"></script>
    {/if}
    {foreach from=$extrascripts item=script}
    {if file_exists("js/$script.min.js")}
    <script type="text/javascript" src="/js/{$script}.min.js?v={$jsversion}"></script>
    {else}
    <script type="text/javascript" src="/js/{$script}.js?v={$jsversion}"></script>
    {/if}
    {/foreach}
    {/if}
    {if $extrajs_files}
    {foreach from=$extrajs_files item=js_file}
    <script type="text/javascript" src="/{$js_file}?v={$jsversion}"></script>
    {/foreach}
    {/if}
    {$smarty.capture.header}
    <link rel="stylesheet" href="/css/review.css?v={$cssversion}" type="text/css" />
    {if $fixlineheight}
    <link rel="stylesheet" href="/css/fix_lineheight.css?v={$cssversion}" type="text/css" />
    {/if}
  </head>
  <body>
    <div id="session_checker">
        <div id="session_checker_popup">
            Connectivity problem! Please <a href="{$url_login}" target="_blank">login</a>.
        </div>
    </div>
    <div class="page_header {if $adminarea}adminheader{/if}">


        {if !$no_user_header}
            <div class="user_block">
                {if $Session->isAuthorized()}
                    logged as: {$user_name} ({if $is_gitosis_admin && !$adminarea}<a href="{$url_gitosis}">admin</a> | {/if}<a href="{$url_logout}">logout</a>)
                {else}
                    logged as: <a href="{$url_login}">{$user_name}</a>
                {/if}
            </div>
        {/if}

        {if $supportedlocales}
            <div class="lang_select">
                <form action="{$SCRIPT_NAME}" method="get" id="frmLangSelect">
                    <div>
                        {foreach from=$requestvars key=var item=val}
                            {if $var != "l"}
                                <input type="hidden" name="{$var|escape}" value="{$val|escape}" />
                            {/if}
                        {/foreach}
                        <label for="selLang">{t}language:{/t}</label>
                        <select name="l" id="selLang">
                            {foreach from=$supportedlocales key=locale item=language}
                                <option {if $locale == $currentlocale}selected="selected"{/if} value="{$locale}">{if $language}{$language} ({$locale}){else}{$locale}{/if}</option>
                            {/foreach}
                        </select>
                        <input type="submit" value="{t}set{/t}" id="btnLangSet" />
                    </div>
                </form>
            </div>
        {/if}

      {if $adminarea}
      <a href="/">⟵ projects list</a>
      {else}
      <a href="index.php?a">{if $homelink}{$homelink}{else}{t}projects{/t}{/if}</a> /
      {/if}
      {if $project}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary">{$project->GetProject()}</a>
        {if $actionlocal}
           / {$actionlocal}
        {/if}
        <div class="search" style="overflow: auto;">
        {if $enablesearch}
            <div class="diff_controls">
            <form method="get" action="index.php" enctype="application/x-www-form-urlencoded">
                <input type="hidden" name="p" value="{$project->GetProject()}" />
                <input type="hidden" name="a" value="search" />
                <input type ="hidden" name="h" value="{if $commit}{$commit->GetHash()}{else}HEAD{/if}" />
                <select name="st">
                    <option {if $searchtype == 'commit'}selected="selected"{/if} value="commit">{t}commit{/t}</option>
                    <option {if $searchtype == 'author'}selected="selected"{/if} value="author">{t}author{/t}</option>
                    <option {if $searchtype == 'committer'}selected="selected"{/if} value="committer">{t}committer{/t}</option>
                    {if $filesearch}
                    <option {if $searchtype == 'file'}selected="selected"{/if} value="file">{t}file{/t}</option>
                    {/if}
                </select> {t}search{/t}: <input type="text" name="s" {if $search}value="{$search|escape}"{/if} />
            </form>
            </div>
        {/if}
        {if $action == 'commitdiff' || $action == 'branchdiff'}
        <div class="diff_controls">Context: <input type="text" size="3" id="diff-context" {if $diffcontext}value="{$diffcontext}"{/if} /></div>
        <div class="diff_controls checkbox">Ignore whitespace: <input id="diff-ignore-whitespace" type="checkbox" {if $ignorewhitespace}checked="checked"{/if}/></div>
        <div class="diff_controls checkbox">Ignore format: <input id="diff-ignore-format" type="checkbox" {if $ignoreformat}checked="checked"{/if}/></div>
        {/if}
        {if $enablebase}
            <div class="diff_controls">
            <form action="{$SCRIPT_NAME}" method="get">
                {foreach from=$requestvars key=var item=val}
                    {if $var != "base"}
                        <input type="hidden" name="{$var|escape}" value="{$val|escape}" />
                    {/if}
                {/foreach}
                Compare with branch: <select {if $base_disabled}disabled="disabled"{/if} name='base' onchange='this.form.submit();'>
                    {foreach from=$base_branches item=branch}
                    <option {if $branch == $base}selected="selected"{/if} value='{$branch}'>{$branch}</option>
                    {/foreach}
                </select>
            </form>
            </div>
        {/if}
        </div>
      {/if}

    </div>
    <div id="notifications"></div>
