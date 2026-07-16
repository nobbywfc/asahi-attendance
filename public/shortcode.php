<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── ショートコード登録 ────────────────────────────────────────────────────────
add_shortcode( 'aap_event', 'aap_event_shortcode' );
function aap_event_shortcode( $atts ) {
    $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'aap_event' );
    $event_id = intval( $atts['id'] );
    if ( ! $event_id ) return '<p class="aap-error">イベントIDを指定してください。</p>';

    $ev = aap_get_event( $event_id );
    if ( ! $ev ) return '<p class="aap-error">イベントが見つかりません。</p>';

    $responses = aap_get_responses( $event_id );
    $counts    = aap_count_by_choice( $responses, $ev->choices );

    // 回答期限チェック
    $is_closed = $ev->deadline && strtotime( $ev->deadline ) < time();

    // 編集トークンチェック
    $edit_token    = sanitize_text_field( $_GET['aap_edit'] ?? '' );
    $editing_resp  = $edit_token ? aap_get_response_by_token( $edit_token ) : null;
    if ( $editing_resp && intval( $editing_resp->event_id ) !== $event_id ) {
        $editing_resp = null;
    }

    ob_start();
    ?>
    <div class="aap-wrap" id="aap-event-<?php echo esc_attr($event_id); ?>">

        <!-- ─── イベント情報 ─── -->
        <div class="aap-event-header">
            <h2 class="aap-event-title"><?php echo esc_html($ev->title); ?></h2>

            <?php if ( $ev->event_date ) : ?>
            <p class="aap-event-meta">
                <span class="aap-icon">📅</span>
                日時: <?php echo esc_html( date( 'Y年m月d日('. ['日','月','火','水','木','金','土'][date('w', strtotime($ev->event_date))].') H時i分', strtotime($ev->event_date)) ); ?> 〜
            </p>
            <?php endif; ?>

            <?php if ( $ev->description ) : ?>
            <div class="aap-description"><?php echo wp_kses_post( nl2br($ev->description) ); ?></div>
            <?php endif; ?>

            <?php if ( $ev->deadline ) : ?>
            <p class="aap-deadline <?php echo $is_closed ? 'aap-closed' : ''; ?>">
                回答期限: <?php echo esc_html( date( 'Y年m月d日 H:i', strtotime($ev->deadline) ) ); ?>
                <?php if ($is_closed) echo ' <strong>（締め切り済み）</strong>'; ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- ─── 回答フォーム ─── -->
        <?php if ( ! $is_closed ) : ?>
        <div class="aap-form-section">
            <h3><?php echo $editing_resp ? '回答を編集' : '回答登録'; ?></h3>
            <form id="aap-form-<?php echo esc_attr($event_id); ?>" class="aap-form">
                <input type="hidden" name="action"   value="aap_submit_response">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                <?php if ( $editing_resp ) : ?>
                <input type="hidden" name="edit_token" value="<?php echo esc_attr($edit_token); ?>">
                <?php endif; ?>

                <div class="aap-field">
                    <label for="aap-name-<?php echo esc_attr($event_id); ?>">お名前 <span class="aap-req">*</span></label>
                    <input type="text" id="aap-name-<?php echo esc_attr($event_id); ?>"
                           name="name" required maxlength="100"
                           value="<?php echo $editing_resp ? esc_attr($editing_resp->name) : ''; ?>">
                </div>

                <div class="aap-field aap-choices">
                    <?php foreach ( $ev->choices as $choice ) :
                        $slug = sanitize_title( $choice );
                        $checked = $editing_resp && $editing_resp->choice === $choice ? 'checked' : '';
                    ?>
                    <label class="aap-choice-label">
                        <input type="radio" name="choice" value="<?php echo esc_attr($choice); ?>" <?php echo $checked; ?> required>
                        <span class="aap-choice-text"><?php echo esc_html($choice); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="aap-field">
                    <label for="aap-comment-<?php echo esc_attr($event_id); ?>">コメント（任意）</label>
                    <textarea id="aap-comment-<?php echo esc_attr($event_id); ?>"
                              name="comment" rows="2" maxlength="300"><?php
                        echo $editing_resp ? esc_textarea($editing_resp->comment) : '';
                    ?></textarea>
                </div>

                <button type="submit" class="aap-btn-submit">
                    <?php echo $editing_resp ? '更新する' : '回答を送信'; ?>
                </button>
                <span class="aap-loading" style="display:none">送信中…</span>
            </form>
            <div class="aap-form-msg" style="display:none"></div>
        </div>
        <?php endif; ?>

        <!-- ─── 集計 ─── -->
        <div class="aap-summary">
            <h3>回答状況</h3>
            <ul class="aap-summary-list">
            <?php foreach ( $counts as $c => $n ) : ?>
                <li>
                    <span class="aap-choice-badge" data-choice="<?php echo esc_attr( aap_choice_class($c) ); ?>">
                        <?php echo esc_html($c); ?>
                    </span>
                    <strong><?php echo esc_html($n); ?></strong> 名
                </li>
            <?php endforeach; ?>
                <li class="aap-total">合計 <strong><?php echo count($responses); ?></strong> 名</li>
            </ul>
        </div>

        <!-- ─── 回答一覧 ─── -->
        <div class="aap-responses">
            <h3>回答一覧 <small class="aap-sort-hint">（列ヘッダーをクリックで並び替え）</small></h3>
            <?php if ( empty($responses) ) : ?>
                <p>まだ回答がありません。</p>
            <?php else : ?>
            <div class="aap-table-wrap">
            <table class="aap-table" id="aap-table-<?php echo esc_attr($event_id); ?>">
                <thead>
                    <tr>
                        <th class="sortable" data-col="0">回答</th>
                        <th class="sortable" data-col="1">名前</th>
                        <th>コメント</th>
                        <th class="sortable" data-col="3">更新</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $responses as $r ) :
                    $edit_url = add_query_arg( 'aap_edit', $r->edit_token, get_permalink() );
                ?>
                    <tr>
                        <td>
                            <span class="aap-badge" data-choice="<?php echo esc_attr( aap_choice_class($r->choice) ); ?>">
                                <?php echo esc_html($r->choice); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($r->name); ?></td>
                        <td><?php echo esc_html($r->comment); ?></td>
                        <td data-ts="<?php echo esc_attr( strtotime($r->updated_at) ); ?>">
                            <?php echo esc_html( date('m/d H:i', strtotime($r->updated_at)) ); ?>
                        </td>
                        <td>
                            <?php if ( ! $is_closed ) : ?>
                            <a href="<?php echo esc_url($edit_url); ?>" class="aap-edit-link">✏️ 編集</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- .aap-wrap -->
    <?php
    return ob_get_clean();
}

