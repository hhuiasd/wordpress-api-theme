<?php
/**
 * WP REST API by Huiyan - API状态检查
 * 
 * 提供API系统状态检查功能，监控WordPress REST API健康状态
 */

// 防止直接访问
if ( ! defined( 'ABSPATH' ) ) {
    die();
}

/**
 * 初始化API状态检查功能
 */
function wp_rest_api_huiyan_init_status_check() {
    // 注册API状态检查端点
    add_action( 'rest_api_init', 'wp_rest_api_huiyan_register_status_endpoints' );
    
    // 添加状态检查管理页面
    add_action( 'admin_menu', 'wp_rest_api_huiyan_add_status_page' );
}

/**
 * 注册API状态检查端点
 */
function wp_rest_api_huiyan_register_status_endpoints() {
    // 公共状态检查端点（不需要认证）
    register_rest_route( 'wp-rest-api-huiyan/v1', '/status', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_api_huiyan_get_status_callback',
        'permission_callback' => '__return_true',
    ));
    
    // 详细状态检查端点（需要管理员权限）
    register_rest_route( 'wp-rest-api-huiyan/v1', '/status/detailed', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_api_huiyan_get_detailed_status_callback',
        'permission_callback' => 'wp_rest_api_huiyan_check_admin_permission',
    ));
    
    // 系统信息端点（需要管理员权限）
    register_rest_route( 'wp-rest-api-huiyan/v1', '/system/info', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_api_huiyan_get_system_info_callback',
        'permission_callback' => 'wp_rest_api_huiyan_check_admin_permission',
    ));
    
    // 清理缓存端点（需要管理员权限）
    register_rest_route( 'wp-rest-api-huiyan/v1', '/system/clean-cache', array(
        'methods' => 'POST',
        'callback' => 'wp_rest_api_huiyan_clean_cache_callback',
        'permission_callback' => 'wp_rest_api_huiyan_check_admin_permission',
    ));
}

/**
 * 获取基本状态信息回调
 */
function wp_rest_api_huiyan_get_status_callback( $request ) {
    $status = array(
        'status' => 'ok',
        'timestamp' => current_time( 'timestamp' ),
        'time' => current_time( 'mysql' ),
        'version' => WP_REST_API_HUIYAN_VERSION,
        'api_available' => true,
    );
    
    return new WP_REST_Response( $status, 200 );
}

/**
 * 获取详细状态信息回调
 */
function wp_rest_api_huiyan_get_detailed_status_callback( $request ) {
    global $wpdb;
    
    // 检查核心组件状态
    $status = array(
        'status' => 'ok',
        'timestamp' => current_time( 'timestamp' ),
        'time' => current_time( 'mysql' ),
        'version' => WP_REST_API_HUIYAN_VERSION,
        'wordpress_version' => get_bloginfo( 'version' ),
        'components' => array(
            'rest_api' => wp_rest_api_huiyan_check_rest_api(),
            'jwt_auth' => wp_rest_api_huiyan_check_jwt_auth(),
            'cache' => wp_rest_api_huiyan_check_cache(),
            'cors' => wp_rest_api_huiyan_check_cors(),
            'security' => wp_rest_api_huiyan_check_security(),
        ),
        'performance' => array(
            'memory_usage' => wp_rest_api_huiyan_get_memory_usage(),
            'database_size' => wp_rest_api_huiyan_get_database_size( $wpdb ),
            'cache_stats' => wp_rest_api_huiyan_get_file_cache_stats(),
        ),
        'config' => array(
            'debug_mode' => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'cache_enabled' => get_option( 'wp_rest_api_huiyan_cache_enabled', true ),
            'jwt_enabled' => get_option( 'wp_rest_api_huiyan_jwt_enabled', false ),
            'cors_enabled' => get_option( 'wp_rest_api_huiyan_cors_enabled', true ),
        ),
    );
    
    // 检查是否有任何组件出现问题
    foreach ( $status['components'] as $component => $component_status ) {
        if ( $component_status['status'] !== 'ok' ) {
            $status['status'] = 'warning';
        }
    }
    
    return new WP_REST_Response( $status, 200 );
}

