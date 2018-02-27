
{include file="header.tpl"}

<div class="page_nav">
    {include file='nav.tpl' commit=$head current='review'}
</div>


<table class="git-table">
    <thead>
        <tr class="list_header">
            <th>Review</th>
            <th>Ticket / Review Name</th>
            <th>Comments count</th>
            <th>Type</th>
            <th>Link</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$snapshots item=snapshot}
            <tr>
                <td>
                    {$snapshot.review_id}
                </td>
                <td>
                    {if $snapshot.ticket_url}
                        <a href="{$snapshot.ticket_url}">{$snapshot.ticket}</a>
                    {else}
                        {$snapshot.ticket}
                    {/if}
                </td>
                <td>
                    {$snapshot.count}
                </td>
                <td>
                    {$snapshot.review_type}
                </td>
                <td style="font-family: Menlo, Monaco, 'Courier New', monospace;">
                    <a href="{$snapshot.url}">{$snapshot.title}</a>
                </td>
            </tr>
        {/foreach}
        <tr>
            <td colspan='5'>
            {if $to_start_link}
                <a class="simple-button" href="{$to_start_link}">&larr; to start</a>
            {/if}
            {if $more_link}
                <a class="simple-button" href="{$more_link}">more &rarr;</a>
            {/if}
            </td>
        </tr>
    </tbody>
</table>

{include file="footer.tpl"}
