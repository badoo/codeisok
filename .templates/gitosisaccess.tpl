<div class="title">
    <strong>Users repositories access</strong>
    {if $scope == 'user'}
        by user | <a href="/?a=gitosis&section=access&scope=repo">by repository</a>
    {/if}
    {if $scope == 'repo'}
        <a href="/?a=gitosis&section=access&scope=user">by user</a> | by repository
    {/if}
</div>
<div id="gitosisaccess">
{if $scope == 'user'}
    <table class="git-table">
        <tr class="list_header">
            <th>Username</th>
            <th>Project</th>
            <th>Mode</th>
            <th>Actions</th>
            <th></th>
        </tr>
        {foreach from=$users item=user}
        <form method="post" action="" id="form_user_{$user.id}">
            <input type="hidden" name="user_id" value="{$user.id}" />
            <tr class="{cycle values="light,dark"}" id="{$user.username}">
                <td>
                    <p>{$user.username}</p>
                    {if $user.access_mode == 'everywhere' || $user.access_mode == 'everywhere-ro'}<small class="warning">only restricted repos shown</small>{/if}
                </td>
                <td>
                    <select name="projects_ids[]" multiple="" size="10" class='select-input'>
                    {if $user.access_mode == 'everywhere' || $user.access_mode == 'everywhere-ro'}
                        {foreach from=$restricted_projects item=project}
                            <option value="{$project.id}">{$project.project}</option>
                        {/foreach}
                    {else}
                        {foreach from=$projects item=project}
                            <option value="{$project.id}">{$project.project}</option>
                        {/foreach}
                    {/if}
                    </select>
                </td>
                <td nowrap>
                    <label><input type="radio" name="mode" value="writable" /> writable</label>
                    <label><input type="radio" name="mode" value="readonly" /> readonly</label>
                    <label><input type="radio" name="mode" value="" /> no</label>
                </td>
                <td>
                    <a class="simple-button" href="#" onclick="document.getElementById('form_user_{$user.id}').submit();">Grant access</a>
                </td>
                <td>
                {foreach from=$access[$user.id] key=mode item=projects_ids}
                    <ol>
                        <b>{$mode}</b>:
                    {foreach name="projects" from=$projects_ids item=project_id}
                        <li>
                            <a href="/?p={$projects[$project_id].project}&a=summary">{$projects[$project_id].project}</a>
                        </li>
                    {/foreach}
                    </ol>
                {/foreach}
                </td>
            </tr>
        </form>
        {/foreach}
    </table>
{/if}
{if $scope == 'repo'}
    <table class="git-table">
        <tr class="list_header">
            <th>Project</th>
            <th>User</th>
            <th>Mode</th>
            <th>Actions</th>
            <th></th>
        </tr>
        {foreach from=$projects item=project}
        <form method="post" action="" id="form_repo_{$project.id}">
            <input type="hidden" name="project_id" value="{$project.id}" />
            <tr class="{cycle values="light,dark"}" id="{$project.project}">
                <td>
                    <p><a href="/?p={$project.project}&a=summary">{$project.project}</a></p>
                    {if $project.restricted == 'Yes'}
                        <small class="warning">Restricted repo!</small>
                        {if $project.owner and $project.owners}
                            <br/><small class="warning">Owner(s):</small>
                            <ul class="owners-list">
                            {foreach from=$project.owners item=owner}
                                <li><a href="mailto:{$owner}">{$owner}</a></li>
                            {/foreach}
                            </ul>
                        {/if}
                    {/if}
                </td>
                <td>
                    <select name="user_ids[]" multiple="" size="10" class="select-input">
                    {foreach from=$users item=user}
                        <option value="{$user.id}">{$user.username}</option>
                    {/foreach}
                    </select>
                </td>
                <td nowrap>
                    <label><input type="radio" name="mode" value="writable" /> writable</label>
                    <label><input type="radio" name="mode" value="readonly" /> readonly</label>
                    <label><input type="radio" name="mode" value="" /> no</label>
                </td>
                <td>
                    <a class="simple-button" href="#" onclick="document.getElementById('form_repo_{$project.id}').submit();">Grant access</a>
                </td>
                <td>
                {if isset($access[$project.id])}
                    {foreach from=$access[$project.id] key=mode item=user_ids}
                        <ol>
                            <b>{$mode}</b>:
                        {foreach name="users" from=$user_ids item=user_id}
                            <li>
                                {$users[$user_id].username}
                            </li>
                        {/foreach}
                        </ol>
                    {/foreach}
                {/if}
                </td>
            </tr>
        </form>
        {/foreach}
    </table>
{/if}
</div>
