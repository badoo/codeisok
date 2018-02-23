<!DOCTYPE html>
<html dir="ltr">
  <head>
    <title>
        {if $project}{$project->GetProject()}{if $actionlocal}/{$actionlocal}{/if}{else}codeisok{/if}
    </title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    {if $project}
        <link rel="alternate" title="{$project->GetProject()} log (Atom)" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=atom" type="application/atom+xml" />
        <link rel="alternate" title="{$project->GetProject()} log (RSS)" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=rss" type="application/rss+xml" />
    {/if}

    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.png" />

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

    <link rel="stylesheet" href="/css/gitphp.css?v={$cssversion}" type="text/css" />
    <link rel="stylesheet" href="/css/{$stylesheet}?v={$cssversion}" type="text/css" />
    <link rel="stylesheet" href="/css/review.css?v={$cssversion}" type="text/css" />

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
            {* {if file_exists("js/$script.min.js")}
                <script type="text/javascript" src="/js/{$script}.min.js?v={$jsversion}"></script>
            {else} *}
                <script type="text/javascript" src="/js/{$script}.js?v={$jsversion}"></script>
            {* {/if} *}
        {/foreach}
    {/if}

    {if $extrajs_files}
        {foreach from=$extrajs_files item=js_file}
            <script type="text/javascript" src="/{$js_file}?v={$jsversion}"></script>
        {/foreach}
    {/if}

    {$smarty.capture.header}

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

    {if !$no_user_header}
    <div class="page_header {if $adminarea}adminheader{/if}">
        <a class="logo" href="index.php?a">codeisok</a>

        <div class="user_block">
            {if $Session->isAuthorized()}
                {$user_name} ({if $is_gitosis_admin && !$adminarea}<a href="{$url_gitosis}">Admin</a>, {/if}<a href="{$url_logout}">Logout</a>)
            {else}
                <a href="{$url_login}">{$user_name}</a>
            {/if}
        </div>

        <span class="project-path">
            {if $adminarea}
                <a href="/">⟵ projects list</a>
            {/if}

            {if $project}
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary">{$project->GetProject()}</a>
                {if $actionlocal}
                    / {$actionlocal}
                {/if}
            {/if}
        </span>
    </div>
    {/if}

    {if $project}
        <div class="page-search">

            {if $action == 'commitdiff' || $action == 'branchdiff'}
                <div class="gear-icon js-show-extra-settings">
                    <div class="extra-settings">
                        {if $action == 'commitdiff' || $action == 'branchdiff'}
                            <div class="search-panel">Ignore format <input id="diff-ignore-format" class="checkbox-input" type="checkbox" {if $ignoreformat}checked="checked"{/if}/></div>
                            <div class="search-panel">Ignore whitespace <input id="diff-ignore-whitespace" class="checkbox-input" type="checkbox" {if $ignorewhitespace}checked="checked"{/if}/></div>
                            <div class="search-panel">Context <input class="text-input" type="text" size="2" id="diff-context" {if $diffcontext}value="{$diffcontext}"{/if} /></div>
                        {/if}
                    </div>
                </div>
            {/if}

            {if $enablebase}
                <form class="search-panel" action="{$SCRIPT_NAME}" method="get">
                    {foreach from=$requestvars key=var item=val}
                        {if $var != "base"}
                            <input type="hidden" name="{$var|escape}" value="{$val|escape}" />
                        {/if}
                    {/foreach}
                    Compare with
                    <select class="select-input" {if $base_disabled}disabled="disabled"{/if} name='base' onchange='this.form.submit();'>
                        {foreach from=$base_branches item=branch}
                            <option {if $branch == $base}selected="selected"{/if} value='{$branch}'>{$branch}</option>
                        {/foreach}
                    </select>
                </form>
            {/if}

            {if $enablesearch}
                <form class="search-panel" method="get" action="index.php" enctype="application/x-www-form-urlencoded">
                    <input type="hidden" name="p" value="{$project->GetProject()}" />
                    <input type="hidden" name="a" value="search" />
                    <input type ="hidden" name="h" value="{if $commit}{$commit->GetHash()}{else}HEAD{/if}" />
                    <select class="select-input" name="st">
                        <option {if $searchtype == 'commit'}selected="selected"{/if} value="commit">{t}Commit{/t}</option>
                        <option {if $searchtype == 'author'}selected="selected"{/if} value="author">{t}Author{/t}</option>
                        <option {if $searchtype == 'committer'}selected="selected"{/if} value="committer">{t}Committer{/t}</option>
                        {if $filesearch}
                            <option {if $searchtype == 'file'}selected="selected"{/if} value="file">{t}File{/t}</option>
                        {/if}
                    </select>
                    <input class="text-input" placeholder="Search" type="text" name="s" {if $search}value="{$search|escape}"{/if} />
                </form>
            {/if}

        </div>
    {/if}

    <div id="notifications"></div>
