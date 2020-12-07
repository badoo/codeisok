{*
 *  heads.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Head view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' commit=$head treecommit=$head}
 </div>

 {include file='title.tpl' target='summary'}

 {include file='headlist.tpl'}

 {include file='footer.tpl'}

