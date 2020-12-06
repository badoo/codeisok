{foreach from=$branchdiff item=filediff}
{$filediff->GetDiff('', true, false, false)}
{/foreach}