/**
 * 選択肢をCSSクラス名用に変換
 */
function aap_choice_class( $choice ) {
    $map = [
        '参加'  => 'attend',
        '出席'  => 'attend',
        '欠席'  => 'absent',
        '未定'  => 'pending',
        '調整中' => 'pending',
    ];
    foreach ( $map as $k => $v ) {
        if ( mb_strpos( $choice, $k ) !== false ) return $v;
    }
    return 'other';
}

// ─── AJAX: 回答送信 ────────────────────────────────────────────────────────────
add_action( 'wp_ajax_aap_submit_response',        'aap_ajax_submit_response' );
add_action( 'wp_ajax_nopriv_aap_submit_response', 'aap_ajax_submit_response' );
function aap_ajax_submit_response() {
    global $wpdb;

    check_ajax_referer( 'aap_nonce', 'nonce' );

    $event_id   = intval( $_POST['event_id'] ?? 0 );
    $name       = sanitize_text_field( $_POST['name'] ?? '' );
    $choice     = sanitize_text_field( $_POST['choice'] ?? '' );
    $comment    = sanitize_textarea_field( $_POST['comment'] ?? '' );
    $edit_token = sanitize_text_field( $_POST['edit_token'] ?? '' );

    if ( ! $event_id || ! $name || ! $choice ) {
        wp_send_json_error( [ 'msg' => '名前と回答は必須です。' ] );
    }

    $ev = aap_get_event( $event_id );
    if ( ! $ev ) {
        wp_send_json_error( [ 'msg' => 'イベントが見つかりません。' ] );
    }
    if ( $ev->deadline && strtotime( $ev->deadline ) < time() ) {
        wp_send_json_error( [ 'msg' => '回答期限が終了しています。' ] );
    }
    if ( ! in_array( $choice, $ev->choices, true ) ) {
        wp_send_json_error( [ 'msg' => '無効な選択肢です。' ] );
    }

    if ( $edit_token ) {
        // 更新
        $existing = aap_get_response_by_token( $edit_token );
        if ( ! $existing || intval($existing->event_id) !== $event_id ) {
            wp_send_json_error( [ 'msg' => '編集権限がありません。' ] );
        }
        $wpdb->update(
            "{$wpdb->prefix}aap_responses",
            [ 'name' => $name, 'choice' => $choice, 'comment' => $comment,
              'updated_at' => current_time('mysql') ],
            [ 'edit_token' => $edit_token ]
        );
        wp_send_json_success( [ 'msg' => '回答を更新しました。', 'mode' => 'edit' ] );
    } else {
        // 新規
        $token = aap_generate_token();
        $wpdb->insert(
            "{$wpdb->prefix}aap_responses",
            [
                'event_id'   => $event_id,
                'name'       => $name,
                'choice'     => $choice,
                'comment'    => $comment,
                'edit_token' => $token,
                'updated_at' => current_time('mysql'),
            ]
        );
        wp_send_json_success( [
            'msg'        => '回答を登録しました。',
            'mode'       => 'new',
            'edit_token' => $token,
        ]);
    }
}
