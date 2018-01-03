{*
 * Headlist
 *
 * Head list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table class="git-table">
    {* Loop and display each head *}
    {foreach from=$headlist item=head name=heads}
        {assign var=headcommit value=$head->GetCommit()}
        <tr>
            <td width="15%"><em>{$headcommit->GetAge()|agestring}</em></td>
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/heads/{$head->GetName()}" class="list"><strong>{$head->GetName()}</strong></a>

                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/heads/{$head->GetName()}">{t}shortlog{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchlog&amp;h={$head->GetName()}">{t}branchlog{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h=refs/heads/{$head->GetName()}">{t}log{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$headcommit->GetHash()}">{t}Tree{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$head->GetName()}">{t}branchdiff{/t}</a>
                </div>
            </td>
       </tr>
   {/foreach}
   {if $hasmoreheads}
        <tr>
            <td colspan="3"><a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=heads">Show More</a></td>
        </tr>
   {/if}
 </table>

