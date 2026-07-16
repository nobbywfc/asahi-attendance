<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── メニュー登録 ─────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'aap_admin_menu' );
function aap_admin_menu() {
    add_menu_page(
        '出欠管理',
        '出欠管理',
        'manage_options',
        'aap-events',
        'aap_admin_events_page',
        'dashicons-calendar-alt',
        30
    );
    add_submenu_page(
        'aap-events',
        'イベント一覧',
        'イベント一覧',
        'manage_options',
        'aap-events',
        'aap_admin_events_page'
    );
    add_submenu_page(
        'aap-events',
        '新規イベント作成',
        '新規作成',
        'manage_options',
        'aap-events-new',
        'aap_admin_new_event_page'
    );
}

// ─── イベント一覧ページ ────────────────────────────────────────────────────────
function aap_admin_events_page() {
    global $wpdb;

    // 削除処理
    if ( isset( $_GET['action'], $_GET['event_id'] ) && $_GET['action'] === 'delete' ) {
        check_admin_referer( 'aap_delete_event' );
        $eid = intval( $_GET['event_id'] );
        $wpdb->delete( "{$wpdb->prefix}aap_responses", [ 'event_id' => $eid ] );
        $wpdb->delete( "{$wpdb->prefix}aap_events",   [ 'id' => $eid ] );
        echo '<div class="notice notice-success"><p>イベントを削除しました。</p></div>';
    }

    $events = aap_get_all_events();
    ?>
    <div class="wrap aap-admin">
        <h1>出欠管理 - イベント一覧 <a href="<?php echo admin_url('admin.php?page=aap-events-new'); ?>" class="page-title-action">新規作成</a></h1>

        <?php if ( empty( $events ) ) : ?>
            <p>イベントがまだありません。<a href="<?php echo admin_url('admin.php?page=aap-events-new'); ?>">最初のイベントを作成</a>しましょう。</p>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>タイトル</th>
                    <th>開催日時</th>
                    <th>回答期限</th>
                    <th>回答数</th>
                    <th>ショートコード</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $events as $ev ) :
                $cnt = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}aap_responses WHERE event_id = %d", $ev->id
                ));
            ?>
                <tr>
                    <td><?php echo esc_html( $ev->id ); ?></td>
                    <td><strong><?php echo esc_html( $ev->title ); ?></strong></td>
                    <td><?php echo $ev->event_date ? esc_html( date( 'Y/m/d H:i', strtotime( $ev->event_date ) ) ) : '―'; ?></td>
                    <td><?php echo $ev->deadline ? esc_html( date( 'Y/m/d H:i', strtotime( $ev->deadline ) ) ) : '―'; ?></td>
                    <td><?php echo esc_html( $cnt ); ?> 名</td>
                    <td><code>[aap_event id="<?php echo esc_attr( $ev->id ); ?>"]</code></td>
                    <td>
                        <a href="<?php echo admin_url( 'admin.php?page=aap-events-new&edit=' . $ev->id ); ?>">編集</a> |
                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=aap-events&action=delete&event_id=' . $ev->id ), 'aap_delete_event' ); ?>"
                           onclick="return confirm('削除しますか？回答データもすべて消えます。')" style="color:red">削除</a> |
                        <a href="<?php echo admin_url( 'admin.php?page=aap-event-responses&event_id=' . $ev->id ); ?>">回答管理</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

