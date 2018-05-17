/*
 * GitPHP javascript project search and listing
 *
 * Live search of project list
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

var oldSearchValue = '';
var searchTimeout = null;

function runSearch() {
    var search = $('input.projectSearchBox').val().toLowerCase();
    oldSearchValue = search;
    clearTimeout(searchTimeout);
    searchTimeout = null;

    if (search.length == 0) {
        $('a.clearSearch').hide();
    } else {
        $('a.clearSearch').show();
    }

    var visibleCats = [];

    var hasmatch = false;

    $('table.project-list tr.projectRow').each(function() {
        if (search.length < 1) {
            $(this).show();
            hasmatch = true;
            return;
        }

        var category = '';

        var projectName = $(this).find('td.projectName a').text();
        if (projectName.length > 0) {
            if (projectName.toLowerCase().indexOf(search) != -1) {
                $(this).show();
                hasmatch = true;
                category = $(this).data('category');
                if (category) {
                    if (jQuery.inArray(category, visibleCats) == -1) {
                        visibleCats.push(category);
                    }
                }
                return;
            }
        }
        var projectDesc = $(this).find('td.projectDescription a').text();
        if (projectDesc.length > 0) {
            if (projectDesc.toLowerCase().indexOf(search) != -1) {
                $(this).show();
                hasmatch = true;
                category = $(this).data('category');
                if (category) {
                    if (jQuery.inArray(category, visibleCats) == -1) {
                        visibleCats.push(category);
                    }
                }
                return;
            }
        }
        var projectOwner = $(this).find('td.projectOwner em').text();
        if (projectOwner.length > 0) {
            if (projectOwner.toLowerCase().indexOf(search) != -1) {
                $(this).show();
                hasmatch = true;
                category = $(this).data('category');
                if (category) {
                    if (jQuery.inArray(category, visibleCats) == -1) {
                        visibleCats.push(category);
                    }
                }
                return;
            }
        }
        $(this).hide();
    });

    $('table.project-list tr.categoryRow').each(function() {
        if (search.length < 1) {
            $(this).show();
            return;
        }

        var category = $(this).children('th.categoryName').text();
        if (category.length > 0) {
            if (jQuery.inArray(category, visibleCats) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        }
    });

    var msgDiv = $('div.message');
    if (hasmatch) {
        msgDiv.hide();
        $('tr.projectHeader').show();
    } else {
        if (msgDiv.length == 0) {
            msgDiv = jQuery(document.createElement('div'));
            msgDiv.addClass('message');
            msgDiv.appendTo($('table.project-list'));
        }

        var msg = GITPHP_RES_NO_MATCHES_FOUND.replace(new RegExp('%1'), $('input.projectSearchBox').val());
        msgDiv.text(msg);

        msgDiv.show();
        $('tr.projectHeader').hide();
    }

    $('img.searchSpinner').hide();
};

function initProjectSearch() {
    $('#projectSearchForm').keypress(function(e) {
        if (e.which == 13) {
            return false;
        }
    });

    var rows = $('table.project-list tr');

    if (rows.length == 0) {
        // No projects, just stop
        return;
    }

    // Store project categories
    var category = '';
    rows.each(function() {
        if ($(this).hasClass('categoryRow')) {
            category = $(this).children('th.categoryName').text();
        } else if ($(this).hasClass('projectRow')) {
            if (category.length > 0) {
                $(this).data('category', category);
            }
        }
    });

    $('a.clearSearch').click(function() {
        $('img.searchSpinner').show();
        $('input.projectSearchBox').val('');
        oldSearchValue = '';
        runSearch();
        return false;
    });

    var typeEvent = function() {
        if ($('input.projectSearchBox').val() != oldSearchValue) {
            $('img.searchSpinner').show();
            if (searchTimeout != null) {
                clearTimeout(searchTimeout);
            }
            setTimeout("runSearch()", 500);
        }
    };

    $('input.projectSearchBox').keyup(typeEvent).bind('input paste', typeEvent);
};

function initProjectToggle(params) {
    var currentFolder = null
    var folderGroup = $();

    // Render sub-folders and their collapsers
    $('.projectRow').get().forEach(projectRow=>{
        projectRow = $(projectRow);

        const projectName = projectRow.children('.projectName').text().trim();
        const folder = projectName.split('/')[0];

        const hasSubFolder = folder !== projectName;
        const subFolderChanged = currentFolder && currentFolder !== folder;

        // No folder found or different folder found
        if (!hasSubFolder || subFolderChanged) {

            // If we have a group then render toggle for it
            if (folderGroup.length > 1) {
                appendCollapser();
            }

            folderGroup = $(hasSubFolder && subFolderChanged && projectRow);
        }
        else {
            folderGroup = folderGroup.add(projectRow);
        }

        currentFolder = folder;
    });

    if (folderGroup.length > 1) {
        appendCollapser();
    }

    function appendCollapser() {
        $(`<tr class="light projectRow subProjectFolder subProjectFolderHeader list_header">
                <th class="categoryName" colspan="6">
                    <span class="expander-folder expanded"></span>
                    ${currentFolder}
                </th>
            </tr>
        `).insertBefore(folderGroup.get(0));
        folderGroup.addClass('subProjectFolder');
        folderGroup.find('.projectName a').each(function (params) {
            const text = $(this).text();
            $(this).text(text.replace(`${currentFolder}/`, ''));
        });
    }

    // Bind events for collapsers
    bindCollapser('.categoryRow', 'projectRow');
    bindCollapser('.subProjectFolderHeader', 'subProjectFolder');
}

function bindCollapser(rowQuery, matchClass) {
    $(rowQuery).click(function () {
        const $category = $(this);
        const $expander = $(this).find('.expander-folder');
        let $projects = $();

        let nextProject = $category.next();
        while (nextProject.length > 0 && nextProject.attr('class').search(matchClass) !== -1) {
            $projects = $projects.add(nextProject);
            nextProject = nextProject.next();
        }

        if ($expander.hasClass('expanded')) {
            $projects.hide();
            $projects.find('.expander-folder').removeClass('expanded');
        }
        else {
            $projects.show();
            $projects.find('.expander-folder').addClass('expanded');
        }

        $expander.toggleClass('expanded');
    });
}

$(document).ready(function() {
    initProjectSearch();
    initProjectToggle();
});
