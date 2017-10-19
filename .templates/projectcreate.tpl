{include file='header.tpl'}

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
                <td>project *</td>
                <td><input type="text" name="project" class="text" placeholder="project.git" value="{$edit_project.project|htmlspecialchars}" {if $edit_project.id}readonly=""{/if} /></td>
            </tr>
            <tr>
                <td>description</td>
                <td><input type="text" name="description" class="text" placeholder="Some meaningful text" value="{$edit_project.description|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td>category</td>
                <td><input type="text" name="category" class="text" placeholder="PHP" value="{$edit_project.category|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td>notify email</td>
                <td><input type="text" name="notify_email" class="text" placeholder="your-team-email" value="{$edit_project.notify_email|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td>restricted access</td>
                <td>
                    {foreach name="restricted" from=$restricted item=restricted_mode}
                        <label><input type="radio" {if ($smarty.foreach.restricted.first && !$edit_project) || ($edit_project && $restricted_mode == $edit_project.restricted)}checked=""{/if} name="restricted" value="{$restricted_mode}"> {$restricted_mode}</label>
                        {if $display == 'Yes'}<sup>web server user must have access for repository directory</sup>{/if}
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td>display</td>
                <td>
                    {foreach name="displays" from=$displays item=display}
                        <label><input type="radio" {if ($smarty.foreach.displays.first && !$edit_project) || ($edit_project && $display == $edit_project.display)}checked=""{/if} name="display" value="{$display}"> {$display}</label>
                        {if $display == 'Yes'}<sup>web server user must have access for repository directory</sup>{/if}
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="color: red;">Your project will be created in one minute after form submission. You should be able to clone it once it appears in projects list</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="color: red;">Restricted access is a special mark for repositories with strict controlled access.</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span style="color: red;">It's not allowed to change project's name upon creation</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="submit" value="Save repository">
                    <a href="/">Cancel</a>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>