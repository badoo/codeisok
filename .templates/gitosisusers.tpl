<div id="create">
<div class="title">
    <strong>
    {if empty($edit_user)}
    Create new user
    {else}
    Edit user
    {/if}
    </strong>
</div>
<div id="gitosisuser">
    <form action="" method="post" id="createform">
        <ul>
        {foreach from=$form_errors item=form_error}
            <li>{$form_error}</li>
        {/foreach}
        </ul>
        <table class="git-admin-table">
            <tbody>
                <tr>
                    <td class="bold">Username: *</td>
                    <td><input type="text" name="username" class="text-input" value="{$edit_user.username|htmlspecialchars}"
                               {if $edit_user.id}readonly=""{/if} /></td>
                </tr>
                <tr>
                    <td class="bold">Email:</td>
                    <td><input type="text" name="email" class="text-input" value="{$edit_user.email|htmlspecialchars}"/></td>
                </tr>
                <tr>
                    <td class="bold">Public ssh key: *</td>
                    <td><textarea name="public_key" class="text-input">{$edit_user.public_key|htmlspecialchars}</textarea></td>
                </tr>
                <tr>
                    <td class="bold">Access mode</td>
                    <td>
                        {foreach name="access_modes" from=$access_modes item=access_mode}
                            <label><input type="radio" {if ($smarty.foreach.access_modes.first && !$edit_user) || ($edit_user && $access_mode == $edit_user.access_mode)}checked=""{/if} name="access_mode" value="{$access_mode}"> {$access_mode}</label>
                        {/foreach}
                    </td>
                </tr>
                <tr>
                    <td class="bold">Comment</td>
                    <td><textarea name="comment" class="text-input">{$edit_user.comment|htmlspecialchars}</textarea></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <a class="simple-button" href="#" onclick="document.getElementById('createform').submit();">Save</a>
                        <a class="simple-button" href="/?a=gitosis&section=users">Cancel</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>
</div>
<div class="title">
    <strong>Users</strong>
</div>
<table class="git-table">
    <tbody>
        <tr class="list_header">
            <th>Username</th>
            <th>Email</th>
            <th>Actions</th>
            <th>Comment</th>
            <th>Created</th>
            <th>Updated</th>
        </tr>
        {foreach from=$users item=user}
            <tr class="{cycle values="light,dark"}">
                <td><a href="/?a=gitosis&section=access&user_id={$user.id}">{$user.username|htmlspecialchars}</a></td>
                <td>{$user.email|htmlspecialchars}</td>
                <td>
                    <a class="simple-button" href="/?a=gitosis&section=users&id={$user.id}">Edit</a>
                    <a class="simple-button" href="/?a=gitosis&section=users&id={$user.id}&delete=1"
                       onclick="return confirm('Are you really want deleting {$user.username}');">Delete</a>
                </td>
                <td>{$user.comment|htmlspecialchars|nl2br}</td>
                <td>{$user.created}</td>
                <td>{$user.updated}</td>
            </tr>
        {/foreach}
    </tbody>
</table>
