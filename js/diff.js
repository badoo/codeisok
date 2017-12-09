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

    // Render the new file list
    if (window._file_list) {
        renderFileList(window._file_list, document.querySelector('.file-list'));
    }
});


function renderFileList(fileList, container) {
    const folderMap = getFolderMap(fileList);
    container.innerHTML = `<ul class="file-list">${folderMap.map(folder => renderFolder(folder)).join('\n')}</ul>`;
}

function renderFolder(folder) {
    return `
        <li>
            <span class="folder">${folder.name}</span>
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
    return `
        <li class="status-${file.status} type-${file.fileType}"><a href="#${file.path}">${file.name}</a></li>
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
                    status: file.status,
                    fileType: file.fileType,
                    path: file.path
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
        return flatten(folder);
    });
}

function flatten(folder) {
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

            return flatten(newFolder);
        }
    }

    folder.contents = folder.contents.map(flatten);

    return folder;
}