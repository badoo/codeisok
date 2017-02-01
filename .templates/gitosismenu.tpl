<div class="page_nav">
    <form method="post" action="?a=gitosis&section=apply">
    {foreach name=sections from=$sections item=section}
        {if !$smarty.foreach.sections.first} | {/if}
    {if $section == 'apply'}
        <input type="submit" value="Apply changes" />
    {elseif $current_section == $section}
        <b>{$section|ucfirst}</b>
    {else}
        <a href="/?a=gitosis&section={$section}">{$section|ucfirst}</a>
    {/if}
    {/foreach}
    </form>
</div>