/**
 * 获取系统信息回调
 */
function wp_rest_api_huiyan_get_system_info_callback( $request ) {
    global $wpdb;
    
    // 基本系统信息
    $system_info = array(
        'wordpress' => array(
            'version' => get_bloginfo( 'version' ),
            'language' => get_bloginfo( 'language' ),
            'multisite' => is_multisite() ? '是' : '否',
            'home_url' => home_url(),
            'site_url' => site_url(),
            'theme' => wp_get_theme()->get( 'Name' ) . ' ' . wp_get_theme()->get( 'Version' ),
        ),
        'server' => array(
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'php_post_max_size' => ini_get( 'post_max_size' ),
            'php_max_execution_time' => ini_get( 'max_execution_time' ),
            'php_max_input_vars' => ini_get( 'max_input_vars' ),
            'memory_limit' => ini_get( 'memory_limit' ),
            'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
        ),
        'database' => array(
            'version' => $wpdb->db_version(),
            'name' => DB_NAME,
            'prefix' => $wpdb->prefix,
            'size' => wp_rest_api_huiyan_get_database_size( $wpdb ),
        ),
        'active_plugins' => wp_rest_api_huiyan_get_active_plugins(),
        'api_theme_settings' => wp_rest_api_huiyan_get_theme_settings(),
    );
    
    return new WP_REST_Response( $system_info, 200 );
}

/**
 * 清理缓存回调
 */
function wp_rest_api_huiyan_clean_cache_callback( $request ) {
    // 清理API缓存
    if ( function_exists( 'wp_rest_api_huiyan_clean_all_cache' ) ) {
        wp_rest_api_huiyan_clean_all_cache();
    }
    
    // 清理对象缓存
    if ( function_exists( 'wp_rest_api_huiyan_clear_rest_cache' ) ) {
        wp_rest_api_huiyan_clear_rest_cache();
    }
    
    // 清理WordPress缓存
    wp_cache_flush();
    
    return new WP_REST_Response( array(
        'success' => true,
        'message' => '所有缓存已清理',
        'timestamp' => current_time( 'timestamp' ),
    ), 200 );
}

/**
 * 检查REST API状态
 */
function wp_rest_api_huiyan_check_rest_api() {
    $status = array(
        'status' => 'ok',
        'available' => true,
        'endpoints' => count( rest_get_server()->get_routes() ),
        'error' => null,
    );
    
    // 尝试访问一个简单的REST API端点
    $response = wp_remote_get( rest_url( 'wp/v2/posts?per_page=1' ), array(
        'timeout' => 5,
        'sslverify' => false,
    ));
    
    if ( is_wp_error( $response ) ) {
        $status['status'] = 'error';
        $status['available'] = false;
        $status['error'] = $response->get_error_message();
    }
    
    return $status;
}

/**
 * 检查JWT认证状态
 */
function wp_rest_api_huiyan_check_jwt_auth() {
    $enabled = get_option( 'wp_rest_api_huiyan_jwt_enabled', false );
    $secret_key = get_option( 'wp_rest_api_huiyan_jwt_secret', '' );
    
    $status = array(
        'status' => 'ok',
        'enabled' => $enabled,
        'configured' => $enabled && ! empty( $secret_key ),
        'error' => null,
    );
    
    if ( $enabled && empty( $secret_key ) ) {
        $status['status'] = 'warning';
        $status['error'] = 'JWT已启用但未配置密钥';
    }
    
    return $status;
}

/**
 * 检查缓存状态
 */
function wp_rest_api_huiyan_check_cache() {
    $enabled = get_option( 'wp_rest_api_huiyan_cache_enabled', true );
    $cache_dir = WP_REST_API_HUIYAN_CACHE_DIR;
    $cache_dir_writable = is_writable( $cache_dir );
    
    $status = array(
        'status' => 'ok',
        'enabled' => $enabled,
        'cache_dir' => $cache_dir,
        'cache_dir_writable' => $cache_dir_writable,
        'error' => null,
    );
    
    if ( $enabled && ! $cache_dir_writable ) {
        $status['status'] = 'error';
        $status['error'] = '缓存目录不可写';
    }
    
    return $status;
}

