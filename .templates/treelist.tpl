{*
 * Tree list
 *
 * Tree filelist template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

{foreach from=$tree->GetContents() item=treeitem}
    {if $treeitem instanceof GitPHP_Blob}
      <tr>
          <td class="list fileName">
              <span class="expander"></span>
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="list">{$treeitem->GetName()}</a>
          </td>
          <td class="filesize">
            {$treeitem->GetSize()}
          </td>
          <td class="link">
            <div class="actions">
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}Blob{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}History{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$treeitem->GetHash()}&amp;f={$treeitem->GetPath()}">{t}Plain{/t}</a>
            </div>
          </td>
      </tr>
    {elseif $treeitem instanceof GitPHP_Tree}
      <tr>
          <td class="list folderName">
              <span class="expander" data-expand-url="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}"></span>
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="treeLink">{$treeitem->GetName()}</a>
          </td>
          <td class="filesize">
          </td>
          <td class="link">
            <div class="actions">
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}Tree{/t}</a>
                <a class="simple-button" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="snapshotTip">{t}Snapshot{/t}</a>
            </div>
          </td>
      </tr>
    {/if}
{/foreach}
