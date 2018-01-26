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
    <div class="git-tabs__items">
        <a class="git-tabs__item {if $current=='summary'}is-active{/if}"
           href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary">
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 4h-4.18A3.01 3.01 0 0 0 12 2c-1.3 0-2.4.84-2.82 2H5a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm-7 0a1 1 0 0 1 1 1 1 1 0 0 1-1 1 1 1 0 0 1-1-1 1 1 0 0 1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V8h10v2z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Summary{/t}
            </span>
        </a>

        <a class="git-tabs__item {if $current=='shortlog' || !$commit}{if !$commit}is-inactive{else}is-active{/if}{/if}"
           href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}refs/heads/{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Short log{/t}
            </span>
        </a>

        <a class="git-tabs__item {if $current=='branchlog' || !$commit}{if !$commit}is-inactive{else}is-active{/if}{/if}"
           href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchlog{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 15h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V9H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 9v2h14V9H7zM3 7h2V5H3v2zm4-2v2h14V5H7z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Branch log{/t}
            </span>
        </a>

        <a class="git-tabs__item {if $current=='log' || !$commit}{if !$commit}is-inactive{else}is-active{/if}{/if}"
           href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log{if $logcommit}&amp;h={if $retbranch}{$retbranch}&amp;z={/if}{if !$branch_name }{$logcommit->GetHash()}{else}refs/heads/{$branch_name}{/if}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 17h18v-2H3v2zm0 3h18v-1H3v1zm0-7h18v-3H3v3zm0-9v4h18V4H3z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Log{/t}
            </span>
        </a>

        <a class="git-tabs__item {if $current == 'commit' || !$commit || $branch}{if !$commit || $branch}is-inactive{else}is-active{/if}{/if}"
           {if $commit}
               href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$commit->GetHash()}&amp;retbranch={if $retbranch}{$retbranch}&amp;z={/if}{if $branch_name}{$branch_name}{/if}"
           {/if}
        >
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm0 14a6 6 0 1 1 .01-12.01A6 6 0 0 1 12 18zM1 11h3v2H1v-2zm19 0h3v2h-3v-2z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Commit{/t}
            </span>
        </a>

        <a class="git-tabs__item {if $current=='commitdiff' || !$commit || $branch}{if !$commit || $branch}is-inactive{else}is-active{/if}{/if}"
           {if $commit}
               href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}&amp;retbranch={if $retbranch}{$retbranch}&amp;z={/if}{if $branch_name}{$branch_name}{/if}"
           {/if}
        >
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm0 14a6 6 0 1 1 .01-12.01A6 6 0 0 1 15 18z"/>
                    <path d="M3 11.74a6 6 0 0 1 4-5.65V4a8 8 0 0 0 0 15.48v-2.09a6 6 0 0 1-4-5.65z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Commitdiff{/t}
            </span>
        </a>

        <a class="git-tabs__item {if $current=='tree' || !$commit}{if !$commit}is-inactive{else}is-active{/if}{/if}" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree{if $treecommit}&amp;hb={$treecommit->GetHash()}{/if}{if $tree}&amp;h={$tree->GetHash()}{/if}">
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 6h-8l-2-2H4a2 2 0 0 0-1.99 2L2 18c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2zm0 12H4V8h16v10z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Tree{/t}
            </span>
        </a>

        {if ($current=='shortlog' || $current == 'branchlog') && $branch_name}
            <a class="git-tabs__item"
               href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch_name}">
                <span class="git-tabs__icon">
                    <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.59 13h-7.14L4.7 18.7l-1.42-1.4L8.6 12l-5.3-5.3 1.4-1.42L10.43 11h7.17l-2.3-2.3 1.42-1.4 4.7 4.7-4.7 4.7-1.42-1.4 2.3-2.3z"/>
                    </svg>
                </span>
                <span class="git-tabs__text">
                    {t}Branchdiff{/t}
                </span>
            </a>
        {/if}

        <a class="git-tabs__item {if $current == 'review'}is-active{/if}"
           href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=reviews">
            <span class="git-tabs__icon">
                <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm0 14H6l-2 2V4h16v12z"/>
                </svg>
            </span>
            <span class="git-tabs__text">
                {t}Reviews{/t}
            </span>
        </a>
    </div>

    {if $retbranch}
        <div class="git-tabs__branch">
            Current branch <b>{$retbranch}</b>
        </div>
    {/if}

</div>