/**
 * 检查CORS状态
 */
function wp_rest_api_huiyan_check_cors() {
    $enabled = get_option( 'wp_rest_api_huiyan_cors_enabled', true );
    $origins = get_option( 'wp_rest_api_huiyan_cors_origins', '*' );
    
    $status = array(
        'status' => 'ok',
        'enabled' => $enabled,
        'origins' => $origins,
        'error' => null,
    );
    
    return $status;
}

/**
 * 检查安全状态
 */
function wp_rest_api_huiyan_check_security() {
    $xmlrpc_disabled = get_option( 'wp_rest_api_huiyan_disable_xmlrpc', true );
    $restricted_access = get_option( 'wp_rest_api_huiyan_restrict_access', true );
    
    $status = array(
        'status' => 'ok',
        'xmlrpc_disabled' => $xmlrpc_disabled,
        'restricted_access' => $restricted_access,
        'error' => null,
    );
    
    return $status;
}

/**
 * 获取内存使用情况
 */
function wp_rest_api_huiyan_get_memory_usage() {
    $usage = memory_get_usage( true ) / 1024 / 1024; // 转换为MB
    $peak_usage = memory_get_peak_usage( true ) / 1024 / 1024; // 转换为MB
    $limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) ) / 1024 / 1024; // 转换为MB
    
    return array(
        'current' => round( $usage, 2 ) . ' MB',
        'peak' => round( $peak_usage, 2 ) . ' MB',
        'limit' => round( $limit, 2 ) . ' MB',
        'percentage' => round( ( $usage / $limit ) * 100, 2 ) . '%',
    );
}

/**
 * 获取数据库大小
 */
function wp_rest_api_huiyan_get_database_size( $wpdb ) {
    $sql = "SELECT table_schema AS 'database', 
            SUM(data_length + index_length) / 1024 / 1024 AS 'size' 
            FROM information_schema.TABLES 
            WHERE table_schema = '" . DB_NAME . "' 
            GROUP BY table_schema;";
    
    $result = $wpdb->get_row( $sql );
    
    if ( $result && isset( $result->size ) ) {
        return round( $result->size, 2 ) . ' MB';
    }
    
    return '未知';
}

/**
 * 获取激活的插件列表
 */
function wp_rest_api_huiyan_get_active_plugins() {
    $active_plugins = get_option( 'active_plugins', array() );
    $plugins = array();
    
    foreach ( $active_plugins as $plugin ) {
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
        $plugins[] = array(
            'name' => $plugin_data['Name'],
            'version' => $plugin_data['Version'],
            'author' => $plugin_data['Author'],
        );
    }
    
    return $plugins;
}

/**
 * 获取主题设置
 */
function wp_rest_api_huiyan_get_theme_settings() {
    $settings = array(
        'version' => WP_REST_API_HUIYAN_VERSION,
        'cache_enabled' => get_option( 'wp_rest_api_huiyan_cache_enabled', true ),
        'cache_ttl' => get_option( 'wp_rest_api_huiyan_cache_ttl', 3600 ),
        'jwt_enabled' => get_option( 'wp_rest_api_huiyan_jwt_enabled', false ),
        'cors_enabled' => get_option( 'wp_rest_api_huiyan_cors_enabled', true ),
        'cors_origins' => get_option( 'wp_rest_api_huiyan_cors_origins', '*' ),
        'disable_xmlrpc' => get_option( 'wp_rest_api_huiyan_disable_xmlrpc', true ),
        'restrict_access' => get_option( 'wp_rest_api_huiyan_restrict_access', true ),
        'remove_generator' => get_option( 'wp_rest_api_huiyan_remove_generator', true ),
    );
    
    return $settings;
}

/**
 * 添加状态检查管理页面
 */
