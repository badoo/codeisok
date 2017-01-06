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
        <i>
            {if $last_apply}Last apply {$last_apply.apply_time} by {$last_apply.username}.{/if}
            {if $last_request}Last request {$last_request.request_time} by {$last_request.username}.{/if}
        </i>
    </form>
</div>