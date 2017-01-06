{*
 * Refbadges
 *
 * Ref badges template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

<span class="refs">
    {foreach from=$commit->GetHeads() item=commithead}
        <span class="head">
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/heads/{$commithead->GetName()}">{$commithead->GetName()}</a>
        </span>
    {/foreach}
    {assign var=in_build_shown value=false}
    {foreach from=$commit->GetTags()|@array_reverse item=committag name=refbadgestags}
        {if substr($committag->GetName(),0,8) == "in-build"}
            {if $in_build_shown}
                {assign var=hide_tag value=true}
            {else}
                {assign var=hide_tag value=false}
            {/if}
            {assign var=in_build_shown value=true}
        {else}
            {assign var=hide_tag value=false}
        {/if}
        <span class="tag{if $hide_tag} hidden{/if}">
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$committag->GetName()}" {if !$committag->LightTag()}class="tagTip"{/if}>{$committag->GetName()}</a>
        </span>
        {if $smarty.foreach.refbadgestags.last && $smarty.foreach.refbadgestags.total > 2}
        <span class="tag" onclick="$(this).siblings('.hidden').toggle();">..</span>
        {/if}
    {/foreach}
    {foreach from=$commit->GetReviews() item=review}
        <span class="review">
            {if $review.hash_base}
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$review.hash_head}&amp;&amp;base={$review.hash_base}&amp;review={$review.review_id}">Review {$review.review_id}</a>
            {else}
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$review.hash_head}&amp;review={$review.review_id}">Review {$review.review_id}</a>
            {/if}
        </span>
    {/foreach}
</span>
