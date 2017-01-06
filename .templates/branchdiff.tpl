{include file='header.tpl'}
{if $commit}
<input type="hidden" id="review_hash_head" value="{$commit->GetHash()}" />
<input type="hidden" id="review_hash_base" value="{$branchdiff->GetFromHash()}" />
{/if}

{* Nav *}
 <div class="page_nav">
   {if $commit}
   {assign var=tree value=$commit->GetTree()}
   {/if}
   {include file='nav.tpl' current='branchdiff' logcommit=$commit treecommit=$commit}
   <br />
   {if $sidebyside}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}&amp;o=unified">{t}unified{/t}</a>
   {else}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}&amp;o=sidebyside">{t}side by side{/t}</a>
   {/if}
   | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff_plain&amp;branch={$branch}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl'}

 <div class="page_body">
   {if $branchdiff && !$sidebyside}
       {if $extensions}
           <div class="file_filter">
               Filter:
               <span class="spacer"></span>
               {foreach from=$statuses item=st}
                   <span class="status selected" data-status="{$st|lower}">{$st}</span>
               {/foreach}
               <span class="spacer"></span>
               {foreach from=$extensions item=ext}
                   <span class="extension selected" data-extension="{$ext}">{$ext}</span>
               {/foreach}
               <span class="spacer"></span>
               {foreach from=$folders item=folder}
                   <span class="folder selected" data-folder="{$folder|lower}">{$folder}</span>
               {/foreach}
               <span class="hint">(+Shift for single select)</span>
           </div>
       {/if}

       <table style="float: left; border: 0; padding: 0; margin: 0;">
       {if $branchdiff->hasHidden()}
       <tr>
           <td>
               <a class="warning_hidden_files" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=branchdiff&amp;branch={$branch}&amp;show_hidden=1">Some files were hidden. Click here to show them.</a>
           </td>
       </tr>
       {/if}
       {foreach from=$branchdiff item=filediff}
       <tr class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower}">
         <td>
             {$filediff->getStatus()}&nbsp;&nbsp;&nbsp;&nbsp;<a href="#{$filediff->getToFile()}">{$filediff->getToFile()}</a>
         </td>
         <td name="files_index_{$filediff->getToFile()}"></td>
       </tr>
       {/foreach}
       </table>
       <br style="clear: both;" />
       <br style="clear: both;" />
   {else}
      <script type="text/javascript" src="/lib/mergely/codemirror.min.js"></script>
      <link type="text/css" rel="stylesheet" href="/lib/mergely/codemirror.css" />
      <script type="text/javascript" src="/lib/mergely/mergely.js"></script>
      <link type="text/css" rel="stylesheet" href="/lib/mergely/mergely.css" />
   {/if}

     {if $branchdiff && $sidebyside}
    <div class="commitDiffSBS">

     <div class="SBSTOC">
       <ul>
       <li class="listcount">
       {t count=$branchdiff->Count() 1=$branchdiff->Count() plural="%1 files changed"}%1 file changed{/t}</li>
       {foreach from=$branchdiff item=filediff}
       <li>
       <a href="#{$filediff->GetFromHash()}_{$filediff->GetToHash()}" 
       onclick="loadSBS('{$filediff->GetFromHash()}', '{$filediff->GetFromFile()}', '{$filediff->GetToHash()}', '{$filediff->GetToFile()}');"
       class="SBSTOCItem">
       {if $filediff->GetStatus() == 'A'}
         {if $filediff->GetToFile()}{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if} {t}(new){/t}
       {elseif $filediff->GetStatus() == 'D'}
         {if $filediff->GetFromFile()}{$filediff->GetFromFile()}{else}{$filediff->GetToFile()}{/if} {t}(deleted){/t}
       {elseif $filediff->GetStatus() == 'M'}
         {if $filediff->GetFromFile()}
	   {assign var=fromfilename value=$filediff->GetFromFile()}
	 {else}
	   {assign var=fromfilename value=$filediff->GetFromHash()}
	 {/if}
	 {if $filediff->GetToFile()}
	   {assign var=tofilename value=$filediff->GetToFile()}
	 {else}
	   {assign var=tofilename value=$filediff->GetToHash()}
	 {/if}
	 {$fromfilename}{if $fromfilename != $tofilename} -&gt; {$tofilename}{/if}
       {/if}
       </a>
       </li>
       {/foreach}
       </ul>
     </div>

     <div class="SBSContent">
   {/if}

   {* Diff each file changed *}
   {if $branchdiff}
   {if !$sidebyside}
      {foreach from=$branchdiff item=filediff}
        {if !$sidebyside}
          {assign var="diff" value=$filediff->GetDiff('', true, true)}
        {/if}
        <div class="filetype-{$filediff->getToFileExtension()} status-{$filediff->getStatus()|lower} folder-{$filediff->getToFileRootFolder()|lower} diffBlob{if $filediff->getDiffTooLarge()} suppressed{/if}" id="{$filediff->GetFromHash()}_{$filediff->GetToHash()}">
        <a name="{$filediff->getToFile()}"></a>
        <div class="diff_info">
          {if ($filediff->GetStatus() == 'D') || ($filediff->GetStatus() == 'M') || ($filediff->GetStatus() == 'R')}
          {assign var=localfromtype value=$filediff->GetFromFileType(1)}
          {* $localfromtype}:{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if *}
           {$localfromtype}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$filediff->GetFromHash()}&amp;hb={$commit->GetHash()}{if $filediff->GetFromFile()}&amp;f={$filediff->GetFromFile()}{/if}">{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if}</a>
          {if $filediff->GetStatus() == 'D'}
            {t}(deleted){/t}
          {/if}
        {/if}

        {if $filediff->GetStatus() == 'M' || $filediff->GetStatus() == 'R'}
          -&gt;
        {/if}

        {if ($filediff->GetStatus() == 'A') || ($filediff->GetStatus() == 'M') || ($filediff->GetStatus() == 'R')}
          {assign var=localtotype value=$filediff->GetToFileType(1)}
          {* $localtotype}:{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if *}
          {$localtotype}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$filediff->GetToHash()}&amp;hb={$commit->GetHash()}{if $filediff->GetToFile()}&amp;f={$filediff->GetToFile()}{/if}">{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if}</a>

          {if $filediff->GetStatus() == 'A'}
            {t}(new){/t}
          {/if}
        {/if}
        </div>
        {include file='filediff.tpl' diff=$diff}
        </div>
      {/foreach}
   {/if}

   {if $sidebyside}
      {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
      </div>
      </div>
   {/if}

   {else}
   <div style='color:red'>Branch is not found</div>
   {/if}
   {if $sexy}
       {include file="sexy_highlighter.tpl"}
   {/if}

   {if $sidebyside}
     </div>
     <div class="SBSFooter"></div>

    </div>
   {/if}


 </div>

 {include file='footer.tpl'}

