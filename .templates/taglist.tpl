{*
 * Taglist
 *
 * Tag list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table cellspacing="0" class="git-table tagTable">
    {foreach from=$taglist item=tag name=tag}
        <tr>
            {assign var=object value=$tag->GetObject()}
            {assign var=tagcommit value=$tag->GetCommit()}
            {assign var=objtype value=$tag->GetType()}
            <td width="15%"><em>{$tagcommit->GetAge()|agestring}</em></td>

            <td>
                {if $objtype == 'commit'}
                    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$object->GetHash()}" class="list"><strong>{$tag->GetName()}</strong></a>
                {elseif $objtype == 'tag'}
                    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$tag->GetName()}" class="list"><strong>{$tag->GetName()}</strong></a>
                {/if}
	        </td>

            <td>
	            {assign var=comment value=$tag->GetComment()}
                {if count($comment) > 0}
                    <a class="list {if !$tag->LightTag()}tagTip{/if}" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$tag->GetName()}">{$comment[0]}</a>
                {/if}

                <div class="actions">
                    {if !$tag->LightTag()}
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$tag->GetName()}">{t}Tag{/t}</a>
                    {/if}
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$tagcommit->GetHash()}">{t}Commit{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h={$tagcommit->GetHash()}">{t}Short Log{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h={$tagcommit->GetHash()}">{t}Log{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$tagcommit->GetHash()}" class="snapshotTip">{t}Snapshot{/t}</a>
                </div>
           </td>
       </tr>
    {/foreach}

    {if $hasmoretags}
        <tr>
            <td colspan="3"><a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tags">Show More</a></td>
        </tr>
    {/if}
</table>
