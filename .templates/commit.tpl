{*
 *  commit.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

 <div class="page_nav">
   {include file='nav.tpl' logcommit=$commit treecommit=$commit current='commit'}
 </div>

{if $commit->GetParent()}
 	{include file='title.tpl' titlecommit=$commit target='commitdiff' hasPageSearch=true}
{else}
	{include file='title.tpl' titlecommit=$commit titletree=$tree target='tree' hasPageSearch=true}
{/if}

{* Commit data *}
<table class="git-table">
    <tr>
        <td>{t}Author{/t}</td>
        <td>{$commit->GetAuthorName()}</td>
    </tr>

    <tr>
        <td></td>
        <td> {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}
        {assign var=hourlocal value=$commit->GetAuthorLocalEpoch()|date_format:"%H"}
        {if $hourlocal < 6}
        (<span class="latenight">{$commit->GetAuthorLocalEpoch()|date_format:"%R"}</span> {$commit->GetAuthorTimezone()})</td>
        {else}
        ({$commit->GetAuthorLocalEpoch()|date_format:"%R"} {$commit->GetAuthorTimezone()})</td>
        {/if}
    </tr>

    <tr>
        <td>{t}Committer{/t}</td>
        <td>{$commit->GetCommitterName()}</td>
    </tr>

    <tr>
        <td></td>
        <td>{$commit->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} ({$commit->GetCommitterLocalEpoch()|date_format:"%R"} {$commit->GetCommitterTimezone()})</td>
    </tr>

    <tr>
        <td>{t}Commit{/t}</td>
        <td>{$commit->GetHash()}</td>
    </tr>

    <tr>
        <td>{t}Tree{/t}</td>
        <td>
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$tree->GetHash()}&amp;hb={$commit->GetHash()}" class="list">{$tree->GetHash()}</a>
            <div class="actions">
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$tree->GetHash()}&amp;hb={$commit->GetHash()}">{t}Tree{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$commit->GetHash()}" class="snapshotTip">{t}Snapshot{/t}</a>
            </div>
        </td>
    </tr>

    {foreach from=$commit->GetParents() item=par}
        <tr>
            <td>{t}Parent{/t}</td>
            <td>
            <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$par->GetHash()}" class="list">{$par->GetHash()}</a>
            <div class="actions">
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$par->GetHash()}">{t}Commit{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}&amp;hp={$par->GetHash()}">{t}Commitdiff{/t}</a>
            </div>
            </td>
        </tr>
    {/foreach}
</table>

<div class="title">
    {assign var=bugpattern value=$project->GetBugPattern()}
    {assign var=bugurl value=$project->GetBugUrl()}

    <strong>
        {foreach from=$commit->GetComment() item=line}
            {$line|htmlspecialchars|buglink:$bugpattern:$bugurl}<br />
        {/foreach}

        {if $treediff->Count() > 10}
            {t count=$treediff->Count() 1=$treediff->Count() plural="%1 files changed:"}%1 file changed:{/t}
        {/if}
    </strong>
</div>

