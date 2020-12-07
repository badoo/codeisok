{*
 * Path
 *
 * Path template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

{if $pathobject}
	<div class="title">
		{assign var=pathobjectcommit value=$pathobject->GetCommit()}
		{assign var=pathobjecttree value=$pathobjectcommit->GetTree()}
		<a href="/?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$pathobjectcommit->GetHash()}&amp;h={$pathobjecttree->GetHash()}"><strong>[{$project->GetProject()}]</strong></a> /
		{foreach from=$pathobject->GetPathTree() item=pathtreepiece}
			<a href="/?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$pathobjectcommit->GetHash()}&amp;h={$pathtreepiece->GetHash()}&amp;f={$pathtreepiece->GetPath()}"><strong>{$pathtreepiece->GetName()}</strong></a> /
		{/foreach}
		{if $pathobject instanceof \GitPHP\Git\Blob}
			{if $target == 'blobplain'}
				<a href="/?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$pathobject->GetHash()}&amp;hb={$pathobjectcommit->GetHash()}&amp;f={$pathobject->GetPath()}"><strong>{$pathobject->GetName()}</strong></a>
			{elseif $target == 'blob'}
				<a href="/?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$pathobject->GetHash()}&amp;hb={$pathobjectcommit->GetHash()}&amp;f={$pathobject->GetPath()}"><strong>{$pathobject->GetName()}</strong></a>
			{else}
				<strong>{$pathobject->GetName()}</strong>
			{/if}
		{elseif $pathobject->GetName()}
			{if $target == 'tree'}
				<a href="/?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$pathobjectcommit->GetHash()}&amp;h={$pathobject->GetHash()}&amp;f={$pathobject->GetPath()}"><strong>{$pathobject->GetName()}</strong></a> /
			{else}
				<strong>{$pathobject->GetName()}</strong> /
			{/if}
		{/if}
	</div>
{/if}
