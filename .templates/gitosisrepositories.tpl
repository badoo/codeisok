<div class="title">
    New repository
</div>
<div id="gitosisrepository">
    <form action="" method="post">
        <ul>
        {foreach from=$form_errors item=form_error}
            <li>{$form_error}</li>
        {/foreach}
        </ul>
        <table>
            <tbody>
            <tr>
                <td>Project *</td>
                <td><input type="text" name="project" class="text" value="{$edit_project.project|htmlspecialchars}" {if $edit_project.id}readonly=""{/if} /></td>
            </tr>
            <tr>
                <td>Description</td>
                <td><input type="text" name="description" class="text" value="{$edit_project.description|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td>Category</td>
                <td><input type="text" name="category" class="text" value="{$edit_project.category|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td>Notify email</td>
                <td><input type="text" name="notify_email" class="text" value="{$edit_project.notify_email|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td>Display</td>
                <td>
                {foreach name="displays" from=$displays item=display}
                    <label><input type="radio" {if ($smarty.foreach.displays.first && !$edit_project) || ($edit_project && $display == $edit_project.display)}checked=""{/if} name="display" value="{$display}"> {$display}</label>
                    {if $display == 'Yes'}<sup>web server user must have access for repository directory</sup>{/if}
                {/foreach}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="submit" value="Save repository">
                    <a href="/?a=gitosis&section=repositories">Cancel</a>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>
<div class="title">
    Gitosis projects
</div>
<table cellspacing="0" width="100%">
    <tbody>
    <tr>
        <th>Project</th>
        <th>Description</th>
        <th>Category</th>
        <th>Notify email</th>
        <th>Display</th>
        <th>Created</th>
        <th>Updated</th>
        <th>Actions</th>
    </tr>
    {foreach from=$projects item=project}
    <tr class="{cycle values="light,dark"}">
        <td>
            <a href="/?a=gitosis&section=access&scope=repo&project_id={$project.id}">{$project.project|htmlspecialchars}</a>
        </td>
        <td>{$project.description|htmlspecialchars}</td>
        <td>{$project.category|htmlspecialchars}</td>
        <td>{$project.notify_email|htmlspecialchars}</td>
        <td>{$project.display}</td>
        <td>{$project.created}</td>
        <td>{$project.updated}</td>
        <td><a href="/?a=gitosis&section=repositories&id={$project.id}">Edit</a></td>
    </tr>
    {/foreach}
    </tbody>
</table>
