/* 出欠管理プラグイン フロントJS */
(function ($) {
    'use strict';

    // ─── フォーム送信（Ajax）────────────────────────────────────────────────────
    $(document).on('submit', '.aap-form', function (e) {
        e.preventDefault();
        var $form    = $(this);
        var $btn     = $form.find('.aap-btn-submit');
        var $loading = $form.find('.aap-loading');
        var $msg     = $form.closest('.aap-form-section').find('.aap-form-msg');
        var data     = $form.serializeArray();
        data.push({ name: 'nonce', value: aapData.nonce });

        $btn.prop('disabled', true);
        $loading.show();
        $msg.hide().removeClass('success error');

        $.post(aapData.ajaxUrl, data)
            .done(function (res) {
                if (res.success) {
                    $msg.addClass('success').text(res.data.msg).show();
                    if (res.data.mode === 'new') {
                        // フォームをリセット
                        $form.find('input[name="name"]').val('');
                        $form.find('input[type="radio"]').prop('checked', false);
                        $form.find('textarea').val('');
                        // 編集リンク用にページにトークンを追加
                        var editToken = res.data.edit_token;
                        if (editToken) {
                            var editUrl = window.location.href.split('?')[0] +
                                '?aap_edit=' + editToken;
                            $msg.append('<br><a href="' + editUrl + '">✏️ 回答を編集する</a>');
                        }
                    }
                    // 回答テーブルを再読み込み
                    setTimeout(function () { location.reload(); }, 1800);
                } else {
                    $msg.addClass('error').text(res.data.msg).show();
                }
            })
            .fail(function () {
                $msg.addClass('error').text('通信エラーが発生しました。もう一度お試しください。').show();
            })
            .always(function () {
                $btn.prop('disabled', false);
                $loading.hide();
            });
    });

    // ─── テーブルソート ──────────────────────────────────────────────────────────
    $(document).on('click', '.aap-table th.sortable', function () {
        var $th    = $(this);
        var $table = $th.closest('.aap-table');
        var col    = parseInt($th.data('col'), 10);
        var asc    = !$th.hasClass('sort-asc');

        $table.find('th').removeClass('sort-asc sort-desc');
        $th.addClass(asc ? 'sort-asc' : 'sort-desc');

        var $tbody = $table.find('tbody');
        var rows   = $tbody.find('tr').toArray();

        rows.sort(function (a, b) {
            var aVal, bVal;
            if (col === 3) {
                // 更新日時はdata-tsで比較
                aVal = parseInt($(a).find('td').eq(col).data('ts'), 10) || 0;
                bVal = parseInt($(b).find('td').eq(col).data('ts'), 10) || 0;
            } else {
                aVal = $(a).find('td').eq(col).text().trim();
                bVal = $(b).find('td').eq(col).text().trim();
            }
            if (aVal < bVal) return asc ? -1 : 1;
            if (aVal > bVal) return asc ? 1 : -1;
            return 0;
        });

        $.each(rows, function (i, row) { $tbody.append(row); });
    });

})(jQuery);
