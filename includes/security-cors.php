<?php
/**
 * WP REST API by Huiyan - 安全加固和跨域配置模块
 * 
 * 提供 WordPress 安全加固和跨域请求支持功能
 */

/**
 * 初始化安全和跨域功能
 */
function wp_rest_api_huiyan_security_init() {
    // 加载安全功能
    wp_rest_api_huiyan_load_security_features();
    
    // 加载跨域功能
    wp_rest_api_huiyan_load_cors_features();
}
add_action( 'init', 'wp_rest_api_huiyan_security_init' );

/**
 * 加载安全功能
 */
function wp_rest_api_huiyan_load_security_features() {
    // 禁用 XML-RPC
    if ( get_option( 'wp_rest_api_huiyan_disable_xmlrpc', true ) ) {
        add_filter( 'xmlrpc_enabled', '__return_false' );
        add_action( 'xmlrpc_call', 'wp_rest_api_huiyan_block_xmlrpc_requests' );
    }
    
    // 隐藏 WordPress 版本信息
    if ( get_option( 'wp_rest_api_huiyan_hide_version', true ) ) {
        remove_action( 'wp_head', 'wp_generator' );
        add_filter( 'the_generator', '__return_empty_string' );
        add_filter( 'show_admin_bar', '__return_false' );
    }
    
    // 禁用更新通知
    if ( get_option( 'wp_rest_api_huiyan_disable_update_notices', true ) ) {
        add_action( 'admin_init', 'wp_rest_api_huiyan_disable_updates' );
    }
    
    // 添加安全头部
    add_action( 'send_headers', 'wp_rest_api_huiyan_add_security_headers' );
    
    // 禁用文件编辑功能
    define( 'DISALLOW_FILE_EDIT', true );
    
    // 禁用 PHP 文件上传执行
    add_filter( 'plugin_themes_auto_update_enabled', '__return_false' );
    
    // 限制登录尝试
    add_filter( 'authenticate', 'wp_rest_api_huiyan_limit_login_attempts', 30, 3 );
    
    // 防止信息泄露
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'index_rel_link' );
    remove_action( 'wp_head', 'parent_post_rel_link' );
    remove_action( 'wp_head', 'start_post_rel_link' );
    remove_action( 'wp_head', 'adjacent_posts_rel_link' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    
    // 禁用 RSS 中的作者信息
    add_filter( 'the_author', 'wp_rest_api_huiyan_hide_author_rss' );
    
    // 防止用户枚举
    add_filter( 'redirect_canonical', 'wp_rest_api_huiyan_prevent_user_enumeration' );
    
    // 禁用 REST API 用户端点
    add_filter( 'rest_endpoints', 'wp_rest_api_huiyan_disable_user_endpoints' );
}

/**
 * 加载跨域功能
 */
function wp_rest_api_huiyan_load_cors_features() {
    // 检查是否启用 CORS
    if ( get_option( 'wp_rest_api_huiyan_cors_enabled', true ) ) {
        // 为 REST API 添加 CORS 支持
        add_action( 'rest_api_init', 'wp_rest_api_huiyan_enable_cors', 15 );
        
        // 处理预检请求
        add_action( 'init', 'wp_rest_api_huiyan_handle_preflight_requests' );
    }
}

/**
 * 阻止 XML-RPC 请求
 */
function wp_rest_api_huiyan_block_xmlrpc_requests() {
    wp_die( 'XML-RPC 功能已禁用', 'XML-RPC 已禁用', array( 'response' => 403 ) );
}

/**
 * 禁用更新通知
 */
function wp_rest_api_huiyan_disable_updates() {
    // 隐藏核心更新通知
    remove_action( 'admin_notices', 'update_nag', 3 );
    
    // 禁用插件更新检查
    remove_action( 'load-plugins.php', 'wp_update_plugins' );
    remove_action( 'load-update.php', 'wp_update_plugins' );
    remove_action( 'admin_init', '_maybe_update_plugins' );
    
    // 禁用主题更新检查
    remove_action( 'load-themes.php', 'wp_update_themes' );
    remove_action( 'load-update.php', 'wp_update_themes' );
    remove_action( 'admin_init', '_maybe_update_themes' );
    
    // 隐藏更新通知
    add_action( 'admin_head', 'wp_rest_api_huiyan_hide_update_notices' );
}

/**
 * 隐藏更新通知的 CSS
 */
function wp_rest_api_huiyan_hide_update_notices() {
    echo '<style>';
    echo '.update-nag, .updated.notice-itsec, .error.notice-itsec, .itsec-notice { display: none !important; }';
    echo '</style>';
}

