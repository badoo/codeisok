<div class="title">
    <strong>
    {if empty($edit_project)}
    Create new repository
    {else}
    Edit repository
    {/if}
    </strong>
</div>
<div id="gitosisrepository">
    <form action="" method="post" id="createform">
        <ul>
        {foreach from=$form_errors item=form_error}
            <li>{$form_error}</li>
        {/foreach}
        </ul>
        <table class="git-admin-table">
            <tbody>
            <tr>
                <td class="bold">Project: *</td>
                <td><input type="text" name="project" class="text-input" value="{$edit_project.project|htmlspecialchars}" {if $edit_project.id}readonly=""{/if} /></td>
            </tr>
            <tr>
                <td class="bold">Description</td>
                <td><input type="text" name="description" class="text-input" value="{$edit_project.description|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td class="bold">Category</td>
                <td><input type="text" name="category" class="text-input" value="{$edit_project.category|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td class="bold">Notify email</td>
                <td><input type="text" name="notify_email" class="text-input" value="{$edit_project.notify_email|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td class="bold">Restricted access</td>
                <td>
                    {foreach name="restricted" from=$restricted item=restricted_mode}
                        <label><input type="radio" {if ($smarty.foreach.restricted.first && !$edit_project) || ($edit_project && $restricted_mode == $edit_project.restricted)}checked=""{/if} name="restricted" value="{$restricted_mode}"> {$restricted_mode}</label>
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td class="bold">Owner(s)</td>
                <td><input type="text" name="owner" class="text-input" value="{$edit_project.owner|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td class="bold">Display</td>
                <td>
                    {foreach name="displays" from=$displays item=display}
                        <label><input type="radio" {if ($smarty.foreach.displays.first && !$edit_project) || ($edit_project && $display == $edit_project.display)}checked=""{/if} name="display" value="{$display}"> {$display}</label>
                        {if $display == 'Yes'}<sup>web server user must have access for repository directory</sup>{/if}
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <a class="simple-button" href="#" onclick="document.getElementById('createform').submit();">Save</a>
                    <a class="simple-button" href="/?a=gitosis&section=repositories">Cancel</a>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>
<div class="title">
    <strong>Repositories</strong>
</div>
<table class="git-table">
    <tbody>
    <tr class="list_header">
        <th>Project</th>
        <th>Actions</th>
        <th>Description</th>
        <th>Category</th>
        <th>Notify email</th>
        <th>Display</th>
        <th>Created</th>
        <th>Updated</th>
    </tr>
    {foreach from=$projects item=project}
    <tr class="{cycle values="light,dark"}">
        <td>
            <a href="/?a=gitosis&section=access&scope=repo&project_id={$project.id}">{$project.project|htmlspecialchars}</a>
        </td>
        <td><a class="simple-button" href="/?a=gitosis&section=repositories&id={$project.id}">Edit</a></td>
        <td>{$project.description|htmlspecialchars}</td>
        <td>{$project.category|htmlspecialchars}</td>
        <td>{$project.notify_email|htmlspecialchars}</td>
        <td>{$project.display}</td>
        <td>{$project.created}</td>
        <td>{$project.updated}</td>
    </tr>
    {/foreach}
    </tbody>
</table>
