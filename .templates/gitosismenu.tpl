<div class="page_nav">
    <form method="post" action="?a=gitosis&section=apply">
    {foreach name=sections from=$sections item=section}
        {if !$smarty.foreach.sections.first} | {/if}
    {if $section == 'apply'}
        <input type="submit" value="Apply changes" />
    {elseif $current_section == $section}
        {$section}
    {else}
        <a href="/?a=gitosis&section={$section}">{$section}</a>
    {/if}
    {/foreach}
    </form>
</div>
