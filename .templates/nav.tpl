{*
 * Nav
 *
 * Nav links template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

    {if $current=='summary'}
        {t}summary{/t}
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary">{t}summary{/t}</a>
    {/if}
    |
    {if $current=='shortlog' || !$commit}
        {t}shortlog{/t}
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}refs/heads/{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}shortlog{/t}</a>
    {/if}
    |
    {if $current=='branchlog' || !$commit}
        {t}branchlog{/t}
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchlog{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}branchlog{/t}</a>
    {/if}
    |
    {if $current=='log' || !$commit}
        {t}log{/t}
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}refs/heads/{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}log{/t}</a>
    {/if}
    |
    {if $current=='commit' || !$commit || $branch}
        {t}commit{/t}
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$commit->GetHash()}&amp;retbranch={if $retbranch}{$retbranch}&amp;z={/if}{if $branch_name}{$branch_name}{/if}">{t}commit{/t}</a>
    {/if}
    |
    {if $current=='commitdiff' || !$commit || $branch}
        {t}commitdiff{/t}
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}&amp;retbranch={if $retbranch}{$retbranch}&amp;z={/if}{if $branch_name}{$branch_name}{/if}">{t}commitdiff{/t}</a>
    {/if}
    |
    {if $current=='tree' || !$commit}
        {t}tree{/t}
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree{if $treecommit}&amp;hb={$treecommit->GetHash()}{/if}{if $tree}&amp;h={$tree->GetHash()}{/if}">{t}tree{/t}</a>
    {/if}
    {if ($current=='shortlog' || $current == 'branchlog') && $branch_name}
        | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch_name}">{t}branchdiff{/t}</a>
    {/if}
    | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=reviews">{t}reviews{/t}</a>

    {if $review && $unified}
    | <a href="#" class="js-toggle-review-comments">toggle review comments</a>
    {/if}

    {if $retbranch}
        Current branch {$retbranch}
    {/if}