<table class="git-table">
    {* Loop and show files changed *}
    {foreach from=$treediff item=diffline}
        <tr class="{cycle values="light,dark"}">

        {if $diffline->GetStatus() == "A"}
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetToHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetFromFile()}" class="list">
                    {$diffline->GetFromFile()}
                </a>
            </td>
            <td>
                <span class="newfile">
                    {assign var=localtotype value=$diffline->GetToFileType(1)}
                    [
                    {if $diffline->ToFileIsRegular()}
                    {assign var=tomode value=$diffline->GetToModeShort()}
                    {t 1=$localtotype 2=$tomode}new %1 with mode %2{/t}
                    {else}
                    {t 1=$localtotype}new %1{/t}
                    {/if}
                    ]
                </span>

                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetToHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetFromFile()}">{t}Blob{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$diffline->GetToHash()}&amp;f={$diffline->GetFromFile()}">{t}Plain{/t}</a>
                </div>
            </td>

        {elseif $diffline->GetStatus() == "D"}
            {assign var=parent value=$commit->GetParent()}
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetFromHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetFromFile()}" class="list">
                    {$diffline->GetFromFile()}
                </a>
            </td>
            <td>
                <span class="deletedfile">
                    {assign var=localfromtype value=$diffline->GetFromFileType(1)}
                    [ {t 1=$localfromtype}deleted %1{/t} ]
                </span>

                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetFromHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetFromFile()}">{t}Blob{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$parent->GetHash()}&amp;f={$diffline->GetFromFile()}">{t}History{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$diffline->GetFromHash()}&amp;f={$diffline->GetFromFile()}">{t}Plain{/t}</a>
                </div>
            </td>

        {elseif $diffline->GetStatus() == "M" || $diffline->GetStatus() == "T"}
            <td>
                {if $diffline->GetToHash() != $diffline->GetFromHash()}
                    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$diffline->GetToHash()}&amp;hp={$diffline->GetFromHash()}&amp;hb={$par->GetHash()}&amp;f={$diffline->GetToFile()}" class="list">
                        {$diffline->GetToFile()}
                    </a>
                {else}
                    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetToHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetToFile()}" class="list">
                        {$diffline->GetToFile()}
                    </a>
                {/if}
            </td>
            <td>
                {if $diffline->GetFromMode() != $diffline->GetToMode()}
                    <span class="changedfile">[
                        {if $diffline->FileTypeChanged()}
                            {assign var=localfromtype value=$diffline->GetFromFileType(1)}
                            {assign var=localtotype value=$diffline->GetToFileType(1)}
                            {if $diffline->FileModeChanged()}
                                {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
                                    {assign var=frommode value=$diffline->GetFromModeShort()}
                                    {assign var=tomode value=$diffline->GetToModeShort()}
                                    {t 1=$localfromtype 2=$localtotype 3=$frommode 4=$tomode}changed from %1 to %2 mode: %3 -> %4{/t}
                                {elseif $diffline->ToFileIsRegular()}
                                    {assign var=tomode value=$diffline->GetToModeShort()}
                                    {t 1=$localfromtype 2=$localtotype 3=$tomode}changed from %1 to %2 mode: %3{/t}
                                {else}
                                    {t 1=$localfromtype 2=$localtotype}changed from %1 to %2{/t}
                                {/if}
                            {else}
                                {t 1=$localfromtype 2=$localtotype}changed from %1 to %2{/t}
                            {/if}
                        {else}
                            {if $diffline->FileModeChanged()}
                                {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
                                    {assign var=frommode value=$diffline->GetFromModeShort()}
                                    {assign var=tomode value=$diffline->GetToModeShort()}
                                    {t 1=$frommode 2=$tomode}changed mode: %1 -> %2{/t}
                                {elseif $diffline->ToFileIsRegular()}
                                    {assign var=tomode value=$diffline->GetToModeShort()}
                                    {t 1=$tomode}changed mode: %1{/t}
                                {else}
                                    {t}Changed{/t}
                                {/if}
                            {else}
                                {t}Changed{/t}
                            {/if}
                        {/if}
                    ]</span>
                {/if}

                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetToHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetToFile()}">{t}Blob{/t}</a>
                    {if $diffline->GetToHash() != $diffline->GetFromHash()}
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$diffline->GetToHash()}&amp;hp={$diffline->GetFromHash()}&amp;hb={$par->GetHash()}&amp;f={$diffline->GetToFile()}">{t}Diff{/t}</a>
                    {/if}
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$diffline->GetFromFile()}">{t}History{/t}</a>
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$diffline->GetToHash()}&amp;f={$diffline->GetToFile()}">{t}Plain{/t}</a>
                </div>
            </td>
        {elseif $diffline->GetStatus() == "R"}
            <td>
                <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetToHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetToFile()}" class="list">
                    {$diffline->GetToFile()}
                </a>
            </td>
            <td>
                <span class="movedfile">
                    {capture assign=fromfilelink}
                        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetFromHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetFromFile()}" class="list">{$diffline->GetFromFile()}</a>
                    {/capture}
                    [
                        {assign var=similarity value=$diffline->GetSimilarity()}
                        {if $diffline->GetFromMode() != $diffline->GetToMode()}
                            {assign var=tomode value=$diffline->GetToModeShort()}
                            {t escape=no 1=$fromfilelink 2=$similarity 3=$tomode}moved from %1 with %2%% similarity, mode: %3{/t}
                        {else}
                            {t escape=no 1=$fromfilelink 2=$similarity}moved from %1 with %2%% similarity{/t}
                        {/if}
                    ]
                </span>

                <div class="actions">
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$diffline->GetToHash()}&amp;hb={$commit->GetHash()}&amp;f={$diffline->GetToFile()}">{t}Blob{/t}</a>
                    {if $diffline->GetToHash() != $diffline->GetFromHash()}
                        <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$diffline->GetToHash()}&amp;hp={$diffline->GetFromHash()}&amp;hb={$par->GetHash()}&amp;f={$diffline->GetToFile()}">{t}Diff{/t}</a>
                    {/if}
                    <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$diffline->GetToHash()}&amp;f={$diffline->GetToFile()}">{t}Plain{/t}</a>
                </div>
            </td>
        {/if}

        </tr>
    {/foreach}
</table>

{include file='footer.tpl'}

