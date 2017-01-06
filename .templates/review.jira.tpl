{foreach from=$DIFF_OBJS item=DIFF_OBJ}
{foreach from=$DIFF_OBJ.HEADER item=HEAD}
{$HEAD.header}
{/foreach}
{foreach from=$DIFF_OBJ.TOP_COMMENT item=TC}
{ldelim}code{rdelim}{ldelim}quote{rdelim}{$TC.date} {$TC.author}: {$TC.comment}{ldelim}quote{rdelim}{ldelim}code{rdelim}
{/foreach}
{foreach from=$DIFF_OBJ.LINE item=L}
{if isset($L.SEPARATOR)}
{foreach from=$L.SEPARATOR item=S}
{ldelim}code{rdelim}
----
{ldelim}code{rdelim}
{/foreach}
{/if}
{$L.line_numbers}{$L.line}
{foreach from=$L.COMMENT item=C}
{ldelim}code{rdelim}{ldelim}quote{rdelim}{$C.date} {$C.author}: {$C.comment}{ldelim}quote{rdelim}{ldelim}code{rdelim}
{/foreach}
{/foreach}
{/foreach}
