<?php
/**
 * Plugin Name: 出欠管理プラグイン（旭走友会）
 * Plugin URI:  https://wp-asahi.shimada-farm.net
 * Description: イベントの出欠確認・回答登録・一覧表示をショートコードで提供するプラグイン。
 * Version:     1.0.0
 * Author:      Nobby / 旭走友会
 * Text Domain: asahi-attendance
 * License:     GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AAP_VERSION',    '1.0.0' );
define( 'AAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ─── DB テーブル作成 ──────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'aap_create_tables' );
function aap_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    // イベントテーブル
    $sql_events = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aap_events (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title         VARCHAR(255) NOT NULL,
        event_date    DATETIME     DEFAULT NULL,
        description   TEXT         DEFAULT NULL,
        deadline      DATETIME     DEFAULT NULL,
        choices       TEXT         NOT NULL,
        created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    // 回答テーブル
    $sql_responses = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aap_responses (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_id   INT UNSIGNED NOT NULL,
        name       VARCHAR(100) NOT NULL,
        choice     VARCHAR(100) NOT NULL,
        comment    TEXT         DEFAULT NULL,
        edit_token VARCHAR(64)  NOT NULL,
        updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_event (event_id),
        INDEX idx_token (edit_token)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_events );
    dbDelta( $sql_responses );
}

// ─── ファイル読み込み ──────────────────────────────────────────────────────────
require_once AAP_PLUGIN_DIR . 'includes/helpers.php';
require_once AAP_PLUGIN_DIR . 'admin/admin-page.php';
require_once AAP_PLUGIN_DIR . 'public/shortcode.php';

// ─── フロント用 CSS / JS ───────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'aap_enqueue_front_assets' );
function aap_enqueue_front_assets() {
    wp_enqueue_style(
        'aap-front',
        AAP_PLUGIN_URL . 'public/css/front.css',
        [],
        AAP_VERSION
    );
    wp_enqueue_script(
        'aap-front',
        AAP_PLUGIN_URL . 'public/js/front.js',
        [ 'jquery' ],
        AAP_VERSION,
        true
    );
    wp_localize_script( 'aap-front', 'aapData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'aap_nonce' ),
    ]);
}

// ─── 管理画面 CSS ─────────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'aap_enqueue_admin_assets' );
function aap_enqueue_admin_assets( $hook ) {
    if ( strpos( $hook, 'aap-events' ) === false ) return;
    wp_enqueue_style(
        'aap-admin',
        AAP_PLUGIN_URL . 'admin/admin.css',
        [],
        AAP_VERSION
    );
}