function wp_rest_api_huiyan_add_status_page() {
    add_submenu_page(
        'wp-rest-api-huiyan',
        'API 状态',
        'API 状态',
        'manage_options',
        'wp-rest-api-huiyan-status',
        'wp_rest_api_huiyan_status_page_callback'
    );
}

/**
 * 状态检查管理页面回调
 */
function wp_rest_api_huiyan_status_page_callback() {
    // 处理缓存清理操作
    if ( isset( $_POST['wp_rest_api_huiyan_clean_cache'] ) && wp_verify_nonce( $_POST['wp_rest_api_huiyan_clean_cache_nonce'], 'wp_rest_api_huiyan_clean_cache' ) ) {
        // 清理API缓存
        if ( function_exists( 'wp_rest_api_huiyan_clean_all_cache' ) ) {
            wp_rest_api_huiyan_clean_all_cache();
        }
        
        // 清理对象缓存
        if ( function_exists( 'wp_rest_api_huiyan_clear_rest_cache' ) ) {
            wp_rest_api_huiyan_clear_rest_cache();
        }
        
        // 清理WordPress缓存
        wp_cache_flush();
        
        echo '<div class="notice notice-success is-dismissible"><p>所有缓存已成功清理</p></div>';
    }
    
    // 获取详细状态信息
    $status = wp_rest_api_huiyan_get_detailed_status_callback( null );
    $status_data = $status->get_data();
    
    // 获取系统信息
    $system_info = wp_rest_api_huiyan_get_system_info_callback( null );
    $system_data = $system_info->get_data();
    
    ?>
    <div class="wrap wp-rest-api-huiyan-status-page">
        <h1>WP REST API by Huiyan - 系统状态</h1>
        
        <!-- 操作按钮区域 - 固定在顶部 -->
        <div class="huiyan-action-bar">
            <form method="post" action="" class="huiyan-cache-form">
                <?php wp_nonce_field( 'wp_rest_api_huiyan_clean_cache', 'wp_rest_api_huiyan_clean_cache_nonce' ); ?>
                <input type="submit" name="wp_rest_api_huiyan_clean_cache" class="button button-primary" value="清理所有缓存">
            </form>
        </div>
        
        <!-- 选项卡导航 -->
        <div class="huiyan-tabs">
            <div class="huiyan-tab-nav">
                <button class="huiyan-tab-button active" data-tab="overview">总览</button>
                <button class="huiyan-tab-button" data-tab="components">组件状态</button>
                <button class="huiyan-tab-button" data-tab="performance">性能信息</button>
                <button class="huiyan-tab-button" data-tab="system">系统信息</button>
            </div>
            
            <!-- 总览选项卡 -->
            <div class="huiyan-tab-content active" id="tab-overview">
                <div class="huiyan-status-grid">
                    <!-- 状态卡片 -->
                    <div class="huiyan-status-card">
                        <h3>当前状态</h3>
                        <div class="huiyan-status-value">
                            <span class="status-indicator 
                                    <?php echo esc_attr($status_data['status'] === 'ok' ? 'status-ok' : 'status-warning'); ?>">
                                <?php echo $status_data['status'] === 'ok' ? '正常' : '警告'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- 版本卡片 -->
                    <div class="huiyan-status-card">
                        <h3>主题版本</h3>
                        <div class="huiyan-status-value">
                            <?php echo $status_data['version']; ?>
                        </div>
                    </div>
                    
                    <!-- WordPress版本卡片 -->
                    <div class="huiyan-status-card">
                        <h3>WordPress版本</h3>
                        <div class="huiyan-status-value">
                            <?php echo $status_data['wordpress_version']; ?>
                        </div>
                    </div>
                    
                    <!-- 检查时间卡片 -->
                    <div class="huiyan-status-card">
                        <h3>检查时间</h3>
                        <div class="huiyan-status-value">
                            <?php echo date( 'Y-m-d H:i:s', $status_data['timestamp'] ); ?>
                        </div>
                    </div>
                </div>
                
                <!-- 快速组件状态概览 -->
                <div class="huiyan-section">
                    <h2 class="huiyan-section-title">组件状态概览</h2>
                    <div class="huiyan-components-overview">
                        <?php foreach ( $status_data['components'] as $component_name => $component_status ) : ?>
                            <div class="huiyan-component-indicator">
                                <span class="component-name"><?php echo ucfirst( str_replace( '_', ' ', $component_name ) ); ?></span>
                                <span class="status-indicator 
                                        <?php echo esc_attr($component_status['status'] === 'ok' ? 'status-ok' : 'status-warning'); ?>">
                                    <?php echo $component_status['status'] === 'ok' ? '正常' : '警告'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- 组件状态选项卡 -->
            <div class="huiyan-tab-content" id="tab-components">
                <div class="huiyan-section">
                    <div class="inside">
                        <table class="widefat huiyan-status-table" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>组件</th>
                                    <th>状态</th>
                                    <th>详情</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $status_data['components'] as $component_name => $component_status ) : ?>
                                    <tr class="<?php echo $component_status['status'] === 'ok' ? 'status-ok' : 'status-warning'; ?>">
                                        <td><?php echo ucfirst( str_replace( '_', ' ', $component_name ) ); ?></td>
                                        <td>
                                            <span class="status-indicator 
                                                <?php echo esc_attr($component_status['status'] === 'ok' ? 'status-ok' : 'status-warning'); ?>">
                                                <?php echo $component_status['status'] === 'ok' ? '正常' : '警告'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ( $component_status['error'] ) : ?>
                                                <strong>错误:</strong> <?php echo $component_status['error']; ?>
                                            <?php else : ?>
                                                <?php 
                                                switch ( $component_name ) {
                                                    case 'rest_api':
                                                        echo '可用，注册了 ' . $component_status['endpoints'] . ' 个端点';
                                                        break;
                                                    case 'jwt_auth':
                                                        echo $component_status['enabled'] ? 
                                                            ( $component_status['configured'] ? '已启用且已配置' : '已启用但未配置密钥' ) : 
                                                            '已禁用';
                                                        break;
                                                    case 'cache':
                                                        echo $component_status['enabled'] ? 
                                                            ( $component_status['cache_dir_writable'] ? '已启用且缓存目录可写' : '已启用但缓存目录不可写' ) : 
                                                            '已禁用';
                                                        break;
                                                    case 'cors':
                                                        echo $component_status['enabled'] ? 
                                                            '已启用，允许来源: ' . $component_status['origins'] : 
                                                            '已禁用';
                                                        break;
                                                    case 'security':
                                                        echo 'XML-RPC: ' . ( $component_status['xmlrpc_disabled'] ? '已禁用' : '已启用' ) . 
                                                            ', 访问限制: ' . ( $component_status['restricted_access'] ? '已启用' : '已禁用' );
                                                        break;
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 性能信息选项卡 -->
            <div class="huiyan-tab-content" id="tab-performance">
                <div class="huiyan-status-grid">
                    <!-- 内存使用卡片 -->
                    <div class="huiyan-status-card">
                        <h3>内存使用</h3>
                        <div class="huiyan-status-value">
                            <?php echo $status_data['performance']['memory_usage']['current']; ?> / <?php echo $status_data['performance']['memory_usage']['limit']; ?>
                        </div>
                        <div class="huiyan-status-secondary">
                            (<?php echo $status_data['performance']['memory_usage']['percentage']; ?>)
                        </div>
                    </div>
                    
                    <!-- 数据库大小卡片 -->
                    <div class="huiyan-status-card">
                        <h3>数据库大小</h3>
                        <div class="huiyan-status-value">
                            <?php echo $status_data['performance']['database_size']; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 系统信息选项卡 -->
            <div class="huiyan-tab-content" id="tab-system">
                <div class="huiyan-system-info">
                    <!-- WordPress信息 -->
                    <div class="huiyan-info-section">
                        <h3>WordPress信息</h3>
                        <table class="widefat huiyan-info-table" cellspacing="0">
                            <tbody>
                                <tr><td>版本:</td><td><?php echo $system_data['wordpress']['version']; ?></td></tr>
                                <tr><td>语言:</td><td><?php echo $system_data['wordpress']['language']; ?></td></tr>
                                <tr><td>多站点:</td><td><?php echo $system_data['wordpress']['multisite']; ?></td></tr>
                                <tr><td>Home URL:</td><td><?php echo $system_data['wordpress']['home_url']; ?></td></tr>
                                <tr><td>Site URL:</td><td><?php echo $system_data['wordpress']['site_url']; ?></td></tr>
                                <tr><td>主题:</td><td><?php echo $system_data['wordpress']['theme']; ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 服务器信息 -->
                    <div class="huiyan-info-section">
                        <h3>服务器信息</h3>
                        <table class="widefat huiyan-info-table" cellspacing="0">
                            <tbody>
                                <tr><td>PHP版本:</td><td><?php echo $system_data['server']['php_version']; ?></td></tr>
                                <tr><td>服务器软件:</td><td><?php echo $system_data['server']['server_software']; ?></td></tr>
                                <tr><td>Post Max Size:</td><td><?php echo $system_data['server']['php_post_max_size']; ?></td></tr>
                                <tr><td>最大执行时间:</td><td><?php echo $system_data['server']['php_max_execution_time']; ?>秒</td></tr>
                                <tr><td>Max Input Vars:</td><td><?php echo $system_data['server']['php_max_input_vars']; ?></td></tr>
                                <tr><td>内存限制:</td><td><?php echo $system_data['server']['memory_limit']; ?></td></tr>
                                <tr><td>最大上传文件:</td><td><?php echo $system_data['server']['upload_max_filesize']; ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 数据库信息 -->
                    <div class="huiyan-info-section">
                        <h3>数据库信息</h3>
                        <table class="widefat huiyan-info-table" cellspacing="0">
                            <tbody>
                                <tr><td>版本:</td><td><?php echo $system_data['database']['version']; ?></td></tr>
                                <tr><td>名称:</td><td><?php echo $system_data['database']['name']; ?></td></tr>
                                <tr><td>前缀:</td><td><?php echo $system_data['database']['prefix']; ?></td></tr>
                                <tr><td>大小:</td><td><?php echo $system_data['database']['size']; ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 激活的插件 -->
                    <div class="huiyan-info-section">
                        <h3>激活的插件 (<?php echo count( $system_data['active_plugins'] ); ?>)</h3>
                        <div class="huiyan-plugins-list">
                            <?php foreach ( $system_data['active_plugins'] as $plugin ) : ?>
                                <div class="huiyan-plugin-item">
                                    <span class="plugin-name"><?php echo $plugin['name']; ?></span>
                                    <span class="plugin-version">(<?php echo $plugin['version']; ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            /* 基础样式 */
            .wp-rest-api-huiyan-status-page {
                margin-top: 20px;
            }
            
            /* 操作栏样式 */
            .huiyan-action-bar {
                background: #f1f1f1;
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            
            .huiyan-cache-form {
                display: inline-block;
            }
            
            /* 选项卡样式 */
            .huiyan-tabs {
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .huiyan-tab-nav {
                display: flex;
                background: #f7f7f7;
                border-bottom: 1px solid #e5e5e5;
            }
            
            .huiyan-tab-button {
                padding: 12px 20px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #555;
                border-right: 1px solid #e5e5e5;
                transition: all 0.2s;
            }
            
            .huiyan-tab-button:last-child {
                border-right: none;
            }
            
            .huiyan-tab-button:hover {
                background: #f1f1f1;
                color: #0073aa;
            }
            
            .huiyan-tab-button.active {
                background: #fff;
                color: #0073aa;
                border-bottom: 2px solid #0073aa;
            }
            
            .huiyan-tab-content {
                display: none;
                padding: 20px;
                background: #fff;
            }
            
            .huiyan-tab-content.active {
                display: block;
            }
            
            /* 状态网格样式 */
            .huiyan-status-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .huiyan-status-card {
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                padding: 20px;
            }
            
            .huiyan-status-card h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #666;
                font-weight: normal;
            }
            
            .huiyan-status-value {
                font-size: 18px;
                font-weight: 600;
                color: #333;
            }
            
            .huiyan-status-secondary {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            
            /* 状态指示器样式 */
            .status-indicator {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 3px;
                font-weight: 500;
                font-size: 12px;
            }
            
            .status-ok {
                background-color: #d4edda;
                color: #155724;
            }
            
            .status-warning {
                background-color: #fff3cd;
                color: #856404;
            }
            
            /* 组件概览样式 */
            .huiyan-section {
                margin-bottom: 20px;
            }
            
            .huiyan-section-title {
                font-size: 16px;
                margin: 0 0 15px 0;
                color: #333;
            }
            
            .huiyan-components-overview {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .huiyan-component-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #f9f9f9;
                padding: 8px 12px;
                border-radius: 4px;
                border: 1px solid #e5e5e5;
                font-size: 13px;
            }
            
            /* 表格样式 */
            .huiyan-status-table,
            .huiyan-info-table {
                border-collapse: collapse;
                width: 100%;
                margin-bottom: 20px;
            }
            
            .huiyan-status-table th,
            .huiyan-info-table th,
            .huiyan-status-table td,
            .huiyan-info-table td {
                padding: 10px 15px;
                text-align: left;
                border-bottom: 1px solid #e5e5e5;
            }
            
            .huiyan-status-table th,
            .huiyan-info-table th {
                background: #f7f7f7;
                font-weight: 600;
                color: #333;
            }
            
            .huiyan-status-table tr:hover,
            .huiyan-info-table tr:hover {
                background: #f9f9f9;
            }
            
            /* 系统信息样式 */
            .huiyan-system-info {
                display: grid;
                gap: 20px;
            }
            
            .huiyan-info-section {
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                padding: 20px;
            }
            
            .huiyan-info-section h3 {
                margin: 0 0 15px 0;
                font-size: 16px;
                color: #333;
            }
            
            /* 插件列表样式 */
            .huiyan-plugins-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 10px;
            }
            
            .huiyan-plugin-item {
                background: #fff;
                padding: 10px;
                border-radius: 3px;
                border: 1px solid #e5e5e5;
                font-size: 13px;
            }
            
            .plugin-name {
                font-weight: 500;
                color: #333;
            }
            
            .plugin-version {
                color: #666;
                font-size: 12px;
            }
            
            /* 响应式设计 */
            @media (max-width: 768px) {
                .huiyan-status-grid {
                    grid-template-columns: 1fr;
                }
                
                .huiyan-tab-nav {
                    flex-wrap: wrap;
                }
                
                .huiyan-tab-button {
                    flex: 1;
                    min-width: 120px;
                    border-bottom: 1px solid #e5e5e5;
                }
                
                .huiyan-tab-button.active {
                    border-bottom: 2px solid #0073aa;
                }
                
                .huiyan-plugins-list {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        
        <script>
            // 选项卡切换功能
            document.addEventListener('DOMContentLoaded', function() {
                const tabButtons = document.querySelectorAll('.huiyan-tab-button');
                const tabContents = document.querySelectorAll('.huiyan-tab-content');
                
                tabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const tabId = this.getAttribute('data-tab');
                        
                        // 隐藏所有内容
                        tabContents.forEach(content => {
                            content.classList.remove('active');
                        });
                        
                        // 移除所有按钮的活动状态
                        tabButtons.forEach(btn => {
                            btn.classList.remove('active');
                        });
                        
                        // 显示当前选项卡内容
                        document.getElementById('tab-' + tabId).classList.add('active');
                        
                        // 激活当前按钮
                        this.classList.add('active');
                    });
                });
            });
        </script>
    </div>
    <?php
}

/**
 * 注册状态检查功能
 */
add_action( 'wp_rest_api_huiyan_init', 'wp_rest_api_huiyan_init_status_check' );
