<div class="title">
    Users repositories access
    {if $scope == 'user'}
        by user | <a href="/?a=gitosis&section=access&scope=repo">by repository</a>
    {/if}
    {if $scope == 'repo'}
        <a href="/?a=gitosis&section=access&scope=user">by user</a> | by repository
    {/if}
</div>
<div id="gitosisaccess">
{if $scope == 'user'}
    <table cellspacing="0">
        <tr>
            <th>Username</th>
            <th>Project</th>
            <th>Mode</th>
            <th>Actions</th>
        </tr>
        {foreach from=$users item=user}
        <form method="post" action="">
            <input type="hidden" name="user_id" value="{$user.id}" />
            <tr class="{cycle values="light,dark"}" id="{$user.username}">
                <td>{$user.username}</td>
                <td>
                    <select name="projects_ids[]" multiple="" size="10">
                    {foreach from=$projects item=project}
                        <option value="{$project.id}">{$project.project}</option>
                    {/foreach}
                    </select>
                </td>
                <td nowrap>
                    <label><input type="radio" name="mode" value="writable" /> Writable</label>
                    <label><input type="radio" name="mode" value="readonly" /> Readonly</label>
                    <label><input type="radio" name="mode" value="" /> No</label>
                </td>
                <td>
                    <input type="submit" value="Grant access" />
                </td>
                <td>
                {foreach from=$access[$user.id] key=mode item=projects_ids}
                    <ol>
                        <b>{$mode|ucfirst}</b>:
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
    <table cellspacing="0">
        <tr>
            <th>Project</th>
            <th>User</th>
            <th>Mode</th>
            <th>Actions</th>
        </tr>
        {foreach from=$projects item=project}
        <form method="post" action="">
            <input type="hidden" name="project_id" value="{$project.id}" />
            <tr class="{cycle values="light,dark"}" id="{$project.project}">
                <td>
                    <a href="/?p={$project.project}&a=summary">{$project.project}</a>
                </td>
                <td>
                    <select name="user_ids[]" multiple="" size="10">
                    {foreach from=$users item=user}
                        <option value="{$user.id}">{$user.username}</option>
                    {/foreach}
                    </select>
                </td>
                <td nowrap>
                    <label><input type="radio" name="mode" value="writable" /> Writable</label>
                    <label><input type="radio" name="mode" value="readonly" /> Readonly</label>
                    <label><input type="radio" name="mode" value="" /> No</label>
                </td>
                <td>
                    <input type="submit" value="Grant access" />
                </td>
                <td>
                {foreach from=$access[$project.id] key=mode item=user_ids}
                    <ol>
                        <b>{$mode|ucfirst}</b>:
                    {foreach name="users" from=$user_ids item=user_id}
                        <li>
                            {$users[$user_id].username}
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
</div>
