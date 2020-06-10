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
        // Triggered by sexy_highlighter.tpl
        window.initTreeDiff = function () {
            // Check for existing viewed file names and mark them as viewed
            markFilesAsViewed(getViewedFiles());
            detectActiveBlobs();
            enableScrollDetection();
        }

        // Start listening for hash changes
        window.onhashchange = detectActiveBlobs;
    }

    enablePaneDragging();
    enableFolderCollapsing();
    sortFileList(treeMap);

    $('.two-panes').removeClass('is-loading');
}

function sortFileList(treeMap) {
    const flatTreeMap = getFlatTreeMap(treeMap);
    const rightPane = document.querySelector('.right-pane');
    const fileNodes = Array.from(rightPane.querySelectorAll('a.anchor')).reduce((fileNodeMap, anchor) => {
        fileNodeMap[anchor.name] = anchor.parentElement;
        return fileNodeMap;
    }, {});

    flatTreeMap.forEach(fileName => {
        const node = fileNodes[fileName];
        if (node) {
            rightPane.appendChild(node);
        }
    })
}

function getFlatTreeMap(treeMap) {
    if (treeMap instanceof Array) {
        return treeMap.map(getFlatTreeMap).flat();
    }

    if (treeMap.type === 'folder') {
        return treeMap.contents.map(getFlatTreeMap).flat();
    }

    return treeMap.path;
}

function enableFolderCollapsing() {
    $('.type-folder').click(function () {
        $(this).parent().toggleClass('collapsed');
    });
}

function markFilesAsViewed(viewedFiles) {
    viewedFiles.forEach(viewedFileName => {
        $(`.file-list a[href="#${viewedFileName}"]`).parent().addClass('is-visited');
    });
}

function enableScrollDetection() {
    if (!window.IntersectionObserver || !WeakMap) {
        return;
    }

    const visibleNodes = new WeakMap();

    const observer = new window.IntersectionObserver((entries) => {
        entries
        .forEach(entry => {
            const el = entry.target;

            if (visibleNodes.has(el)) {
                clearTimeout(visibleNodes.get(el));
            }

            if (!entry.isIntersecting) {
                visibleNodes.delete(el);
                return;
            }

            const fileName = el.querySelector('a.anchor').name;
            markFileAsActive(fileName);
            scrollFileListItemIntoView(fileName);

            // auto-loading diff showed poor UX when implemented
            // const suppressedDiff = el.querySelector('.show_suppressed_diff');
            // if (suppressedDiff) {
            //     suppressedDiff.click();
            // }

            // For a file to be marked as viewed, it must be in the viewport for 2 seconds
            visibleNodes.set(el, setTimeout(() => {
                setViewedFiles([fileName]);
            }, 1000));
        });
    }, {
        // Start triggering when an element is in the top 90% of the screen
        rootMargin: `0px 0px -${window.innerHeight * 0.9}px 0px`,

        // 0 threshold means we get triggers for when an element is visible and also when it leaves
        threshold: 0
    });

    document.querySelectorAll('.right-pane .diffBlob').forEach(el => observer.observe(el));
}

function markFileAsActive(fileName) {
    $('.file-list li').removeClass('is-active');
    $(`.file-list a[href="#${fileName}"]`).parent().addClass('is-active');
}

function scrollFileListItemIntoView(fileName) {
    const fileListElement = document.querySelector(`.file-list a[href="#${fileName}"]`);

    if (fileListElement) {
        // scrollIntoView scrolls the whole page, so we use a chrome-only api
        fileListElement.scrollIntoView({
            block: 'nearest',
            inline: 'start'
        });

        // fix weird scrolling
        document.querySelector('.js-left-pane').scrollLeft = 0;
    }
}

// Dragging for panes
function enablePaneDragging() {
    const leftPane = $('.js-left-pane');
    const dragger = $('.js-pane-dragger');

    if (leftPane.length === 0) {
        return;
    }

    let dragStart = 0;
    let leftPaneWidth;

    dragger.on('mousedown', function (e) {
        dragStart = e.clientX;
        leftPaneWidth = leftPane.width();

        $(document.body)
            .on('mouseup', onMouseUp)
            .on('mousemove', onMouseMove);
    });

    function onMouseUp() {
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

    // Find the file name and highlight on the left pane
    const fileName = closestBlob.find('a.anchor').attr('name');
    markFileAsActive(fileName);

    // Save the viewed files again
    const viewedFiles = $('.type-file.is-visited a').get().map(el => el.getAttribute('href'));
    setViewedFiles(viewedFiles);

    // Make sure it's in the view
    if (foundElement.length > 0) {
        foundElement.get(0).scrollIntoView();
        scrollFileListItemIntoView(fileName);
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

    if (files.length === 0) {
        return;
    }

    try {
        markFilesAsViewed(files);

        sessionStorage.setItem('viewed-files', JSON.stringify({
            reviewKey: getReviewKey(),
            files: files.concat(getViewedFiles())
        }));
    }
    catch(err) {
        console.error(err);
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
            <a href="#${file.path}" ${fileDataString}>${file.name} <span class="review-comments" name="files_index_${file.path}" ${fileDataString}></span></a>
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
