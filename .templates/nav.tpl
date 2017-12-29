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

<div class="git-tabs">
    {if $current=='summary'}
        <span class="active">{t}Summary{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary">{t}Summary{/t}</a>
    {/if}

    {if $current=='shortlog' || !$commit}
        <span class="{if !$commit}inactive{else}active{/if}">{t}Short log{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}refs/heads/{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}Short log{/t}</a>
    {/if}

    {if $current=='branchlog' || !$commit}
        <span class="{if !$commit}inactive{else}active{/if}">{t}Branch log{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchlog{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}Branch log{/t}</a>
    {/if}

    {if $current=='log' || !$commit}
        <span class="{if !$commit}inactive{else}active{/if}">{t}Log{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}refs/heads/{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}Log{/t}</a>
    {/if}

    {if $current=='commit' || !$commit || $branch}
        <span class="{if !$commit || $branch}inactive{else}active{/if}">{t}Commit{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$commit->GetHash()}&amp;retbranch={if $retbranch}{$retbranch}&amp;z={/if}{if $branch_name}{$branch_name}{/if}">{t}Commit{/t}</a>
    {/if}

    {if $current=='commitdiff' || !$commit || $branch}
        <span class="{if !$commit || $branch}inactive{else}active{/if}">{t}Commitdiff{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}&amp;retbranch={if $retbranch}{$retbranch}&amp;z={/if}{if $branch_name}{$branch_name}{/if}">{t}Commitdiff{/t}</a>
    {/if}

    {if $current=='tree' || !$commit}
        <span class="{if !$commit}inactive{else}active{/if}">{t}Tree{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree{if $treecommit}&amp;hb={$treecommit->GetHash()}{/if}{if $tree}&amp;h={$tree->GetHash()}{/if}">{t}Tree{/t}</a>
    {/if}

    {if ($current=='shortlog' || $current == 'branchlog') && $branch_name}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch_name}">{t}Branchdiff{/t}</a>
    {/if}

    {if $current=='review'}
        <span class="active">{t}Reviews{/t}</span>
    {else}
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=reviews">{t}Reviews{/t}</a>
    {/if}

    {if $retbranch}
        Current branch {$retbranch}
    {/if}
</div>