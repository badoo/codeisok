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
          <td class="filesize">{$treeitem->GetSize()}</td>
          <td class="link">
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}blob{/t}</a> |
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}history{/t}</a> |
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$treeitem->GetHash()}&amp;f={$treeitem->GetPath()}">{t}plain{/t}</a>
          </td>
      </tr>
    {elseif $treeitem instanceof GitPHP_Tree}
      <tr>
          <td class="list folderName">
              <span class="expander" data-expand-url="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}"></span>
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="treeLink">{$treeitem->GetName()}</a>
          </td>
          <td class="filesize"></td>
          <td class="link">
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}tree{/t}</a> |
              <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="snapshotTip">{t}snapshot{/t}</a>
          </td>
      </tr>
    {/if}
{/foreach}
