{foreach from=$DIFF_OBJS item=DIFF_OBJ}
    {foreach from=$DIFF_OBJ.HEADER item=HEAD}
        <div style="color:#006699;white-space:pre;font-weight:700;">{$HEAD.header}</div>
    {/foreach}

    {foreach from=$DIFF_OBJ.TOP_COMMENT item=TC}
        <div style="color:blue;background:#EEEEEE;">
        <span style="color:#777777;font-size:11px;margin-left:20px;text-decoration:underline;">{$TC.date} {$TC.author}:</span>
        {$TC.comment}
        </div>
    {/foreach}

    {foreach from=$DIFF_OBJ.LINE item=L}
        {if isset($L.SEPARATOR)}
            {foreach from=$L.SEPARATOR item=S}
            <hr/>
            {/foreach}
        {/if}
        <div style="color: {$L.color};white-space:pre;">{$L.line_numbers}{$L.line}</div>
        {foreach from=$L.COMMENT item=C}
            <div style="color:blue;background:#EEEEEE;">
                <span style="color:#777777;font-size:11px;margin-left:20px;text-decoration:underline;">{$C.date} {$C.author}:</span>
                {$C.comment}
            </div>
        {/foreach}
    {/foreach}
{/foreach}
