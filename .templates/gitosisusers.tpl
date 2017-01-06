<div class="title">
    New gitosis user
</div>
<div id="gitosisuser">
    <form action="" method="post">
        <ul>
        {foreach from=$form_errors item=form_error}
            <li>{$form_error}</li>
        {/foreach}
        </ul>
        <table>
            <tbody>
                <tr>
                    <td>Username *</td>
                    <td><input type="text" name="username" class="text" value="{$edit_user.username|htmlspecialchars}"
                               {if $edit_user.id}readonly=""{/if} /></td>
                </tr>
                <tr>
                    <td>Public key *</td>
                    <td><textarea name="public_key">{$edit_user.public_key|htmlspecialchars}</textarea></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Save user">
                        <a href="/?a=gitosis&section=users">Cancel</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>
<div class="title">
    Gitosis users
</div>
<table cellspacing="0" width="100%">
    <tbody>
        <tr>
            <th>Username</th>
            <th>Actions</th>
            <th>Created</th>
            <th>Updated</th>
        </tr>
        {foreach from=$users item=user}
            <tr class="{cycle values="light,dark"}">
                <td><a href="/?a=gitosis&section=access&user_id={$user.id}">{$user.username|htmlspecialchars}</a></td>
                <td>
                    <a href="/?a=gitosis&section=users&id={$user.id}">Edit</a> |
                    <a href="/?a=gitosis&section=users&id={$user.id}&delete=1"
                       onclick="return confirm('Are you really want deleting {$user.username}');">Delete</a>
                </td>
                <td>{$user.created}</td>
                <td>{$user.updated}</td>
            </tr>
        {/foreach}
    </tbody>
</table>
