/**
 * @author emakhrov
 * @date 05.03.2013
 * @time 17:41
 */

function setSelectionRange(input, selectionStart, selectionEnd) {
    if (input.setSelectionRange) {
        input.focus();
        input.setSelectionRange(selectionStart, selectionEnd);
    }
    else if (input.createTextRange) {
        var range = input.createTextRange();
        range.collapse(true);
        range.moveEnd('character', selectionEnd);
        range.moveStart('character', selectionStart);
        range.select();
    }
}

function getInputSelection(el) {
    var start = 0, end = 0, normalizedValue, range,
        textInputRange, len, endRange;

    if (typeof el.selectionStart == "number" && typeof el.selectionEnd == "number") {
        start = el.selectionStart;
        end = el.selectionEnd;
    } else {
        range = document.selection.createRange();

        if (range && range.parentElement() == el) {
            len = el.value.length;
            normalizedValue = el.value.replace(/\r\n/g, "\n");

            // Create a working TextRange that lives only in the input
            textInputRange = el.createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());

            // Check if the start and end of the selection are at the very end
            // of the input, since moveStart/moveEnd doesn't return what we want
            // in those cases
            endRange = el.createTextRange();
            endRange.collapse(false);

            if (textInputRange.compareEndPoints("StartToEnd", endRange) > -1) {
                start = end = len;
            } else {
                start = -textInputRange.moveStart("character", -len);
                start += normalizedValue.slice(0, start).split("\n").length - 1;

                if (textInputRange.compareEndPoints("EndToEnd", endRange) > -1) {
                    end = len;
                } else {
                    end = -textInputRange.moveEnd("character", -len);
                    end += normalizedValue.slice(0, end).split("\n").length - 1;
                }
            }
        }
    }

    return {
        start: start,
        end: end
    };
}

function toggleCheckBoxes(el) {
    $('.projects_checkbox').each(function(){this.checked = el.checked;});
}

function submitSearchForm(el) {
    $('#error').text("");
    res = false;
    if ("" != $('input[name="t"]').val()) {
        if (0 < $('.projects_checkbox:checked').size()) {
            form = $(el).parents('form');
            $('.projects_checkbox:checked').each(function(){el = $(this).clone(); el.css('display', 'none'); el.appendTo(form);});
            form.submit();
            res = true;
        } else {
            $('#error').text("Select at least one project to search in!");
        }
    } else {
        $('#error').text("Search text is empty!");
    }
    return res;
}

function getSearchResults(stext, sproject, element)
{
    var data = {
        text: stext,
        project: sproject
    };
    $.post('/?a=searchtext', data, function(data) {
        $(element).parent().html(data.response);
    }, 'json');
}

function keydownSearchField(el) {
    if (event.keyCode == 13) {
        submitSearchForm(el);
    }
}

    function addLineFocus() {
        document.addEventListener('click', event => {
            const element = event.target;
            if (element.classList.contains('line-number')) {
                removeLineHighlight();
                const lineNumber = element.textContent;
                location.hash = 'L' + lineNumber;
                highlightLine(lineNumber);
            }
        }, true);

        setTimeout(function() {
            const lineMatch = location.hash.match(/#L(\d+)/);
            if (lineMatch) {
                const lineNumber = lineMatch[1];
                highlightLine(lineNumber);
                focusLine(lineNumber);
            }
        }, 50);
    }

    function removeLineHighlight() {
        const highlightedLines = document.querySelectorAll('.highlighted-line');
        Array.prototype.forEach.call(highlightedLines, line => {
            line.classList.remove('highlighted-line');
        });
    }

    function highlightLine(lineNumber) {
        const line = document.querySelector('.line.number' + lineNumber);
        if (line) {
            line.classList.add('highlighted-line');
        }
    }

    function focusLine(lineNumber) {
        const line = document.querySelector('.line.number' + lineNumber);
        if (line) {
            const lineRect = line.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const lineOffset = lineRect.top + scrollTop;
            const scrollOffset = lineOffset - window.innerHeight / 3;
            window.scrollBy(0, scrollOffset);
        }
    }

function initSearchContainer() {
    const searchContainer = $('.page-search-container');
    const pageSearch = $('.page-search');

    if (searchContainer.length > 0) {
        $('.page-search-container').append(pageSearch);
    }

    $('.js-show-extra-settings').click(function (e) {
        if ($(e.target).hasClass('js-show-extra-settings')) {
            $(this).parent().toggleClass('settings-visible');
        }
    });
}

$( document ).ready(function() {
    addLineFocus();
    initSearchContainer();
});