// ─── 新規作成 / 編集ページ ─────────────────────────────────────────────────────
function aap_admin_new_event_page() {
    global $wpdb;
    $edit_id = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0;
    $ev      = $edit_id ? aap_get_event( $edit_id ) : null;
    $saved   = false;
    $error   = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['aap_save_event'] ) ) {
        check_admin_referer( 'aap_save_event' );

        $title      = sanitize_text_field( $_POST['title'] ?? '' );
        $event_date = sanitize_text_field( $_POST['event_date'] ?? '' );
        $description = wp_kses_post( $_POST['description'] ?? '' );
        $deadline   = sanitize_text_field( $_POST['deadline'] ?? '' );
        $choices_raw = array_filter( array_map( 'sanitize_text_field', explode( "\n", $_POST['choices'] ?? '' ) ) );
        $choices    = array_values( $choices_raw );

        if ( empty( $title ) ) {
            $error = 'タイトルを入力してください。';
        } elseif ( count( $choices ) < 1 ) {
            $error = '選択肢を1つ以上入力してください。';
        } else {
            $data = [
                'title'       => $title,
                'event_date'  => $event_date ?: null,
                'description' => $description,
                'deadline'    => $deadline ?: null,
                'choices'     => json_encode( $choices, JSON_UNESCAPED_UNICODE ),
            ];
            if ( $edit_id ) {
                $wpdb->update( "{$wpdb->prefix}aap_events", $data, [ 'id' => $edit_id ] );
            } else {
                $wpdb->insert( "{$wpdb->prefix}aap_events", $data );
                $edit_id = $wpdb->insert_id;
            }
            $ev    = aap_get_event( $edit_id );
            $saved = true;
        }
    }

    // デフォルト選択肢
    $default_choices = "江ノ島から参加\n大池から参加\n欠席\n未定";
    $choices_str     = $ev ? implode( "\n", $ev->choices ) : $default_choices;
    ?>
    <div class="wrap aap-admin">
        <h1><?php echo $ev ? 'イベント編集' : '新規イベント作成'; ?></h1>

        <?php if ( $saved ) : ?>
            <div class="notice notice-success"><p>保存しました。ショートコード: <code>[aap_event id="<?php echo esc_attr( $edit_id ); ?>"]</code></p></div>
        <?php endif; ?>
        <?php if ( $error ) : ?>
            <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'aap_save_event' ); ?>
            <input type="hidden" name="aap_save_event" value="1">
            <table class="form-table">
                <tr>
                    <th><label for="title">タイトル <span style="color:red">*</span></label></th>
                    <td><input type="text" id="title" name="title" class="regular-text"
                         value="<?php echo esc_attr( $ev->title ?? '' ); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="event_date">開催日時</label></th>
                    <td><input type="datetime-local" id="event_date" name="event_date"
                         value="<?php echo esc_attr( $ev && $ev->event_date ? date( 'Y-m-d\TH:i', strtotime($ev->event_date) ) : '' ); ?>"></td>
                </tr>
                <tr>
                    <th><label for="description">説明・案内文</label></th>
                    <td><textarea id="description" name="description" rows="5" class="large-text"><?php
                        echo esc_textarea( $ev->description ?? '' );
                    ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="deadline">回答期限</label></th>
                    <td><input type="datetime-local" id="deadline" name="deadline"
                         value="<?php echo esc_attr( $ev && $ev->deadline ? date( 'Y-m-d\TH:i', strtotime($ev->deadline) ) : '' ); ?>"></td>
                </tr>
                <tr>
                    <th><label for="choices">選択肢 <span style="color:red">*</span><br><small>（1行1選択肢）</small></label></th>
                    <td><textarea id="choices" name="choices" rows="6" class="regular-text"><?php
                        echo esc_textarea( $choices_str );
                    ?></textarea><p class="description">例: 参加／欠席／未定（1行に1つ）</p></td>
                </tr>
            </table>
            <?php submit_button( $ev ? '更新する' : '作成する' ); ?>
        </form>
    </div>
    <?php
}

// ─── 回答管理ページ ────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'aap_admin_responses_submenu' );
function aap_admin_responses_submenu() {
    add_submenu_page(
        'aap-events',
        '回答管理',
        '回答管理',
        'manage_options',
        'aap-event-responses',
        'aap_admin_responses_page'
    );
}

function aap_admin_responses_page() {
    global $wpdb;
    $event_id = intval( $_GET['event_id'] ?? 0 );
    if ( ! $event_id ) { echo '<div class="wrap"><p>イベントIDが指定されていません。</p></div>'; return; }

    $ev = aap_get_event( $event_id );
    if ( ! $ev ) { echo '<div class="wrap"><p>イベントが見つかりません。</p></div>'; return; }

    // 回答削除
    if ( isset( $_GET['del_resp'] ) ) {
        check_admin_referer( 'aap_del_resp' );
        $wpdb->delete( "{$wpdb->prefix}aap_responses", [ 'id' => intval($_GET['del_resp']) ] );
        echo '<div class="notice notice-success"><p>回答を削除しました。</p></div>';
    }

    $responses = aap_get_responses( $event_id );
    $counts    = aap_count_by_choice( $responses, $ev->choices );
    ?>
    <div class="wrap aap-admin">
        <h1>回答管理 — <?php echo esc_html( $ev->title ); ?></h1>
        <p><a href="<?php echo admin_url('admin.php?page=aap-events'); ?>">← イベント一覧に戻る</a></p>

        <h2>集計</h2>
        <ul style="list-style:disc;padding-left:1.5em">
        <?php foreach ( $counts as $c => $n ) : ?>
            <li><?php echo esc_html($c); ?>: <strong><?php echo esc_html($n); ?></strong> 名</li>
        <?php endforeach; ?>
            <li>合計: <strong><?php echo count($responses); ?></strong> 名</li>
        </ul>

        <h2>回答一覧</h2>
        <?php if ( empty( $responses ) ) : ?>
            <p>まだ回答がありません。</p>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>回答</th><th>名前</th><th>コメント</th><th>更新日時</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach ( $responses as $r ) : ?>
                <tr>
                    <td><?php echo esc_html($r->choice); ?></td>
                    <td><?php echo esc_html($r->name); ?></td>
                    <td><?php echo esc_html($r->comment); ?></td>
                    <td><?php echo esc_html( date('m/d H:i', strtotime($r->updated_at)) ); ?></td>
                    <td>
                        <a href="<?php echo wp_nonce_url(
                            admin_url("admin.php?page=aap-event-responses&event_id={$event_id}&del_resp={$r->id}"),
                            'aap_del_resp'
                        ); ?>" onclick="return confirm('削除しますか？')" style="color:red">削除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}
