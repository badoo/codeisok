{*
 * Title
 *
 * Title template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

<div class="title">
    {if $titlecommit}
        {if $target == 'commitdiff'}
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()|escape}</a>
        {elseif $target == 'tree'}
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$titletree->GetHash()}&amp;hb={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()|escape}</a>
        {else}
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()|escape}</a>
        {/if}
        {include file='refbadges.tpl' commit=$titlecommit}
    {else}
        {if $target == 'summary'}
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary" class="title">&nbsp;</a>
        {elseif $target == 'shortlog'}
            {if $disablelink}
              {t}shortlog{/t}
            {else}
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog" class="title">{t}shortlog{/t}</a>
            {/if}
        {elseif $target == 'tags'}
            {if $disablelink}
              {t}tags{/t}
            {else}
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tags" class="title">{t}tags{/t}</a>
            {/if}
        {elseif $target == 'heads'}
            {if $disablelink}
              {t}heads{/t}
            {else}
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=heads" class="title">{t}heads{/t}</a>
            {/if}
        {else}
            &nbsp;
        {/if}
    {/if}
    <div class="title-right">
        {if $ticket}
       <span class="ticket-label">Issue: </span>#<a href="{$ticket_href}" class="ticket">{$ticket}</a>
        {/if}
        {foreach from=$reviews item=review}
        <a href="{$review.link}">Review {$review.review_id}</a>
        {if $review.diff_link}<span> (<a href="{$review.diff_link}">show diff</a>)</span>{/if}
        {/foreach}
    </div>
</div>
