<?php
/**
 * WP REST API by Huiyan - 主题核心功能文件
 * 
 * 提供WordPress REST API的增强功能，包括前端访问控制、API缓存、安全加固等
 */

// 定义主题常量
define( 'WP_REST_API_HUIYAN_VERSION', '1.0.0' );
define( 'WP_REST_API_HUIYAN_DIR', get_template_directory() );
define( 'WP_REST_API_HUIYAN_URL', get_template_directory_uri() );
define( 'WP_REST_API_HUIYAN_BASENAME', plugin_basename( __FILE__ ) );

// 注册主题支持
function wp_rest_api_huiyan_theme_setup() {
    // 支持语言国际化
    load_theme_textdomain( 'wp-rest-api-huiyan', WP_REST_API_HUIYAN_DIR . '/languages' );
    
    // 支持HTML5
    add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
    
    // 支持自动feed链接
    add_theme_support( 'automatic-feed-links' );
}
add_action( 'after_setup_theme', 'wp_rest_api_huiyan_theme_setup' );

// 加载必要的功能模块
function wp_rest_api_huiyan_load_modules() {
    // 加载管理页面功能
    if ( is_admin() ) {
        require_once WP_REST_API_HUIYAN_DIR . '/includes/admin-settings.php';
        require_once WP_REST_API_HUIYAN_DIR . '/includes/api-status.php';
    }
    
    // 加载API缓存功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/api-cache.php';
    
    // 加载JWT认证功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/jwt-auth.php';
    
    // 加载安全和跨域配置功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/security-cors.php';
    
    // 加载API字段精简功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/api-fields.php';
}
add_action( 'after_setup_theme', 'wp_rest_api_huiyan_load_modules' );

// 前端访问控制 - 限制非API请求
function wp_rest_api_huiyan_restrict_frontend() {
    // 允许的路径模式
    $allowed_paths = array(
        '/wp-json/',           // REST API
        '/wp-admin/',          // 管理后台
        '/wp-login.php',       // 登录页面
        '/wp-cron.php',        // 定时任务
        '/xmlrpc.php',         // XML-RPC (但会在security.php中禁用)
    );
    
    // 获取当前请求URI
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    
    // 跳过管理后台、登录页面、REST API请求和OPTIONS请求
    if ( is_admin() || 
         $request_uri === '/wp-login.php' || 
         strpos($request_uri, '/wp-json/') !== false || 
         $request_method === 'OPTIONS' ||
         $request_uri === '/wp-cron.php' ) {
        return;
    }
    
    // 对于其他前端访问，重定向到首页（index.php中会返回403）
    if ( ! is_admin() && ! is_login_page() && ! is_rest_request() ) {
        status_header( 403 );
        include( get_template_directory() . '/index.php' );
        exit;
    }
}
add_action( 'template_redirect', 'wp_rest_api_huiyan_restrict_frontend' );

// 判断是否为登录页面
function is_login_page() {
    return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
}

// 判断是否为REST API请求
function is_rest_request() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $http_accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    return strpos($request_uri, '/wp-json/') !== false || strpos($http_accept, 'application/json') !== false;
}

// 清理WordPress头部不必要的输出
function wp_rest_api_huiyan_cleanup_head() {
    // 移除WordPress版本号
    remove_action( 'wp_head', 'wp_generator' );
    
    // 移除RSD链接
    remove_action( 'wp_head', 'rsd_link' );
    
    // 移除wlwmanifest链接
    remove_action( 'wp_head', 'wlwmanifest_link' );
    
    // 移除短链接
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    
    // 移除Emoji相关代码
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    
    // 移除canonical链接
    remove_action( 'wp_head', 'rel_canonical' );
    
    // 移除adjacent posts链接
    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
    remove_action( 'wp_head', 'adjacent_posts_rel_link', 10 );
}
add_action( 'init', 'wp_rest_api_huiyan_cleanup_head' );

// 移除WordPress默认小工具
function wp_rest_api_huiyan_remove_default_widgets() {
    unregister_widget( 'WP_Widget_Pages' );
    unregister_widget( 'WP_Widget_Calendar' );
    unregister_widget( 'WP_Widget_Archives' );
    unregister_widget( 'WP_Widget_Links' );
    unregister_widget( 'WP_Widget_Meta' );
    unregister_widget( 'WP_Widget_Search' );
    unregister_widget( 'WP_Widget_Text' );
    unregister_widget( 'WP_Widget_Categories' );
    unregister_widget( 'WP_Widget_Recent_Posts' );
    unregister_widget( 'WP_Widget_Recent_Comments' );
    unregister_widget( 'WP_Widget_RSS' );
    unregister_widget( 'WP_Widget_Tag_Cloud' );
    unregister_widget( 'WP_Nav_Menu_Widget' );
}
add_action( 'widgets_init', 'wp_rest_api_huiyan_remove_default_widgets' );

// 禁用XML-RPC功能
function wp_rest_api_huiyan_disable_xmlrpc() {
    add_filter( 'xmlrpc_enabled', '__return_false' );
    remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'template_redirect', 'rest_output_link_header' );
}
add_action( 'init', 'wp_rest_api_huiyan_disable_xmlrpc' );

// 自定义错误页面（仅针对前端）
function wp_rest_api_huiyan_custom_error_page() {
    if ( ! is_admin() && ! is_rest_request() ) {
        status_header( 403 );
        include( get_template_directory() . '/index.php' );
        exit;
    }
}
add_action( 'wp', 'wp_rest_api_huiyan_custom_error_page' );

// 添加API状态检查端点
function wp_rest_api_huiyan_register_status_endpoint() {
    register_rest_route( 'wp-rest-api-huiyan/v1', '/status', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_api_huiyan_status_callback',
        'permission_callback' => '__return_true', // 公开访问
    ));
}
add_action( 'rest_api_init', 'wp_rest_api_huiyan_register_status_endpoint' );

// API状态检查回调函数
function wp_rest_api_huiyan_status_callback() {
    return array(
        'status' => 'ok',
        'version' => WP_REST_API_HUIYAN_VERSION,
        'message' => 'WP REST API by Huiyan is running',
        'timestamp' => current_time( 'timestamp' ),
    );
}

// 移除自动更新通知
function wp_rest_api_huiyan_disable_update_notifications() {
    remove_action( 'admin_notices', 'update_nag', 3 );
}
add_action( 'admin_init', 'wp_rest_api_huiyan_disable_update_notifications' );
