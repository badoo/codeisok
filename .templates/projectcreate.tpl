{include file='header.tpl'}

<div class="title">
   <strong>Create new repository</strong>
</div>
<div id="gitosisrepository">
    <form action="" method="post" id="createform">
        <ul>
            {foreach from=$form_errors item=form_error}
                <li>{$form_error}</li>
            {/foreach}
        </ul>
        <table class='git-table'>
            <tbody>
            <tr>
                <td class="bold">Project *</td>
                <td><input type="text" name="project" class="text-input" placeholder="project.git" value="{$edit_project.project|htmlspecialchars}" {if $edit_project.id}readonly=""{/if} /></td>
            </tr>
            <tr>
                <td class="bold">Description</td>
                <td><input type="text" name="description" class="text-input" placeholder="Some meaningful text" value="{$edit_project.description|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td class="bold">Category</td>
                <td><input type="text" name="category" class="text-input" placeholder="PHP" value="{$edit_project.category|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td class="bold">Notify email</td>
                <td><input type="text" name="notify_email" class="text-input" placeholder="your-team-email" value="{$edit_project.notify_email|htmlspecialchars}" /></td>
            </tr>
            <tr>
                <td class="bold">Restricted access</td>
                <td>
                    {foreach name="restricted" from=$restricted item=restricted_mode}
                        <label><input type="radio" {if ($smarty.foreach.restricted.first && !$edit_project) || ($edit_project && $restricted_mode == $edit_project.restricted)}checked=""{/if} name="restricted" value="{$restricted_mode}"> {$restricted_mode}</label>
                    {/foreach}
                    <sup>Restricted access is a special mark for repositories with strict controlled access</sup>
                </td>
            </tr>
            <tr>
                <td class="bold">Display</td>
                <td>
                    {foreach name="displays" from=$displays item=display}
                        <label><input type="radio" {if ($smarty.foreach.displays.first && !$edit_project) || ($edit_project && $display == $edit_project.display)}checked=""{/if} name="display" value="{$display}"> {$display}</label>
                        {if $display == 'Yes'}<sup>Web server user must have access for repository directory</sup>{/if}
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <i>Your project will be created in one minute after form submission.
                    It's not allowed to change project's name upon creation.</i>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <a class="simple-button" href="#" onclick="document.getElementById('createform').submit();">Save repository</a>
                    <a class="simple-button" href="/">Cancel</a>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>
