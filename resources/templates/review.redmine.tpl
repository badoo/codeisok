{foreach from=$DIFF_OBJS item=DIFF_OBJ}
{foreach from=$DIFF_OBJ.HEADER item=HEAD}
{$HEAD.header}
{/foreach}
{foreach from=$DIFF_OBJ.TOP_COMMENT item=TC}
</pre>
>*{$TC.date} {$TC.author}:* _{$TC.comment}_
<pre>
{/foreach}
{foreach from=$DIFF_OBJ.LINE item=L}
{if isset($L.SEPARATOR)}
{foreach from=$L.SEPARATOR item=S}
</pre>
----
<pre>
{/foreach}
{/if}
{$L.line_numbers}{$L.line}
{foreach from=$L.COMMENT item=C}
</pre>
>*{$C.date} {$C.author}:* _{$C.comment}_
<pre>
{/foreach}
{/foreach}
{/foreach}
