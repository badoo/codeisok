<div class="title">
    new gitosis user
</div>
<div id="gitosisuser">
    <form action="" method="post">
        <ul>
        {foreach from=$form_errors item=form_error}
            <li>{$form_error}</li>
        {/foreach}
        </ul>
        <table cellspacing="0">
            <tbody>
                <tr>
                    <td>username: *</td>
                    <td><input type="text" name="username" class="text" value="{$edit_user.username|htmlspecialchars}"
                               {if $edit_user.id}readonly=""{/if} /></td>
                </tr>
                <tr>
                    <td>email:</td>
                    <td><input type="text" name="email" class="text" value="{$edit_user.email|htmlspecialchars}"/></td>
                </tr>
                <tr>
                    <td>public ssh key: *</td>
                    <td><textarea name="public_key">{$edit_user.public_key|htmlspecialchars}</textarea></td>
                </tr>
                <tr>
                    <td>comment:</td>
                    <td><textarea name="comment">{$edit_user.comment|htmlspecialchars}</textarea></td>
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
    gitosis users
</div>
<table cellspacing="0" width="100%">
    <tbody>
        <tr class="list_header">
            <th>username</th>
            <th>email</th>
            <th>actions</th>
            <th>comment</th>
            <th>created</th>
            <th>updated</th>
        </tr>
        {foreach from=$users item=user}
            <tr class="{cycle values="light,dark"}">
                <td><a href="/?a=gitosis&section=access&user_id={$user.id}">{$user.username|htmlspecialchars}</a></td>
                <td>{$user.email|htmlspecialchars}</td>
                <td>
                    <a href="/?a=gitosis&section=users&id={$user.id}">Edit</a> |
                    <a href="/?a=gitosis&section=users&id={$user.id}&delete=1"
                       onclick="return confirm('Are you really want deleting {$user.username}');">Delete</a>
                </td>
                <td>{$user.comment|htmlspecialchars|nl2br}</td>
                <td>{$user.created}</td>
                <td>{$user.updated}</td>
            </tr>
        {/foreach}
    </tbody>
</table>
