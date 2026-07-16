<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * イベントを取得
 */
function aap_get_event( $event_id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aap_events WHERE id = %d",
        $event_id
    ) );
    if ( ! $row ) return null;
    $row->choices = json_decode( $row->choices, true );
    return $row;
}

/**
 * 全イベント取得
 */
function aap_get_all_events() {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}aap_events ORDER BY created_at DESC"
    );
    foreach ( $rows as &$row ) {
        $row->choices = json_decode( $row->choices, true );
    }
    return $rows;
}

/**
 * イベントの回答一覧取得
 */
function aap_get_responses( $event_id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aap_responses WHERE event_id = %d ORDER BY updated_at DESC",
        $event_id
    ) );
}

/**
 * 回答をトークンで取得
 */
function aap_get_response_by_token( $token ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aap_responses WHERE edit_token = %s",
        $token
    ) );
}

/**
 * 集計
 */
function aap_count_by_choice( $responses, $choices ) {
    $counts = array_fill_keys( $choices, 0 );
    foreach ( $responses as $r ) {
        if ( isset( $counts[ $r->choice ] ) ) {
            $counts[ $r->choice ]++;
        }
    }
    return $counts;
}

/**
 * ランダムトークン生成
 */
function aap_generate_token() {
    return bin2hex( random_bytes( 16 ) );
}
