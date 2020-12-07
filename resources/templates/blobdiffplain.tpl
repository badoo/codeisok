{*
 *  blobdiffplain.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff plain template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{assign var="diff" value=$filediff->GetDiff($file, false)}
{if $escape}
{$diff|escape:'html'}
{else}
{$diff}
{/if}