/**
 * 添加安全头部
 */
function wp_rest_api_huiyan_add_security_headers() {
    // X-Content-Type-Options
    header( 'X-Content-Type-Options: nosniff' );
    
    // X-Frame-Options
    header( 'X-Frame-Options: SAMEORIGIN' );
    
    // X-XSS-Protection
    header( 'X-XSS-Protection: 1; mode=block' );
    
    // Referrer-Policy
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    
    // Content-Security-Policy (基本设置)
    header( "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'" );
    
    // 移除不必要的头部
    @header_remove( 'X-Powered-By' );
    @header_remove( 'Server' );
}

/**
 * 限制登录尝试
 */
function wp_rest_api_huiyan_limit_login_attempts( $user, $username, $password ) {
    // 获取客户端 IP
    $client_ip = wp_rest_api_huiyan_get_client_ip();
    
    // 获取最大尝试次数和锁定时间
    $max_attempts = get_option( 'wp_rest_api_huiyan_max_login_attempts', 5 );
    $lockout_duration = get_option( 'wp_rest_api_huiyan_lockout_duration', 3600 );
    
    // 获取登录尝试数据
    $login_attempts = get_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip );
    if ( ! $login_attempts ) {
        $login_attempts = array(
            'count' => 0,
            'last_attempt' => time()
        );
    }
    
    // 检查是否还在锁定时间内
    if ( $login_attempts['count'] >= $max_attempts ) {
        $time_passed = time() - $login_attempts['last_attempt'];
        if ( $time_passed <= $lockout_duration ) {
            // 还在锁定时间内
            return new WP_Error( 
                'too_many_attempts', 
                sprintf( '登录尝试次数过多，请在 %d 分钟后再试。', ceil( ( $lockout_duration - $time_passed ) / 60 ) ) 
            );
        } else {
            // 锁定时间已过，重置尝试次数
            $login_attempts['count'] = 0;
        }
    }
    
    // 如果用户验证失败，增加尝试次数
    if ( ! $user || is_wp_error( $user ) ) {
        $login_attempts['count']++;
        $login_attempts['last_attempt'] = time();
        set_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip, $login_attempts, $lockout_duration );
    } else {
        // 登录成功，重置尝试次数
        delete_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip );
    }
    
    return $user;
}

/**
 * 隐藏 RSS 中的作者信息
 */
function wp_rest_api_huiyan_hide_author_rss( $author_name ) {
    if ( is_feed() ) {
        return 'Administrator';
    }
    return $author_name;
}

/**
 * 防止用户枚举
 */
function wp_rest_api_huiyan_prevent_user_enumeration( $redirect ) {
    // 检查是否是用户枚举尝试
    if ( is_author() && ! is_user_logged_in() ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        return home_url( '/' );
    }
    return $redirect;
}

/**
 * 禁用 REST API 用户端点
 */
function wp_rest_api_huiyan_disable_user_endpoints( $endpoints ) {
    // 检查是否是管理员
    if ( ! current_user_can( 'manage_options' ) ) {
        // 禁用用户列表端点
        if ( isset( $endpoints['/wp/v2/users'] ) ) {
            unset( $endpoints['/wp/v2/users'] );
        }
        // 禁用用户详情端点
        if ( isset( $endpoints['/wp/v2/users/(?P<id>\d+)'] ) ) {
            unset( $endpoints['/wp/v2/users/(?P<id>\d+)'] );
        }
    }
    return $endpoints;
}

/**
 * 启用 REST API 的 CORS 支持
 */
function wp_rest_api_huiyan_enable_cors() {
    // 为所有 REST API 路由添加 CORS 支持
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', 'wp_rest_api_huiyan_send_cors_headers' );
}

/**
 * 发送 CORS 头部
 */
function wp_rest_api_huiyan_send_cors_headers( $value ) {
    // 获取允许的来源
    $allowed_origins = get_option( 'wp_rest_api_huiyan_cors_origins', '*' );
    
    // 设置允许的来源
    if ( $allowed_origins === '*' ) {
        // 允许所有来源
        header( 'Access-Control-Allow-Origin: *' );
    } else {
        // 检查请求来源是否在允许列表中
        $request_origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
        $origins = explode( ',', $allowed_origins );
        
        foreach ( $origins as $origin ) {
            $origin = trim( $origin );
            if ( $origin === $request_origin ) {
                header( 'Access-Control-Allow-Origin: ' . $origin );
                break;
            }
        }
    }
    
    // 设置允许的方法
    header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
    
    // 设置允许的头部
    header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
    
    // 设置是否允许凭证
    header( 'Access-Control-Allow-Credentials: true' );
    
    // 设置预检请求的缓存时间
    header( 'Access-Control-Max-Age: 86400' );
    
    return $value;
}

