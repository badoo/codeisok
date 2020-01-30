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
    if (!window.sbsTreeDiff) {
        // Check for existing viewed file names and mark them as viewed
        const viewedFiles = getViewedFiles();
        viewedFiles.forEach(viewedFileName => {
            $(`.file-list a[href="${viewedFileName}"]`).parent().addClass('is-active is-visited');
        });

        detectActiveBlobs();

        // Start listening for hash changes
        window.onhashchange = detectActiveBlobs;
    }

    enablePaneDragging();
    enableFolderCollapsing();

    $('.two-panes').removeClass('is-loading');
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

    dragger.on('mousedown', function (e) {
        isDragging = true;
        dragStart = e.clientX;
        leftPaneWidth = leftPane.width();

        $(document.body)
            .on('mouseup', onMouseUp)
            .on('mousemove', onMouseMove);
    });

    function onMouseUp() {
        isDragging = false;

        $(document.body)
            .off('mouseup', onMouseUp)
            .off('mousemove', onMouseMove);
    }

    function onMouseMove(e) {
        e.preventDefault();

        const offset = dragStart - e.clientX;
        leftPane.css('min-width', Math.min(leftPaneWidth - offset, window.innerWidth / 3));
    }
}

function detectActiveBlobs() {
    const hash = decodeURIComponent(window.location.hash.substr(1));
    const foundElement = $(`[name="${hash}"]`);
    const closestBlob = foundElement.closest('.diffBlob');
    closestBlob.addClass('is-visible').siblings().removeClass('is-visible');

    // Find the file name and highlight on the left pane
    const fileName = closestBlob.find('a.anchor').attr('name');
    $('.file-list li').removeClass('is-active');
    $(`.file-list a[href="#${fileName}"]`).parent().addClass('is-active is-visited');

    // Save the viewed files again
    const viewedFiles = $('.type-file.is-visited a').get().map(el => el.getAttribute('href'));
    setViewedFiles(viewedFiles);

    // Make sure it's in the view
    if (foundElement.length > 0) {
        foundElement.get(0).scrollIntoView();
    }

    const suppressedDiff = closestBlob.find('.show_suppressed_diff');
    if (suppressedDiff.length > 0) {
        suppressedDiff.click();
    }

}

function getReviewKey() {
    return `${$('#review_hash_base').val()}:${$('#review_hash_head').val()}`;
}

function getViewedFiles() {
    let viewedFiles = [];

    try {
        const viewedFileData = JSON.parse(sessionStorage.getItem('viewed-files'));
        const reviewKey = viewedFileData.reviewKey;

        if (reviewKey === getReviewKey() && viewedFileData.files) {
            return viewedFileData.files;
        }
        else {
            sessionStorage.removeItem('viewed-files');
        }
    }
    catch(e) {
        sessionStorage.removeItem('viewed-files');
    }

    return viewedFiles;
}

function setViewedFiles(files) {
    files = files || [];

    try {
        sessionStorage.setItem('viewed-files', JSON.stringify({
            reviewKey: getReviewKey(),
            files: files
        }));
    }
    catch(e) {
        sessionStorage.removeItem('viewed-files');
    }
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
            <a href="#${file.path}" ${fileDataString}>${file.name} <span class="review-comments" name="files_index_${file.path}"></span></a>
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
        const folders = file.path.split('/');

        let currentFolder = contents;
        folders.forEach((folder, idx) => {
            // Last content is always a file
            if (idx === folders.length - 1) {
                currentFolder.push(Object.assign({}, file, {
                    type: 'file',
                    name: folder
                }));
                return;
            }

            // If no folder found then make one
            let foundFolder = currentFolder.find(item => item.name === folder);
            if (!foundFolder) {
                foundFolder = {
                    type: 'folder',
                    name: folder,
                    contents: []
                };
                currentFolder.push(foundFolder);
                currentFolder = foundFolder.contents;
            }
            // Move into the folder if found one
            else {
                currentFolder = foundFolder.contents;
            }
        });

        return contents;
    }, [])
    .map(folder => {
        return flattenAndSortFolder(folder);
    });
}

function flattenAndSortFolder(folder) {
    // It's a file or something else
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

            return flattenAndSortFolder(newFolder);
        }
    }

    folder.contents = folder.contents
        .map(flattenAndSortFolder)
        .sort((itemA, itemB) => {

            // Folders at the top always
            if (itemA.type !== 'folder' && itemB.type === 'folder') {
                return 1;
            }
            if (itemA.type === 'folder' && itemB.type !== 'folder') {
                return -1;
            }

            return itemA.name.localeCompare(itemB.name);
        });

    return folder;
}
