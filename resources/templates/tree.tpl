{*
 *  tree.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

{* Nav *}
{include file='nav.tpl' current='tree' logcommit=$commit}

{include file='title.tpl' titlecommit=$commit hasPageSearch=true}

{include file='path.tpl' pathobject=$tree target='tree'}

<div class="page_body">
    {* List files *}
    <table cellspacing="0" class="git-table treeTable">
        {include file='treelist.tpl'}
    </table>
</div>

{include file='footer.tpl'}

