{*
 *  filediff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Single file diff template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{assign var="decoration" value=$filediff->GetDecorationData()}
{if $filediff->getDiffTypeImage()}
{$diff}
{else}
{if $filediff->getDiffTooLarge() && $sexy}
<p class="too_large_diff">
    <img src="/images/loader.gif" alt="Please wait" />
    <a data-brush="{$decoration.highlighter_brush_name}" href="?p={$project->GetProject()|urlencode}&a=blobdiff_plain&hp={$filediff->getDiffTreeLine()|urlencode}&h=&f={$filediff->GetFromFile()|urlencode}" class="show_suppressed_diff">Diff suppressed. Click to show.</a>
</p>
{else}
{if $sexy}
<pre class="brush: {$decoration.highlighter_brush_name}" data-marks="{$filediff->getInlineChanges()}">{foreach from=$diff item=diffline}{$diffline}
{/foreach}</pre>
{else}
<div class="pre">
{foreach from=$diff item=diffline}
{if substr($diffline,0,1)=="+"}
<span class="diffplus">{$diffline}</span>
{elseif substr($diffline,0,1)=="-"}
<span class="diffminus">{$diffline}</span>
{elseif substr($diffline,0,1)=="@"}
<span class="diffat">{$diffline}</span>
{else}
<span>{$diffline}</span>
{/if}
{/foreach}
</div>
{/if}
{/if}
{/if}