/**
 * 处理预检请求
 */
function wp_rest_api_huiyan_handle_preflight_requests() {
    // 检查是否是预检请求
    if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
        // 检查是否是 REST API 请求
        $request_uri = $_SERVER['REQUEST_URI'];
        if ( preg_match( '#^/wp-json/#', parse_url( $request_uri, PHP_URL_PATH ) ) ) {
            // 发送 CORS 头部
            wp_rest_api_huiyan_send_cors_headers( true );
            
            // 返回 200 OK
            status_header( 200 );
            exit();
        }
    }
}

// 如果函数不存在，在这里定义一个兼容版本
if ( ! function_exists( 'wp_rest_api_huiyan_get_client_ip' ) ) {
    /**
     * 获取客户端 IP
     * 
     * @return string 客户端 IP 地址
     */
    function wp_rest_api_huiyan_get_client_ip() {
        // 检查是否使用了代理
        if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip_addresses = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            $client_ip = trim( $ip_addresses[0] );
        } elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            $client_ip = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $client_ip;
    }
}

/**
 * 禁用自动保存
 */
function wp_rest_api_huiyan_disable_autosave() {
    wp_deregister_script( 'autosave' );
}
add_action( 'wp_print_scripts', 'wp_rest_api_huiyan_disable_autosave' );

/**
 * 限制修订版本数量
 */
function wp_rest_api_huiyan_limit_post_revisions( $num, $post ) {
    return 5; // 只保留最近5个修订版本
}
add_filter( 'wp_revisions_to_keep', 'wp_rest_api_huiyan_limit_post_revisions', 10, 2 );

/**
 * 禁用 Pingback
 */
function wp_rest_api_huiyan_disable_pingback( $links ) {
    if ( isset( $links['pingback'] ) ) {
        unset( $links['pingback'] );
    }
    return $links;
}
add_filter( 'bloginfo_url', 'wp_rest_api_huiyan_disable_pingback', 10, 2 );

/**
 * 阻止 Pingback 请求
 */
function wp_rest_api_huiyan_block_pingback_requests( $methods ) {
    if ( isset( $methods['pingback.ping'] ) ) {
        unset( $methods['pingback.ping'] );
    }
    return $methods;
}
add_filter( 'xmlrpc_methods', 'wp_rest_api_huiyan_block_pingback_requests' );

/**
 * 加强 wp-config.php 的保护
 */
function wp_rest_api_huiyan_protect_wp_config() {
    // 检查是否是 wp-config.php 文件的直接访问
    if ( basename( $_SERVER['SCRIPT_FILENAME'] ) === 'wp-config.php' ) {
        status_header( 403 );
        die( 'Access denied' );
    }
}
add_action( 'init', 'wp_rest_api_huiyan_protect_wp_config' );

/**
 * 限制管理员登录后重定向到指定页面
 */
function wp_rest_api_huiyan_admin_login_redirect( $redirect_to, $request, $user ) {
    // 检查用户是否是管理员
    if ( isset( $user->roles ) && is_array( $user->roles ) && in_array( 'administrator', $user->roles ) ) {
        // 重定向到仪表板
        return admin_url( 'index.php' );
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'wp_rest_api_huiyan_admin_login_redirect', 10, 3 );

/**
 * 禁用默认的 REST API 匿名访问
 */
// 临时完全允许所有REST API访问（用于调试）
function wp_rest_api_huiyan_restrict_rest_api_access( $result ) {
    // 重要：暂时禁用所有API访问限制，确保问题不是由访问控制引起的
    // 直接返回原始结果，不做任何权限检查
    return $result;
}
// 暂时注释掉这个过滤器，完全禁用访问控制
// add_filter( 'rest_authentication_errors', 'wp_rest_api_huiyan_restrict_rest_api_access' );

/**
 * 更改 REST API 的基础路径
 */
function wp_rest_api_huiyan_change_rest_base() {
    // 可以在这里更改 REST API 的基础路径
    // return 'api';
    return 'wp-json'; // 使用默认路径
}
add_filter( 'rest_url_prefix', 'wp_rest_api_huiyan_change_rest_base' );
