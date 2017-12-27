var SmartFilter = {

    onInvertClick : function ($this, classname_prefix, css_class, hidden_prefix) {
        var $objects = null;
        var selected = false;
        if ($this.hasClass('selected')) {
            $objects = $this.siblings();
            selected = true;
        } else {
            $objects = $this.parent().children().filter('.selected').add($this);
        }

        $objects = $objects.filter('.' + css_class);
        if (selected && $objects.hasClass('selected')) {
            $objects = $objects.filter('.selected');
        }

        $objects.each(function() {
            SmartFilter.onFilterClick(null, $(this), classname_prefix, css_class, hidden_prefix);
        });
    },

    onFilterClick : function(event, $object, classname_prefix, css_class, hidden_prefix) {
        if (event && event.shiftKey) {
            SmartFilter.onInvertClick($object, classname_prefix, css_class, hidden_prefix);
            return;
        }
        $('.' + classname_prefix + '-' + $object.data(css_class)).toggleClass(hidden_prefix + '-hidden');
        $object.toggleClass('selected');
    }
};

$(function(){
    $('#diff-context')
        .keyup(function(e){
            if (e.keyCode == 13) {
                $.cookie('diff_context', $(this).val(), {expires: 365});
                e.preventDefault();
                location.reload();
            }
        })
        .change(function(e){
            $.cookie('diff_context', $(this).val(), {expires: 365});
            location.reload();
        });

    $('#diff-ignore-whitespace').change(function(){
        $.cookie('ignore_whitespace', $(this).is(':checked'), {expires: 365});
        location.reload();
    });

    $('#diff-ignore-format').change(function(){
        $.cookie('ignore_format', $(this).is(':checked'), {expires: 365});
        location.reload();
    });

    $('.file_filter .extension').click(function(event) {
        SmartFilter.onFilterClick(event, $(this), 'filetype', 'extension', 'ext');
    });

    $('.file_filter .status').click(function(event) {
        SmartFilter.onFilterClick(event, $(this), 'status', 'status', 'st');
    });

    $('.file_filter .folder').click(function(event) {
        SmartFilter.onFilterClick(event, $(this), 'folder', 'folder', 'fldr');
    });

    // Render tree diff mode
    if (window._file_list) {
        renderTreeDiff(window._file_list, document.querySelector('.file-list'));
    }
});


function renderTreeDiff(fileList, container) {

    // Update the folder list
    const treeMap = getFolderMap(fileList);
    container.innerHTML = `
        <ul class="file-list">
            ${treeMap.map(item => item.type === 'file' ? renderFile(item) : renderFolder(item)).join('\n')}
        </ul>`;

    // Check if we need to display a pre-selected comment or blob
    if (!window.sbsDiff) {
        detectActiveBlobs();
        // Start listening for hash changes
        window.onhashchange = detectActiveBlobs;
    }

    enablePaneDragging();
    enableFolderCollapsing();

    $('.left-pane').removeClass('is-loading');
}

function enableFolderCollapsing() {
    $('.type-folder').click(function () {
        $(this).parent().toggleClass('collapsed');
    });
}

// Dragging for panes
function enablePaneDragging() {
    const leftPane = $('.js-left-pane');
    const dragger = $('.js-pane-dragger');

    if (leftPane.length === 0) {
        return;
    }

    let isDragging = false;
    let dragStart = 0;
    let leftPaneWidth;

    $(document.body)
        .mousedown(function (e) {
            if (e.target !== dragger.get(0)) {
                return;
            }

            isDragging = true;
            dragStart = e.clientX;
            leftPaneWidth = leftPane.width();
        })
        .mouseup(function () {
            isDragging = false;
        })
        .mousemove(function (e) {
            if (!isDragging) {
                return;
            }

            e.preventDefault();

            const offset = dragStart - e.clientX;
            leftPane.css('min-width', Math.min(leftPaneWidth - offset, window.innerWidth / 3));
        });
}

function detectActiveBlobs() {
    const hash = window.location.hash.substr(1);
    const closestBlob = $(`[name="${hash}"]`).closest('.diffBlob');
    closestBlob.addClass('is-visible').siblings().removeClass('is-visible');

    // Find the file name and highlight on the left pane
    const fileName = closestBlob.find('a.anchor').attr('name');
    $('.file-list li').removeClass('is-active');
    $(`.file-list a[href="#${fileName}"]`).parent().addClass('is-active is-visited');
}

function renderFolder(folder) {
    return `
        <li>
            <span class="type-folder">${folder.name}</span>
            <ul class="file-list">
                ${folder.contents.map(content => {
                    if (content.type === 'file') {
                        return renderFile(content);
                    }
                    return renderFolder(content);
                }).join('\n')}
            </ul>
        </li>
    `
}

function renderFile(file) {
    const fileData = file.data || {};

    const fileDataString = Object.keys(fileData).map(key => {
        return `data-${key}="${fileData[key]}"`;
    }).join(' ');

    return `
        <li class="type-file status-${file.status} filetype-${file.fileType}">
            <a href="#${file.path}" ${fileDataString}>${file.name}</a>
            <span class="review-comments" name="files_index_${file.path}"></span>
        </li>
    `;
}

/**
 * Takes a list of files and converts it into a nested folder structure where contents
 * are flattened for each folder with one subfolder only
 * @param {Array} fileList
 */

function getFolderMap(fileList) {
    return fileList.reduce((contents, file) => {
        let currentFolder = contents;

        const folders = file.path.split('/');

        folders.forEach((folder, idx) => {
            let foundFolder = currentFolder.find(item => item.name === folder);

            // Last content is always a file
            if (idx === folders.length - 1) {
                currentFolder.push({
                    type: 'file',
                    name: folder,
                    ...file
                })
            }
            // If no folder found then make one
            else if (!foundFolder) {
                foundFolder = {
                    type: 'folder',
                    name: folder,
                    contents: []
                };
                currentFolder.push(foundFolder);
                currentFolder = foundFolder.contents;
            }
            // Move into the folder
            else {
                currentFolder = foundFolder.contents;
            }
        });

        return contents;
    }, [])
    .map(folder => {
        return flattenFolder(folder);
    });
}

function flattenFolder(folder) {
    if (!folder || folder.type !== 'folder') {
        return folder;
    }

    if (folder.contents.length === 1) {
        const subFolder = folder.contents[0];

        if (subFolder.type === 'folder') {
            const newFolder = {
                type: 'folder',
                name: folder.name + '/' + subFolder.name,
                contents: subFolder.contents
            };

            return flattenFolder(newFolder);
        }
    }

    folder.contents = folder.contents.map(flattenFolder);

    return folder;
}