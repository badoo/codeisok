<div class="page_nav">
    <div class="title">
    {foreach name=sections from=$sections item=section}
        {if !$smarty.foreach.sections.first} | {/if}
    {if $current_section == $section}
        {$section|ucfirst}
    {else}
        <a href="/?a=gitosis&section={$section}">{$section|ucfirst}</a>
    {/if}
    {/foreach}
    </div>
</div>
