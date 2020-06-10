/**
 * @author emakhrov
 * @date 11.10.2012
 * @time 15:18
 */


var Review = (function() {

    var Review = {
        review_id: null,
        ticket: null,
        select_file: null,
        select_line: null,
        select_after: null,
        select_count: null,
        select_active: false,
        select_start_line: null,
        form_shown: false,
        comments_count: 0,
        comments_count_draft: 0,
        last_mouse_over: null,
        last_mouse_down: null,
        comments_ids_file: null,
        current_comment: null,
        is_review_controls_dim: false,
        is_handlers_bound: false
    };

    Review.reviewSaveSuccess = function(data) {
        $('#review_msg').removeClass('error');
        if (data.log && document.cookie.indexOf("debug_js") >= 0) {
            statslow_add_request(data.log);
        }
        if (data.error) {
            if ($('#review_msg:visible')) {
                $('#review_msg').html(data.error);
                $('#review_msg').addClass('error');
                for (i=0;i<3;i++) {
                    $('#review_review').fadeTo(100, 0.1).fadeTo(200, 1.0)
                }
            } else {
                alert(data.error);
            }
            return false;
        } else if (data.msg) {
            if ($('#review_msg:visible')) {
                $('#review_msg').html(data.msg);
            } else {
                alert(data.msg);
            }
            return false;
        }
        return true;
    };

    Review.saveError = function() {
        console.log('Error');
    };
    Review.reviewSaveComplete = function() {
        // Trigger any listeners to review comment changes
        if (window.onhashchange) {
            window.onhashchange();
        }
    };


    Review.getHash = function() {
        return $('#review_hash_head').val();
    };
    Review.getHashBase = function() {
        var hash_base = $('#review_hash_base').val();
        return hash_base ? hash_base : '';
    };

    Review.getRepo = function() {
        return Review.getUrlParams().p;
    };

    Review.getCommitMessage = function() {
        return $('div > a.title').text();
    };

    Review.getUrlParams = function () {
        var q = {};
        var paramEquation = location.search.substr(1).split('&');
        for (var i in paramEquation) {
            var param = paramEquation[i].split('=');
            q[decodeURIComponent(param[0])] = decodeURIComponent(param[1]);
        }
        return q;
    };

    Review.redrawReviewSelector = function(reviews, new_review, new_review_name) {
        var $reviewSelect = $('#review_ticket_select');
        $reviewSelect.html('<strong>Review </strong>');

        if (reviews !== undefined) {
            var reviewSelectClickHandler = function() {
                $('.review-select').removeClass('review-selected');
                $(this).addClass('review-selected');
                $(this).data('review-id') ? $('#review_ticket').hide() : $('#review_ticket').show();
                Review.review_id = $(this).data('review-id');
                Review.ticket = $(this).data('ticket');
            };
            for (var i in reviews) {
                var $reviewNew = $('<div class="review-select" title="Id: ' + reviews[i]['review_id'] + '">'
                    + reviews[i]['ticket'] + ' (' + reviews[i]['comments_count']
                    + (reviews[i]['comments_count_draft'] ? '+' + reviews[i]['comments_count_draft'] + ' draft': '')
                    + ')</div>')
                    .data('review-id', reviews[i]['review_id'])
                    .data('ticket', reviews[i]['ticket'])
                    .click(reviewSelectClickHandler);
                if (reviews[i].selected) {
                    $reviewNew.addClass('review-selected');
                    Review.review_id = reviews[i]['review_id'];
                }
                $reviewSelect.append($reviewNew);
            }
            var $reviewNew = $('<div class="review-select">New</div>').click(reviewSelectClickHandler)
                .data('review-id', 0)
                .data('ticket', '');
            $('#review_ticket').val(new_review_name);
            if (new_review) {
                Review.review_id = 0;
                $reviewNew.addClass('review-selected');
                $('#review_ticket').show();
            }
        } else {
            var $reviewNew = $('<div class="review-select review-selected" title="Id: ' + Review.review_id + '">'
                + Review.ticket + ' (' + Review.comments_count
                + (Review.comments_count ? '+' + Review.comments_count + ' draft': '')
                + ')</div>');
        }
        $reviewSelect.append($reviewNew);
        $reviewSelect.show();
    };

    Review.checkReviewId = function() {
        var urlQuery = Review.getUrlParams();
        if (Review.review_id === null && (urlQuery.review === undefined || urlQuery.review === '')) {
            var data = {
                commit_message: Review.getCommitMessage(),
                hash: Review.getHash(),
                hash_base: Review.getHashBase(),
                url: location.toString()
            };

            $.post('/?a=get_review', data, function(data) {
                if (!Review.reviewSaveSuccess(data)) {
                    return;
                }

                Review.redrawReviewSelector(data.reviews, data.new_review, data.new_review_name);

            }, 'json')
                .error(Review.saveError).complete(Review.reviewSaveComplete);
            return false;
        } else if (Review.review_id == null) {
            Review.review_id = urlQuery.review;
            Review.redrawReviewSelector();

            SessionChecker.setTimer();

        } else if (Review.review_id === 0) {
            Review.ticket = $('#review_ticket').val();
        }
        return true;
    };


    Review.commentSubmit = function() {
        if (!Review.checkReviewId()) {
            return;
        }
        var comment_data = {
            review_id: Review.review_id,
            ticket: Review.ticket,
            repo: Review.getRepo(),
            hash: Review.getHash(),
            hash_base: Review.getHashBase(),
            file: Review.select_file,
            line: Review.select_line,
            real_line: Review.select_after,
            text: $('#review_text').val(),
            lines_count: Review.select_count
        };
        $.post('/?a=save_comment', comment_data, function(data) {
            if (!Review.reviewSaveSuccess(data)) {
                return;
            }
            Review.hideForm();
            $('#review_text').val('');

            if (!Review.getUrlParams().review) {
                history.replaceState(null, null, location.search + '&review=' + data.review_id + '#' + data.comment_id);
                Review.review_id = data.review_id;
                Review.checkReviewId();
            } else {
                location.hash = data.comment_id;
            }

            Review.showComments();
            $('#review_finish').show();
            $('#review_abort').show();
        }, 'json')
            .error(Review.saveError).complete(Review.reviewSaveComplete);
    };

    Review.dimReviewControls = function(dimState) {
        if (dimState) {
            Review.is_review_controls_dim = true;
            $('#review_loader').show();
            $('#review_finish').hide();
            $('#review_abort').hide();
        } else {
            Review.is_review_controls_dim = false;
            $('#review_loader').hide();
            $('#review_finish').show();
            $('#review_abort').show();
        }
    };

    Review.setReviewStatus = function() {
        if (this.id == 'review_abort' && !confirm('Are you sure?')) {
            return;
        }
        if (Review.is_review_controls_dim) {
            return;
        }
        Review.dimReviewControls(true);
        $.ajax('/?a=set_review_status', {
            type: 'POST',
            data: {
                review_id: Review.review_id,
                status: this.id === 'review_finish' ? 'Finish' : 'Deleted',
            },
            async: false
        }).success(function (data) {
            Review.dimReviewControls(false);
            if (!Review.reviewSaveSuccess(data)) {
                return;
            }
            Review.hideForm();
            $('#review_finish').hide();
            $('#review_abort').hide();
            Review.showComments();
        }).error(Review.saveError).complete(Review.reviewSaveComplete);
    };

    Review.getClickTargetParams = function(target) {
        var file;
        var $review_file = $('#review_file');
        if ($review_file.size()) {
            file = $review_file.val();
        } else if ($(target).hasClass('commentable')) {
            var $diffBlob = $(target).parents('.diffBlob:first');
            if ($diffBlob.size() == 0) {
                console.log('DEBUG ME: getClickTargetParams diffBlob', $diffBlob);
                return false;
            }
            try {
                file = $diffBlob.find('a[name]').get(0).name;
            } catch (e) {
                console.log('DEBUG ME: getClickTargetParams exception', e);
                return;
            }
        }
        var line = target.className.match(/number(\d+)/)[1];
        var before = target.className.match(/numb(-?\d+)/)[1];
        var after = target.className.match(/numa(-?\d+)/)[1];
        return {file: file, line: parseInt(line), before: parseInt(before), after: parseInt(after)};
    };

    Review.getNodeFile = function(file) {
        return $('.diffBlob a[name="' + file + '"]').parent();
    };

    Review.getNodeFileLine = function(file, line, lineb, linea, preceding) {
        if (file != undefined && file != '' && !$('#review_file').size()) {
            var $file = Review.getNodeFile(file);
            var $line;
            if (linea != undefined || lineb != undefined) {
                $line = $file.find('.code div.line.numa' + linea);
                if (!$line.size()) {
                    $line = $file.find('.code div.line.numb' + lineb);
                }
                if (preceding) {
                    $line = $line.prevAll().eq(preceding - 1);
                }
            } else {
                $line = $file.find('.code div.line.number' + line);
            }
        } else {
            $line = $('div.line.number' + line);
        }
        return $line;
    };

    Review.commentableLine = function($line) {
        var commentable = $line.hasClass('commentable');
        var line = $line.hasClass('line');
        var blob = $('#review_file').size();
        var alreadyCommented = $line.hasClass('commented');

        var wordDiff = $line.find('.diff > span').size();

        if ((commentable || wordDiff || (line && blob)) && !alreadyCommented) {
            return true;
        }
        return false;
    };

    Review.getCommentIdForIndex = function(crement) {
        /* special case: first click - next goes to first, prev goes to last */
        if (Review.current_comment == null && crement == 1) {
            Review.current_comment = 0;
            return Review.comments_ids_file[Review.current_comment];
        } else if (Review.current_comment == null && crement == -1) {
            Review.current_comment = Review.comments_ids_file.length - 1;
            return Review.comments_ids_file[Review.current_comment];
        }
        Review.current_comment += crement;
        if (Review.current_comment > Review.comments_ids_file.length - 1) {
            Review.current_comment = 0;
        } else if (Review.current_comment < 0) {
            Review.current_comment = Review.comments_ids_file.length - 1;
        }
        var resultVal = Review.comments_ids_file[Review.current_comment];
        return resultVal;
    };

    Review.showComments = function() {
        var q = Review.getUrlParams();

        if (q.review) {
            $.post('/?a=get_comments', {
                review_id: q.review,
                hash: Review.getHash(),
                hash_base: Review.getHashBase()
            }, function(data) {
                if (!Review.reviewSaveSuccess(data)) {
                    return;
                }
                $('div.comments').remove();
                $('a.files_index_anchor').remove();
                $('div.commented').each(function(){
                    $(this).removeClass('commented');
                });
                Review.comments_ids_file = [];
                var anchorDefferredForClick = null;
                if (data.comments && data.comments.length) {
                    var prev_line = 0;
                    for (var i in data.comments) {
                        var thread = '';
                        var file = data.comments[i].file;
                        var line = parseInt(data.comments[i].line);
                        var lines_count = parseInt(data.comments[i].lines_count) || 0;
                        var real_line = parseInt(data.comments[i].real_line) || undefined;
                        if (data.comments[i].side) {
                            real_line = real_line + 1;
                        }
                        var real_line_before = undefined;
                        var text = data.comments[i].text;
                        var author = data.comments[i].author;
                        var date = data.comments[i].date;
                        for (var j = line - lines_count; j <= line; j++) {
                            $line = Review.getNodeFileLine(file, j, real_line_before, real_line, line - j);
                            $line.addClass('commented').data('line', line).data('file', file).data('lines_count', lines_count)
                                .data('real_line', real_line).data('real_line_before', real_line_before);
                            $line.closest('.diffBlob').addClass('has-review-comment');
                        }
                        if (data.comments[i].status == 'Draft') {
                            $('#review_finish').show();
                            $('#review_abort').show();
                        }
                        if (prev_line == line) {
                            thread = ' thread';
                        }
                        let commentsHtml = `
                            <div class="comments ${thread}">
                                <a class="comment-user" name="${data.comments[i].id}" href="#${data.comments[i].id}">
                                    <span class="author">${author}</span>
                                    <span class="date">${date}</span>
                                </a>
                                <span class="text">${text}</span>
                            </div>`;

                        if (data.comments[i].status == 'Draft') {
                            commentsHtml = `
                                <div class="comments draft ${thread}" title="draft, click to edit">
                                    <a class="comment-user" name="${data.comments[i].id}" href="#${data.comments[i].id}">
                                        <span class="author">${author} (draft)</span>
                                        <span class="date">${date}</span>
                                    </a>
                                    <span class="text">${text}</span>
                                    <div id="review_ticket_tab">
                                        <div class="btn_small review_btn review_save" id="review_line_edit" title="Edit this comment">Edit</div>
                                        <div class="btn_small review_btn review_cancel" id="review_line_delete" title="delete this comment">Delete</div>
                                    </div>
                                </div>`;
                        }

                        var $container = $line.children('.comment-container');

                        if ($container.length === 0) {
                            $line.append('<div class="comment-container"></div>');
                            $container = $line.children('.comment-container');
                        }
                        $container.append(commentsHtml);
                        var $comment_anchor = $('<a href="#' + data.comments[i].id + '" class="files_index_anchor">#' + (1 + parseInt(i)) + '</a>');
                        $comment_anchor.data('file', file).click(Review.clickCommentAnchor);
                        if (location.hash.substr(1) == data.comments[i].id) {
                            anchorDefferredForClick = $comment_anchor;
                        }
                        $('td[name="files_index_' + file + '"]').append(' ').append($comment_anchor);
                        $('span[name="files_index_' + file + '"]').append(' ').append($comment_anchor);
                        Review.comments_ids_file.push({id: data.comments[i].id, file: file});
                        prev_line = line;
                    }
                    $container.append($('#review_comment'));
                    $('#review_review').show();
                    $('body').addClass('has-review-block');
                    $('#review_commentnav_next').show();
                    $('#review_commentnav_prev').show();

                    if (anchorDefferredForClick != null) anchorDefferredForClick.click();
                    Review.ticket = data.review.ticket;
                    Review.comments_count = data.comments_count;
                    Review.comments_count_draft = data.comments_count_draft;
                } else {
                    $('#review_review').hide();
                    $('body').removeClass('has-review-block');
                    $('#review_commentnav_next').hide();
                    $('#review_commentnav_prev').hide();
                }
                Review.checkReviewId();
            }).error(Review.saveError).complete(Review.reviewSaveComplete);
        }
    };

    Review.showForm = function(target) {
        if ($(target).hasClass('line')==false) {
            target = $(target).parents('.line');
        }
        $('l').removeClass('hoverable').hide();
        $('#review_review').show();
        $('body').addClass('has-review-block');
        $('#review_posfixedspace').append($('#review_comment'));
        target.className += ' selected selected-multi';
        var $draft = $(target).find('.draft');
        if ($draft.size()) {
            var text = $draft.find('.text').text();
            $('#review_text').text(text).val(text);
            $draft.hide();
        } else {
            $('#review_text').text('').val('');
        }
        Review.form_shown = true;
        var wnd = $('#review_comment').show();
        Review.adjustTextarea($('#review_text'));
        if ($(target).find('#review_save').size()) {
            return true;
        }
        var $container = $(target).children('.comment-container');

        if ($container.length === 0) {
            $(target).append('<div class="comment-container"></div>');
            $container = $(target).children('.comment-container');
        }

        $container.append(wnd);
        Review.checkReviewId();
        $('#review_text').focus();
    };

    Review.hideForm = function(e) {
        Review.select_file = null;
        Review.select_line = null;
        Review.select_after = null;
        Review.select_count = null;
        Review.select_active = false;
        try {
            $('div.line').removeClass('selected').removeClass('selected-multi');
            Review.form_shown = false;
            $('#review_comment').hide();
            $('.commented .draft').show();
            if (!Review.review_id) {
                $('#review_review').hide();
                $('body').removeClass('has-review-block');
            }
            $('#review_posfixedspace').remove();
            e && e.stopPropagation && e.stopPropagation();
        } catch (e) {
            console.log(e);
        }
        $('l').addClass('hoverable').show();
    };

    Review.selectReset = function() {
        $('div.line').removeClass('selected').removeClass('selected-multi');

        for (var line = Review.select_line - Review.select_count; line <= Review.select_line; line++) {
            var $node = Review.getNodeFileLine(Review.select_file, line);
            $node.addClass('selected-multi');
        }
        $node.addClass('selected');
    };

    Review.selectStart = function(e) {
        Review.last_mouse_down = e.target;
        if (!Review.commentableLine($(this)) || e.which != 1 || Review.form_shown || e.target.tagName != 'L') {
            return true;
        }
        if (this != e.target && !(e.target.tagName == 'SPAN' && $(e.target).parent().hasClass('diff')) && e.target.tagName != 'L') {
            console.log(e.target, '!=', this);
            return false;
        }

        $('body').on('mousemove', null, null, Review.selectMove);

        Review.hideForm();
        var params = Review.getClickTargetParams(this);
        Review.select_file = params.file;
        Review.select_line = params.line;
        Review.select_start_line = params.line;
        Review.select_after = params.after;
        Review.select_active = true;
        Review.selectReset();
        return false;
    };

    Review.selectEnd = function(e) {
        $('body').off('mousemove', null, null, Review.selectMove);

        // If there is a selection in the document, we don't want to show dialog
        // This allows developers to copy code without losing selection
        if (window.getSelection().isCollapsed === false) {
            return;
        }

        var $target = $(e.target);

        /* click onto existing comment */
        if (!Review.form_shown && ($target.hasClass('commented') || $target.parents('.commented').size() != 0) && $target.attr('id') != 'review_line_delete') {
            if (!$target.hasClass('commented')) {
                $target = $target.parents('.commented');
            }
            if ($target.data('line')) {
                Review.select_count = $target.data('lines_count');
                Review.select_after = $target.data('real_line');
                $target = Review.getNodeFileLine($target.data('file'), $target.data('line'), $target.data('real_line_before'), $target.data('real_line'));
            }
            var params = Review.getClickTargetParams($target[0]);
            Review.select_file = params.file;
            Review.select_line = params.line;
            Review.select_after = params.after;
            Review.selectReset();
            Review.showForm($target[0]);
            return;
        }

        // Check if the user clicked on delete button
        if ($target.attr('id') == 'review_line_delete' && $target.parents('.commented').size() != 0) {
            if (confirm('Are you sure?')) {
                // (current comment container -> draft -> link).name
                var $comment_id = $target.parents('.comment-container').find('.comments.draft > a').attr('name');
                $.post('/?a=delete_comment', {comment_id: $comment_id}, function(data) {
                    $target = $target.parents('.commented');
                    $target.find('table').hide();
                    Review.showComments();
                }, 'json');
            }
            return;
        }

        if (Review.select_active) {
            Review.select_active = false;

            var $target = Review.getNodeFileLine(Review.select_file, Review.select_line);
            Review.showForm($target);
            Review.last_mouse_over = null;
        } else if (e.target == Review.last_mouse_down) {
            var target = e.target;
            if (!$target.is('div.line')) {
                var $div_line = $target.parents('div.line');
                if ($div_line.size() == 0) return;
                target = $div_line.get(0);
            }

            if (!Review.commentableLine($(target))) {
                return;
            }

            // If an existing comment exists, scroll to it
            if (Review.form_shown) {
                const reviewText = $('#review_text');

                // User clicked inside the form
                if (target.contains(reviewText.get(0))) {
                    return;
                }

                const currentReviewText = reviewText.val();

                if (currentReviewText.length > 0) {
                    const userConfirm = window.confirm('You have an unsaved comment, do you want to go to it?');

                    if (userConfirm) {
                        window.scrollTo({ left: 0, top: reviewText.offset().top - 50, behavior: 'smooth' })
                    }

                    return;
                }
            }

            var params = Review.getClickTargetParams(target);
            Review.select_file = params.file;
            Review.select_line = params.line;
            Review.select_after = params.after;
            Review.selectReset();
            if ($target.hasClass('spaces') || $target.hasClass('line-number')) {
                $target = $target.parent('.line');
            }
            Review.showForm($target);
        }
    };

    Review.selectMove = function(e) {
        if (!Review.select_active || this == Review.last_mouse_over) {
            return true;
        }

        const $nearestLine = $(e.target).closest('.line');
        if ($nearestLine.length === 0) {
            return true;
        }

        var params = Review.getClickTargetParams($nearestLine.get(0));
        if (!Review.commentableLine($nearestLine) || !params
            || params.file != Review.select_file) {
            return true;
        }

        if (params.line > Review.select_line) {
            Review.select_after = params.after;
        }
        Review.select_line = Math.max(params.line, Review.select_line, Review.select_start_line);
        Review.select_start_line = Math.min(params.line, Review.select_line, Review.select_start_line);
        Review.select_count = Review.select_line - Review.select_start_line;
        Review.selectReset();
        Review.last_mouse_over = $nearestLine.get(0);
        return true;
    };

    Review.gotoNextComment = function() {
        var id_file = Review.getCommentIdForIndex(1);
        var commentnav_next = $('#review_commentnav_next');
        commentnav_next.attr('href', '#' + id_file.id).data('file', id_file.file);
        Review.clickCommentAnchor.apply(this instanceof HTMLAnchorElement ? this : commentnav_next.get(0), arguments);
        return false;
    };

    Review.gotoPrevComment = function() {
        var id_file = Review.getCommentIdForIndex(-1);
        var commentnav_prev = $('#review_commentnav_prev');
        commentnav_prev.attr('href', '#' + id_file.id).data('file', id_file.file);
        Review.clickCommentAnchor.apply(this instanceof HTMLAnchorElement ? this : commentnav_prev.get(0), arguments);
        return false;
    };

    /**
     * @param textarea jQuery selected textarea or keyup event object
     */
    Review.adjustTextarea = function(textarea) {
        if (textarea instanceof jQuery && textarea.size()) {
            textarea = textarea[0];
        } else {
            textarea = this;
        }
        textarea.rows = 1;
        if (textarea.value) {
            textarea.rows = textarea.value.split("\n").length;
        }
        textarea.rows = textarea.rows > 32 ? 32 : textarea.rows;
        return true;
    };

    Review.clickCommentAnchor = function() {
        var file = $(this).data('file');
        var $file = Review.getNodeFile(file);
        var $expander = $file.find('.too_large_diff a');
        if ($expander.size()) {
            show_suppressed_diff($expander);
        }

        var hash = this.href.substr(this.href.lastIndexOf('#') + 1);
        location.hash = hash;
        $anchorElement = $('a[name="' + hash + '"]');
        if ($anchorElement.size()) {
            $('html,body').animate({scrollTop: $anchorElement.offset().top});
        }
        return false;
    };

    Review.toggleReviewComments = function (e) {
        e.preventDefault();
        var input = $('.js-toggle-review-comments-input')[0];
        input.checked = !input.checked;
        $('.diffBlob.lines-visible').removeClass('lines-visible');
        $('.page_body').toggleClass('only-comments');
        if (input.checked) { //expand suppressed diffs if those have review comments
            var files = [];
            Review.comments_ids_file.forEach(function(elem){
                if (files.indexOf(elem.file) == -1) {
                    files.push(elem.file);
                }
            });
            files.forEach(function(name){
                var $file = Review.getNodeFile(name);
                var $expander = $file.find('.too_large_diff a');
                if ($expander.size()) {
                    show_suppressed_diff($expander);
                }
            })
        }
    }

    Review.expandBlobIfNeeded = function (e) {
        // Comments only mode is not shown, no need to go forther
        if (!$('.page_body').hasClass('only-comments')) {
            return;
        }
        $(e.currentTarget).addClass('lines-visible');
    }

    /**
     * call when all lines are hightlighted and div.line exists in DOM
     */
    Review.start = function() {
        $.ajax({
            url: '/?a=get_unfinished_review',
            dataType: 'json',
            success: function (data) {
                if (!Review.getUrlParams().review && data.last_review !== undefined) {
                    $('#notifications').html("<span data-url='" + data.last_review + "'>You have an unfinished review. You can <strong id='review_edit'>Continue reviewing</strong> or <strong id='review_delete'>Delete</strong> it.</span>");
                    $('#review_edit').click(function() {
                        document.location = $('#review_edit').parent('span').data('url');
                    });
                    $('#review_delete').click(function() {
                        $.getJSON('/?a=delete_all_draft_comments', {}, function(data, status_text, xhr) {
                            if (data.status == 0) {
                                $('#notifications').html("");
                                $('#notifications').hide();
                            }
                        });
                    });
                    $('#notifications').show();
                }
            }
        });
        $('.line-number').parent().removeClass('commentable').addClass('commentable');

        if ($('#review_file').size()) {
            $('.line').removeClass('commentable').addClass('commentable');
        }
        $('.line-number').parent().prepend('<l class="hoverable context-menu-button"></l>');

        if (!Review.is_handlers_bound) {
            $('.js-toggle-review-comments').click(Review.toggleReviewComments);
            $('.js-toggle-treediff').click(function () {
                $(this).toggleClass('checked');
            });
            $('.diffBlob').click(Review.expandBlobIfNeeded);
            $('#review_save').click(Review.commentSubmit);
            $('#review_cancel').click(Review.hideForm);
            $('#review_finish').click(Review.setReviewStatus);
            $('#review_abort').click(Review.setReviewStatus);
            $('#review_commentnav_prev').click(Review.gotoPrevComment);
            $('#review_commentnav_next').click(Review.gotoNextComment);

            let keyMap = {};

            $('#review_text').keydown(function(e) {
                keyMap[e.keyCode] = true;

                // Detect CMD+Enter
                if (keyMap[91] && keyMap[13]) {
                    Review.commentSubmit();

                    // Clear it because we won't trigger keyup if we modify the DOM
                    keyMap = {};
                }
                else if (keyMap[13]) {
                    e.preventDefault();

                    var cur = getInputSelection(e.target);
                    this.value = this.value.substr(0, cur.start) + "\n" + this.value.substr(cur.end);
                    setSelectionRange(e.target, cur.start + 1, cur.end + 1);
                    Review.adjustTextarea.call(this, e);
                }
            })
            .keyup(function(e) {
                delete keyMap[e.keyCode];
                Review.adjustTextarea.call(this, e);
            });

            $('body').on({
                mousedown: Review.selectStart
            }, 'div.line');

            $('body').on('mouseup', null, null, Review.selectEnd);

            $('body').on('keyup', null, null, function(e) {
                if (e.keyCode == 27) {
                    Review.hideForm();
                } else if ((e.keyCode == 38 || e.keyCode == 80) && !(e.target instanceof HTMLTextAreaElement) && (e.ctrlKey || e.altKey)) {
                    Review.gotoPrevComment();
                    window.location = $('#review_commentnav_prev').attr('href');
                } else if ((e.keyCode == 40 || e.keyCode == 78) && !(e.target instanceof HTMLTextAreaElement) && (e.ctrlKey || e.altKey)) {
                    Review.gotoNextComment();
                    window.location = $('#review_commentnav_next').attr('href');
                }
                return true;
            });

            $(window).on('beforeunload', function() {
                if (Review.form_shown && $('#review_text').val()) {
                    return "You're about to leave this page but you have unsaved comment\nIf you continue unsaved message would be lost";
                }
            });

            // Fix copy paste of code
            document.addEventListener('copy', function(e){
                const copyTarget = $(e.target);
                const parentBlob = copyTarget.parents('.diffBlob');

                // Ignore copy pasting of non-code
                if (parentBlob.length === 0) {
                    return;
                }

                // HACKIEST of hacks :) but it works!
                $('.line-number').addClass('hidden');
                const clipboardData = document.getSelection().toString();
                $('.line-number').removeClass('hidden');

                e.clipboardData.setData('text/plain', clipboardData);
                e.preventDefault(); // default behaviour is to copy any selected text
            });
        }
        Review.is_handlers_bound = true;
        Review.showComments();
    };

    return Review;
})();
