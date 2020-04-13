
SideBySideReview = function (compare, initialize) {
    if (initialize === undefined) {
        initialize = false;
    }

    if (compare !== undefined) {
        this.compare = compare;
        this.leftEditor = compare.mergely('cm', 'lhs');
        this.rightEditor = compare.mergely('cm', 'rhs');
    } else {
        this.compare = undefined;
        this.leftEditor = undefined;
        this.rightEditor = undefined;
    }

    this.lastSelection = undefined;
    this.review_id = this.getUrlParams().review;
    if (!this.review_id) {
        this.review_id = this.getHashParams().review;
    }
    this.finished = false;
    this.review = false;
    this.existingComments = [];
    this.editingCommentId = -1;
    SideBySideReview.Mouse.init();

    this.ticket = $('div.title-right a.ticket').text();

    if (initialize) {
        this.restore();
    }
};

SideBySideReview.prototype = {
    constructor: SideBySideReview,

    setCompareElement: function (compare) {
        this.compare = compare;
        this.leftEditor = compare.mergely('cm', 'lhs');
        this.rightEditor = compare.mergely('cm', 'rhs');
        var review = this;
        this.compare.mergely('options').updated = function () {
            review.update();
        }
    },

    setReviewId: function (new_review_id) {
        this.review_id = new_review_id;
        if (!this.getUrlParams().review && !this.getHashParams().review) {
            location.hash = '#review=' + this.review_id;
        }
        this.showReviewStatus();
    },

    setReviewText: function (text) {
        $(this.review.node).find('textarea')[0].value = text;
    },

    setReviewStatus: function (new_status, callback) {
        $.ajax(
            '/?a=set_review_status',
            {
                type: 'POST',
                data: {
                    review_id: this.review_id,
                    status: new_status
                },
                async: false
            }
        ).done(callback);
    },

    setSelection: function (range) {
        for (var lineNumber = range.from; lineNumber <= range.to; ++lineNumber) {
            range.editor.addLineClass(lineNumber, 'background', 'selected_text');
        }
    },

    loadReview: function (review_id) {
        var review = this;

        $.post('/?a=get_comments', {
            review_id: review_id,
            hash: this.getHash(),
            hash_base: this.getHashBase(),
            file: $('#review_file').val()
        }, 'ajax')
            .fail(function () {
                alert('Cannot get review comments from the server. Check url and/or try again later');
            })
            .done(function (data) {
                review.finished = true;
                if (data.comments) {
                    data.comments.forEach(
                        function (comment_data) {
                            if (comment_data.status == 'Deleted') {
                                return;
                            }
                            if (comment_data.status != 'Finish') {
                                review.finished = false;
                            }
                            var line_from = parseInt(comment_data.line);
                            var comment_side = comment_data.side;
                            if (comment_data.real_line && !comment_side) {
                                line_from = parseInt(comment_data.real_line);
                            }
                            if (!comment_side) {
                                comment_side = 'rhs';
                                if (parseInt($('#rhs_length').val()) < line_from) {
                                    comment_side = 'lhs';
                                }
                            }
                            if (!$('#comment' + comment_data.id).length) {
                                review.addComment(
                                    comment_side,
                                    {
                                        from: line_from,
                                        to: Math.max(1, line_from + parseInt(comment_data.lines_count) - 1)
                                    },
                                    comment_data.text,
                                    parseInt(comment_data.id),
                                    comment_data.status == 'Draft',
                                    comment_data.date,
                                    comment_data.author
                                )
                            }
                        }
                    );
                    review.setReviewId(review_id);
                    if (review.finished || review.review_id) {
                        review.updateCommentsCounts();
                        review.showReviewStatus();
                    }
                }
            });
    },

    updateCommentsCounts: function () {
        var review = this;
        if (!this.review_id) {
            return;
        }

        $.post('/?a=get_comments', {
            review_id: this.review_id,
            hash: this.getHash(),
            hash_base: this.getHashBase()
        }, 'ajax')
            .fail(function () {
                alert('Cannot get review comments from the server. Check url and/or try again later');
            })
            .done(function (data) {
                if (data.comments) {
                    var comments_counts = {};
                    data.comments.forEach(
                        function (comment_data) {
                            if (comment_data.status == 'Deleted') {
                                return;
                            }
                            if (!comments_counts[comment_data.file]) {
                                comments_counts[comment_data.file] = 1;
                            } else {
                                comments_counts[comment_data.file] += 1;
                            }
                        }
                    );
                    for (var file_name in comments_counts) {
                        const link = $('.SBSTOC .review-comments[data-fromfile="'+file_name+'"],.SBSTOC .review-comments[data-tofile="'+file_name+'"]');
                        const commentCount = comments_counts[file_name];
                        link.html(`<span>${commentCount} comment${commentCount > 1 ? 's' : ''}</span>`);
                    }
                }
            });
    },

    discardReview: function () {
        if (this.review) {
            this.review.clear();
            this.clearSelection(this.lastSelection);
            this.lastSelection = undefined;
            this.review = false;
            this.compare.mergely('update');
            if (this.editingCommentId && this.editingCommentId > 0) {
                this.deleteComment(this.editingCommentId, true);
                this.editingCommentId = -1;
            }
        }
    },

    startReview: function (editor, line) {
        this.finished = false;
        if (line == undefined) {
            line = this.lastSelection.to;
        }

        var node = document.createElement('div');
        node.id = 'review';

        node.innerHTML =
            '<div class="sbs review_comment_block cloud_with_text" style="display: block;">' +
                '<div id="review_comment_tab">' +
                    '<textarea class="sbs" name="text" rows="1" cols="2" id="review_text"></textarea>' +
                '</div>' +
                '<div id="review_ticket_tab">' +
                    '<div class="review_btn" id="review_save">OK</div>' +
                    '<div class="review_btn" id="review_cancel">Cancel</div>' +
                '</div>' +
                '<div id="review_msg"></div>' +
            '</div>';

        this.review = editor.addLineWidget(line, node);

        var review = this;
        review.showReviewStatus();
        $('#review_save').on('click', function () { review.submitComment(); });
        $('#review_cancel').on('click', function () { review.discardReview(); });
        this.compare.mergely('update');
    },

    clearSelection: function (range) {
        for (var lineNumber = range.from; lineNumber <= range.to; ++lineNumber) {
            range.editor.removeLineClass(lineNumber, 'background', 'selected_text');
        }
        range.editor.refresh();
    },

    saveReviewToComment: function (comment_id) {
        var commentText = $('#review_text')[0].value;

        var selection = this.lastSelection;
        this.discardReview();
        this.addComment(selection.editor, selection, commentText, comment_id);
    },

    addComment: function (editor, selection, text, id, draft, date, author) {
        if (draft == undefined) {
            draft = true;
        }
        if (editor == 'lhs') {
            editor = this.leftEditor;
        } else if (editor == 'rhs') {
            editor = this.rightEditor;
        }

        selection.editor = editor;
        selection.mark = this.setSelection(selection);

        var widget = this._addCommentWidget(editor, selection.to, text, id, draft, date, author);
        this.existingComments.push({selection: selection, text: text, widget: widget, id: id, draft: draft});
        this.compare.mergely('update');
        this.showReviewStatus();

        var active_item = $('li.activeItem > a');
        var active_item_text = active_item.text();
        active_item_text = active_item_text.replace(/ \([0-9]+ comment.*?\)\)/g, '');
        active_item.text(active_item_text + ' (' + (this.existingComments.length) + ' comment(s))');

    },

    _addCommentWidget: function (editor, line, comment_text, comment_id, draft, date, author) {
        if (draft == undefined) {
            draft = true;
        }
        comment_text = comment_text.replace(new RegExp("\n",'g'), '<br />');

        var commentElement = document.createElement('div');
        commentElement.innerHTML = '<div class="sbs review_comment_block" style="display:block;" id="comment' + comment_id + '">' +
                                   '<div class="sbs cloud_with_text">' + comment_text + '</div></div>';
        if (draft) {
            commentElement.querySelector('.review_comment_block').classList.add('draft');
            var editButtons = document.createElement('div');
            editButtons.innerHTML = '<div class="review_btn comment_edit">edit</div><div class="review_btn comment_delete">delete</div>';
            editButtons.setAttribute('class', 'edit_buttons');
            editButtons.setAttribute('data-id', comment_id);

            commentElement.appendChild(editButtons);
        } else if (date && author){
            commentElement.querySelector('.cloud_with_text').innerHTML =
                `<span class="author">${author}</span>
                <span class="date">${date}</span>
                <span class="text">${comment_text}</span>`;
        }

        var lineWidget = editor.addLineWidget(line, commentElement);

        this.compare.mergely('update');

        return lineWidget;
    },

    isLineReviewed: function (editor, line_number) {
        if (!this._isLineSelected(editor, line_number)) {
            return false;
        }

        var in_draft_comment = false;
        this.existingComments.some(function (comment) {
            if (comment.selection.editor != editor) {
                return false;
            }
            if (line_number >= comment.selection.from && line_number <= comment.selection.to && comment.draft) {
                in_draft_comment = true;
                return true;
            }
        });
        return in_draft_comment;
    },

    _isLineSelected: function (editor, line_number) {
        var background_class = editor.getLineHandle(line_number).bgClass;
        if (!background_class) {
            return false;
        }
        background_class = background_class.split(' ');
        return (background_class.indexOf('selected_text') != -1);
    },

    isLineChanged: function (editor, line_number) {
        var background_class = editor.getLineHandle(line_number).bgClass;
        if (!background_class) {
            return false;
        }
        background_class = background_class.split(' ');
        var mergely_affected = background_class.indexOf('mergely') != -1;
        if (!mergely_affected) {
            return false;
        }
        if (editor == this.leftEditor) {
            return background_class.indexOf('a') == -1;
        } else {
            return background_class.indexOf('d') == -1;
        }
    },

    editComment: function (comment_id) {
        var index_to_remove;
        var review = this;
        this.existingComments.forEach(
            function (comment, index) {
                if (comment.id == comment_id) {
                    comment.widget.clear();
                    review.clearSelection(comment.selection);
                    index_to_remove = index;

                    review.lastSelection = comment.selection;
                    review.setSelection(review.lastSelection);
                    review.startReview(comment.selection.editor, comment.selection.to);
                    review.setReviewText(comment.text);
                }
            }
        );
        if (index_to_remove != undefined) {
            this.existingComments.splice(index_to_remove, 1);
            this.editingCommentId = comment_id;
        }
        this.compare.mergely('update');
    },

    deleteComment: function (comment_id, delete_from_server) {
        if (delete_from_server == undefined) {
            delete_from_server = true;
        }
        if (!delete_from_server) {
            this._removeCommentFromLocalList(comment_id);
            return;
        }
        var review = this;

        $.post('/?a=delete_comment', {comment_id: comment_id}, function (data) {
            if (data.error) {
                review._saveFailureHandler(data);
            }
        }, 'json')
            .error(function (data) {
                review._saveFailureHandler(data);
            })
            .complete(function () {
                review._removeCommentFromLocalList(comment_id);
            });
    },

    _removeCommentFromLocalList: function (comment_id) {
        var review = this;
        var index_to_remove;

        this.existingComments.forEach(
            function (comment, index) {
                if (comment.id == comment_id) {
                    comment.widget.clear();
                    review.clearSelection(comment.selection);
                    index_to_remove = index;
                }
            }
        );
        if (index_to_remove != undefined) {
            this.existingComments.splice(index_to_remove, 1);
        }
        this.compare.mergely('update');
        if (!this.review && !this.existingComments.length) {
            this.hideReviewStatus();
        }
    },

    selectionHandler: function (editor) {
        if (this.paused || this.review) {
            return;
        }
        var selection;
        if (editor.somethingSelected()) {
            selection = editor.listSelections()[0];
        } else {
            var cursor_position = editor.getCursor();
            selection = {
                anchor: cursor_position,
                head: cursor_position
            }
        }

        if (this.lastSelection != undefined) {
            this.clearSelection(this.lastSelection);
        }
        var new_selection;
        if (selection.anchor.line > selection.head.line) {
            new_selection = {to: selection.anchor.line, from: selection.head.line, editor: editor};
        } else {
            new_selection = {from: selection.anchor.line, to: selection.head.line, editor: editor};
        }
        if (this._isReviewAvailableForSelection(new_selection)) {
            this.lastSelection = new_selection;
        } else if (this.lastSelection == undefined) {
            return;
        }
        this.lastSelection.mark = this.setSelection(this.lastSelection);
        editor.setSelection(selection.head, selection.head);

        var review = this;
        SideBySideReview.Mouse.onMouseUp = function () {
            review.startReview(editor);
        };
    },

    _isReviewAvailableForSelection: function (selection) {
        for (var line_number = selection.from; line_number <= selection.to; ++line_number) {
            if (!this.isLineChanged(selection.editor, line_number) || this.isLineReviewed(selection.editor, line_number)) {
                return false;
            }
        }
        return true;
    },

    pause: function () {
        this.discardReview();
        this.paused = true;
    },

    restore: function () {
        document.currentReview = this;
        if (!this.initialized) {
            this.initialized = true;

            var review = this;
            var handler = function (editor) {
                review.selectionHandler(editor);
            };
            this.leftEditor.on('cursorActivity', handler);
            this.rightEditor.on('cursorActivity', handler);

            $('div.edit_buttons>div.comment_edit').live('click', function (event) {
                var commentDataId = $(event.target).parent()[0].getAttribute('data-id');
                document.currentReview.editComment(commentDataId);
            });
            $('div.edit_buttons>div.comment_delete').live('click', function (event) {
                var commentDataId = $(event.target).parent()[0].getAttribute('data-id');
                document.currentReview.deleteComment(commentDataId);
            });

            if (this.review_id) {
                this.loadReview(this.review_id);
                return;
            }
        }

        var comment;
        for (var commentNumber = 0; commentNumber < this.existingComments.length; ++commentNumber) {
            comment = this.existingComments[commentNumber];
            this.setSelection(comment.selection);
            comment.selection.editor.refresh();
            comment.widget = this._addCommentWidget(comment.selection.editor, comment.selection.to, comment.text, comment.id, comment.draft);
        }
        this.paused = false;
        this.compare.mergely('update');
    },

    update: function () {
        var review = this;
        this.existingComments.forEach (function (comment) {
            review.setSelection(comment.selection);
        });
        if (this.lastSelection) {
            this.setSelection(this.lastSelection);
        }

        this._updateEditor(this.leftEditor, 'a');
        this._updateEditor(this.rightEditor, 'd');
    },

    _updateEditor: function (editor, skip_bg_class) {
        var scroller = $(editor.getScrollerElement());
        var background;
        editor.eachLine(function (line_handler) {
            background = line_handler.bgClass;
            if (!background) {
                return;
            }
            background = background.split(' ');
            if (background.indexOf('mergely') != -1 && background.indexOf(skip_bg_class) == -1) {
                scroller.find('div.CodeMirror-code>div:nth-child(' + (line_handler.lineNo() + 1) + ')').css('cursor', 'pointer');
            }
        });
    },

    showReviewStatus: function () {
        var finish_review, abort_review;
        var review_status = $('#review_review');

        if (!review_status.length) {
            var review_review = '<div id="review_review" style="display: block; z-index: 1000;">';

            if (this.review_id) {
                review_review = `
                    ${review_review}
                    <div id="review_ticket_select">
                        <strong>Review </strong>
                        <div class="review-select review-selected" title="Id: ${this.review_id}">
                            ${this.ticket}(${this.review_id})
                        </div>
                    </div>`;
            } else {
                review_review = `
                    ${review_review}
                    <div id="review_ticket_select">
                        <strong>Review </strong>
                        <div class="review-select review-selected">New</div>
                    </div>`;
            }

            review_review = `
                    ${review_review}
                    <input type="text" id="review_ticket" value="${this.ticket}" />
                    <div id="review_loader" style="background: url('/images/loader.gif') transparent;height: 16px;line-height: 16px;width: 16px;display:none;">&nbsp;</div>
                    <div class="review-actions">
                        <div class="review_btn" id="review_abort" style="">Discard</div>
                        <div class="review_btn" id="review_finish" style="">Finish</div>
                    </div>
                </div>`;

            $('.page_body').append($(review_review));
            if (!this.review_id) {
                $('#review_ticket').show();
            }

            var review = this;

            finish_review = $('#review_finish');
            abort_review = $('#review_abort');
            finish_review.on('click', function () {
                review.finishReview();
            });
            abort_review.on('click', function () {
                review.abortReview();
            });

            $('#review_review').show();
            $('body').addClass('has-review-block');

            if (!this.finished) {
                finish_review.hide();
                abort_review.hide();
            }
        } else {
            finish_review = $('#review_finish');
            abort_review = $('#review_abort');
            if (this.finished) {
                finish_review.hide();
                abort_review.hide();
            } else {
                finish_review.show();
                abort_review.show();
            }
            review_status.show();
        }
    },

    hideReviewStatus: function () {
        $('#review_review').hide();
        $('body').removeClass('has-review-block');
    },

    finishReview: function () {
        this.setReviewStatus('Finish');
        this.finished = true;

        var review_id = this.getUrlParams().review;
        if (review_id && review_id > 0) {
            location.reload();
        } else {
            location.hash = '';
            location.search += "&review=" + this.review_id;
        }
    },

    abortReview: function () {
        this.hideReviewStatus();
        this.discardReview();
        this.clearComments();
        this.setReviewStatus('Deleted');
    },

    clearComments: function () {
        var review = this;
        this.existingComments.forEach(
            function (comment) {
                review.deleteComment(comment.id);
            }
        )
    },

    submitComment: function () {
        var post_url, post_data;

        post_url = '/?a=save_comment';
        post_data = {
            review_id: this.review_id,
            ticket: this.ticket,
            repo: this.getRepo(),
            hash: this.getHash(),
            hash_base: this.getHashBase(),
            file: $('#review_file').val(),
            line: this.lastSelection.from,
            real_line: parseInt(this.lastSelection.from),
            lines_count: this.lastSelection.to - this.lastSelection.from + 1,
            side: this.lastSelection.editor == this.leftEditor ? 'lhs' : 'rhs',
            text: $('#review_text').val()
        };
        if (!this.review_id) {
            post_data.ticket = $('#review_ticket').val();
        }
        var review = this;
        $.post(post_url, post_data, function (data) {
            if (data.error) {
                review._saveFailureHandler(data);
            }
            if (!review.getUrlParams().review) {
                review.setReviewId(data.review_id);
                review.updateCommentsCounts();
                review.showReviewStatus();
            }
        }, 'json')
            .fail(function (data) {
                review._saveFailureHandler(data);
            })
            .done(function (data) {
                review._commentSavedHandler(data);
            });
    },

    _saveFailureHandler: function (data) {
        $('#review_msg').html(data.error);
        $('#review_msg').addClass('error');
        for (i=0;i<3;i++) {
            $('#review_review').fadeTo(100, 0.1).fadeTo(200, 1.0)
        }
        $('#review_review').find('.review_btn').addClass('hidden');
    },

    _commentSavedHandler: function (data) {
        if (data.error || typeof data.comment_id !== 'number') {
            return;
        }
        console.log(data);
        $('#review_review').find('.review-selected').text($('#review_ticket').val());
        $('#review_ticket').hide();
        $('#review_review').find('.review_btn').removeClass('hidden');
        if (this.editingCommentId && this.editingCommentId > 0 && this.editingCommentId != data.comment_id) {
            // server saved our comment with other id for some reason
            this.deleteComment(this.editingCommentId, true);
        }
        this.saveReviewToComment(data.comment_id);
        this.editingCommentId = -1;
    },

    getUrlParams: function () {
        return this._getParamsFromString(location.search.substr(1));
    },

    getHashParams: function () {
        if (!location.hash || !(location.string instanceof String)) {
            return {};
        }
        return this._getParamsFromString(location.hash.substr(1));
    },

    _getParamsFromString: function (params_string) {
        var params_list = params_string.split('&');
        var params_dict = {};
        params_list.forEach(function (param) {
            param = param.split('=');
            params_dict[decodeURIComponent(param[0])] = decodeURIComponent(param[1]);
        });
        return params_dict;
    },

    getRepo: function () {
        return this.getUrlParams().p;
    },

    getHash: function () {
        return $('#review_hash_head').val();
    },

    getHashBase: function () {
        return $('#review_hash_base').val();
    }
};

SideBySideReview.Mouse = {
    initialized: false,
    mouseDown: false,
    onMouseUp: undefined,

    init: function () {
        if (!SideBySideReview.Mouse.initialized) {
            document.body.onmousedown = SideBySideReview.Mouse.down;
            document.body.onmouseup = SideBySideReview.Mouse.up;
            SideBySideReview.Mouse.initialized = true;
        }
    },

    down: function () {
        SideBySideReview.Mouse.mouseDown = true;
    },

    up: function () {
        SideBySideReview.Mouse.mouseDown = false;
        if (SideBySideReview.Mouse.onMouseUp != undefined) {
            SideBySideReview.Mouse.onMouseUp();
            SideBySideReview.Mouse.onMouseUp = undefined;
        }
    }
};
