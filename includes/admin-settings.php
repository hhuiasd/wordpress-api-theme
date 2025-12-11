<?php
/**
 * WP REST API by Huiyan - 管理页面设置模块
 * 
 * 提供主题的管理后台设置界面
 */

// 添加设置菜单
function wp_rest_api_huiyan_add_settings_menu() {
    // 添加主菜单
    add_menu_page(
        'WP REST API 设置',
        'WP REST API',
        'manage_options',
        'wp-rest-api-huiyan',
        'wp_rest_api_huiyan_settings_page',
        'dashicons-rest-api',
        80
    );
    
    // 添加子菜单 - 常规设置
    add_submenu_page(
        'wp-rest-api-huiyan',
        '常规设置',
        '常规设置',
        'manage_options',
        'wp-rest-api-huiyan',
        'wp_rest_api_huiyan_settings_page'
    );
    
    // 添加子菜单 - 系统状态
    add_submenu_page(
        'wp-rest-api-huiyan',
        '系统状态',
        '系统状态',
        'manage_options',
        'wp-rest-api-huiyan-status',
        'wp_rest_api_huiyan_status_page'
    );
}
add_action( 'admin_menu', 'wp_rest_api_huiyan_add_settings_menu' );

// 注册设置选项
function wp_rest_api_huiyan_register_settings() {
    // 注册API缓存设置
    register_setting(
        'wp_rest_api_huiyan_cache',
        'wp_rest_api_huiyan_cache_enabled',
        array('default' => true)
    );
    register_setting(
        'wp_rest_api_huiyan_cache',
        'wp_rest_api_huiyan_cache_duration',
        array('default' => 3600)
    );
    register_setting(
        'wp_rest_api_huiyan_cache',
        'wp_rest_api_huiyan_cache_dir',
        array('default' => WP_CONTENT_DIR . '/cache/wp-rest-api-huiyan')
    );
    
    // 注册JWT认证设置
    register_setting(
        'wp_rest_api_huiyan_jwt',
        'wp_rest_api_huiyan_jwt_enabled',
        array('default' => true)
    );
    register_setting(
        'wp_rest_api_huiyan_jwt',
        'wp_rest_api_huiyan_jwt_secret',
        array('default' => wp_generate_password(32, true, true))
    );
    register_setting(
        'wp_rest_api_huiyan_jwt',
        'wp_rest_api_huiyan_jwt_expiration',
        array('default' => 3600)
    );
    
    // 注册CORS设置
    register_setting(
        'wp_rest_api_huiyan_cors',
        'wp_rest_api_huiyan_cors_enabled',
        array('default' => true)
    );
    register_setting(
        'wp_rest_api_huiyan_cors',
        'wp_rest_api_huiyan_cors_origins',
        array('default' => '*')
    );
    
    // 注册安全设置
    register_setting(
        'wp_rest_api_huiyan_security',
        'wp_rest_api_huiyan_disable_xmlrpc',
        array('default' => true)
    );
    register_setting(
        'wp_rest_api_huiyan_security',
        'wp_rest_api_huiyan_hide_version',
        array('default' => true)
    );
    register_setting(
        'wp_rest_api_huiyan_security',
        'wp_rest_api_huiyan_disable_update_notices',
        array('default' => true)
    );
    register_setting(
        'wp_rest_api_huiyan_security',
        'wp_rest_api_huiyan_max_login_attempts',
        array('default' => 5)
    );
    register_setting(
        'wp_rest_api_huiyan_security',
        'wp_rest_api_huiyan_lockout_duration',
        array('default' => 3600)
    );
}
add_action( 'admin_init', 'wp_rest_api_huiyan_register_settings' );

// 设置页面回调函数
function wp_rest_api_huiyan_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP REST API by Huiyan 设置</h1>
        <div class="nav-tab-wrapper">
            <a href="#cache" class="nav-tab nav-tab-active" onclick="jQuery('.settings-tab').hide(); jQuery('#cache-tab').show(); jQuery('.nav-tab').removeClass('nav-tab-active'); jQuery(this).addClass('nav-tab-active'); return false;">缓存设置</a>
            <a href="#jwt" class="nav-tab" onclick="jQuery('.settings-tab').hide(); jQuery('#jwt-tab').show(); jQuery('.nav-tab').removeClass('nav-tab-active'); jQuery(this).addClass('nav-tab-active'); return false;">JWT认证</a>
            <a href="#cors" class="nav-tab" onclick="jQuery('.settings-tab').hide(); jQuery('#cors-tab').show(); jQuery('.nav-tab').removeClass('nav-tab-active'); jQuery(this).addClass('nav-tab-active'); return false;">跨域设置</a>
            <a href="#security" class="nav-tab" onclick="jQuery('.settings-tab').hide(); jQuery('#security-tab').show(); jQuery('.nav-tab').removeClass('nav-tab-active'); jQuery(this).addClass('nav-tab-active'); return false;">安全设置</a>
        </div>
        <!-- 缓存设置选项卡 -->
        <div id="cache-tab" class="settings-tab">
            <h2>API 缓存设置</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'wp_rest_api_huiyan_cache' ); ?>
                <?php do_settings_sections( 'wp_rest_api_huiyan_cache' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">启用 API 缓存</th>
                        <td>
                            <input type="checkbox" name="wp_rest_api_huiyan_cache_enabled" value="1" <?php checked( 1, get_option( 'wp_rest_api_huiyan_cache_enabled' ) ); ?> />
                            <p class="description">开启后将缓存 REST API GET 请求响应，提高性能。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">缓存持续时间（秒）</th>
                        <td>
                            <input type="number" name="wp_rest_api_huiyan_cache_duration" value="<?php echo esc_attr( get_option( 'wp_rest_api_huiyan_cache_duration', 3600 ) ); ?>" min="1" />
                            <p class="description">缓存有效时间，默认为 3600 秒（1小时）。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">缓存目录路径</th>
                        <td>
                            <input type="text" name="wp_rest_api_huiyan_cache_dir" value="<?php echo esc_attr( get_option( 'wp_rest_api_huiyan_cache_dir', WP_CONTENT_DIR . '/cache/wp-rest-api-huiyan' ) ); ?>" class="regular-text" />
                            <p class="description">缓存文件存储路径，确保 WordPress 有写入权限。</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <!-- JWT认证选项卡 -->
        <div id="jwt-tab" class="settings-tab" style="display:none;">
            <h2>JWT 认证设置</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'wp_rest_api_huiyan_jwt' ); ?>
                <?php do_settings_sections( 'wp_rest_api_huiyan_jwt' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">启用 JWT 认证</th>
                        <td>
                            <input type="checkbox" name="wp_rest_api_huiyan_jwt_enabled" value="1" <?php checked( 1, get_option( 'wp_rest_api_huiyan_jwt_enabled' ) ); ?> />
                            <p class="description">开启后将使用 JWT 令牌进行 API 认证。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">JWT 密钥</th>
                        <td>
                            <input type="text" name="wp_rest_api_huiyan_jwt_secret" value="<?php echo esc_attr( get_option( 'wp_rest_api_huiyan_jwt_secret' ) ); ?>" class="regular-text" />
                            <button type="button" class="button button-secondary" onclick="this.form.wp_rest_api_huiyan_jwt_secret.value = generateRandomString(32);">生成随机密钥</button>
                            <p class="description">用于签名 JWT 令牌的密钥，请勿泄露。修改后所有现有令牌将失效。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">令牌过期时间（秒）</th>
                        <td>
                            <input type="number" name="wp_rest_api_huiyan_jwt_expiration" value="<?php echo esc_attr( get_option( 'wp_rest_api_huiyan_jwt_expiration', 3600 ) ); ?>" min="1" />
                            <p class="description">JWT 令牌的有效期，默认为 3600 秒（1小时）。</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <!-- 跨域设置选项卡 -->
        <div id="cors-tab" class="settings-tab" style="display:none;">
            <h2>跨域（CORS）设置</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'wp_rest_api_huiyan_cors' ); ?>
                <?php do_settings_sections( 'wp_rest_api_huiyan_cors' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">启用 CORS 支持</th>
                        <td>
                            <input type="checkbox" name="wp_rest_api_huiyan_cors_enabled" value="1" <?php checked( 1, get_option( 'wp_rest_api_huiyan_cors_enabled' ) ); ?> />
                            <p class="description">开启后将允许跨域请求访问 API。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">允许的来源域名</th>
                        <td>
                            <input type="text" name="wp_rest_api_huiyan_cors_origins" value="<?php echo esc_attr( get_option( 'wp_rest_api_huiyan_cors_origins', '*' ) ); ?>" class="regular-text" />
                            <p class="description">允许访问 API 的域名列表，多个域名用逗号分隔。使用 * 表示允许所有来源（不推荐在生产环境使用）。</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <!-- 安全设置选项卡 -->
        <div id="security-tab" class="settings-tab" style="display:none;">
            <h2>安全设置</h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'wp_rest_api_huiyan_security' ); ?>
                <?php do_settings_sections( 'wp_rest_api_huiyan_security' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">禁用 XML-RPC</th>
                        <td>
                            <input type="checkbox" name="wp_rest_api_huiyan_disable_xmlrpc" value="1" <?php checked( 1, get_option( 'wp_rest_api_huiyan_disable_xmlrpc' ) ); ?> />
                            <p class="description">禁用 XML-RPC 功能，防止暴力攻击。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">隐藏 WordPress 版本信息</th>
                        <td>
                            <input type="checkbox" name="wp_rest_api_huiyan_hide_version" value="1" <?php checked( 1, get_option( 'wp_rest_api_huiyan_hide_version' ) ); ?> />
                            <p class="description">从页面头部和响应头移除 WordPress 版本信息。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">禁用更新提示</th>
                        <td>
                            <input type="checkbox" name="wp_rest_api_huiyan_disable_update_notices" value="1" <?php checked( 1, get_option( 'wp_rest_api_huiyan_disable_update_notices' ) ); ?> />
                            <p class="description">关闭 WordPress 更新通知。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">最大登录尝试次数</th>
                        <td>
                            <input type="number" name="wp_rest_api_huiyan_max_login_attempts" value="<?php echo esc_attr( get_option( 'wp_rest_api_huiyan_max_login_attempts', 5 ) ); ?>" min="1" />
                            <p class="description">锁定前允许的失败登录尝试次数。</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">锁定时间（秒）</th>
                        <td>
                            <input type="number" name="wp_rest_api_huiyan_lockout_duration" value="<?php echo esc_attr( get_option( 'wp_rest_api_huiyan_lockout_duration', 3600 ) ); ?>" min="1" />
                            <p class="description">登录失败后的 IP 锁定时间，默认为 3600 秒（1小时）。</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    <script type="text/javascript">
        function generateRandomString(length) {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=';
            var result = '';
            for (var i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }
    </script>
    <?php
}

// 系统状态页面回调函数
function wp_rest_api_huiyan_status_page() {
    // 获取系统信息
    global $wp_version;
    
    // 检查缓存目录
    $cache_dir = get_option('wp_rest_api_huiyan_cache_dir', WP_CONTENT_DIR . '/cache/wp-rest-api-huiyan');
    $cache_dir_exists = file_exists($cache_dir);
    $cache_dir_writable = $cache_dir_exists && is_writable($cache_dir);
    
    // 检查JWT密钥
    $jwt_secret = get_option('wp_rest_api_huiyan_jwt_secret');
    $jwt_secret_set = !empty($jwt_secret);
    
    // 检查PHP版本
    $php_version = phpversion();
    $php_ok = version_compare($php_version, '7.4.0', '>=');
    
    // 检查Memcached
    $memcached_enabled = extension_loaded('memcached') || extension_loaded('memcache');
    $memcached_connected = false;
    $memcached_stats = [];
    
    if ($memcached_enabled) {
        if (extension_loaded('memcached')) {
            try {
                $memcached = new Memcached();
                $memcached->addServer('localhost', 11211);
                $memcached_connected = $memcached->getStats();
                if ($memcached_connected) {
                    $memcached_stats = $memcached_connected['localhost:11211'] ?? [];
                }
            } catch (Exception $e) {
                $memcached_connected = false;
            }
        } elseif (extension_loaded('memcache')) {
            try {
                $memcache = new Memcache();
                $memcached_connected = $memcache->connect('localhost', 11211);
                if ($memcached_connected) {
                    $memcached_stats = $memcache->getStats();
                }
            } catch (Exception $e) {
                $memcached_connected = false;
            }
        }
    }
    
    // 检查OPcache
    $opcache_enabled = function_exists('opcache_get_status') && opcache_get_status();
    $opcache_status = $opcache_enabled ? opcache_get_status(false) : [];
    
    // 计算总体状态
    $overall_status = 'ok';
    if (!$php_ok || !$cache_dir_writable || !$jwt_secret_set) {
        $overall_status = 'error';
    } elseif (!$cache_dir_exists || !get_option('wp_rest_api_huiyan_cache_enabled') || !get_option('wp_rest_api_huiyan_jwt_enabled')) {
        $overall_status = 'warning';
    }
    
    ?>
    <div class="wrap">
        <h1>WP REST API by Huiyan - 系统状态</h1>
        
        <!-- 操作按钮区域 - 固定在顶部 -->
        <div class="status-actions">
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display: inline-block; margin-right: 10px;">
                <input type="hidden" name="action" value="wp_rest_api_huiyan_clear_cache" />
                <?php wp_nonce_field( 'wp_rest_api_huiyan_clear_cache_nonce', 'wp_rest_api_huiyan_clear_cache_nonce' ); ?>
                <input type="submit" class="button button-secondary" value="清理 API 缓存" />
            </form>
            
            <?php if ($memcached_enabled) : ?>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display: inline-block; margin-right: 10px;">
                <input type="hidden" name="action" value="wp_rest_api_huiyan_clear_memcached" />
                <?php wp_nonce_field( 'wp_rest_api_huiyan_clear_memcached_nonce', 'wp_rest_api_huiyan_clear_memcached_nonce' ); ?>
                <input type="submit" class="button button-secondary" value="清理 Memcached 缓存" />
            </form>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display: inline-block;">
                <input type="hidden" name="action" value="wp_rest_api_huiyan_clear_opcache" />
                <?php wp_nonce_field( 'wp_rest_api_huiyan_clear_opcache_nonce', 'wp_rest_api_huiyan_clear_opcache_nonce' ); ?>
                <input type="submit" class="button button-secondary" value="清理 OPcache" />
            </form>
        </div>
        
        <!-- 选项卡导航 -->
        <div class="status-tabs">
            <ul class="tab-nav">
                <li class="tab-active" data-tab="overview">总览</li>
                <li data-tab="components">组件状态</li>
                <li data-tab="performance">性能信息</li>
                <li data-tab="system">系统信息</li>
            </ul>
        </div>
        
        <!-- 选项卡内容 -->
        <div class="tab-content">
            <!-- 总览选项卡 -->
            <div id="overview" class="tab-pane active">
                <div class="status-card">
                    <h3>系统总体状态</h3>
                    <div class="status-indicator status-<?php echo $overall_status; ?>">
                        <span class="status-text"><?php 
                            switch ($overall_status) {
                                case 'ok': echo '系统状态良好'; break;
                                case 'warning': echo '系统存在警告'; break;
                                case 'error': echo '系统存在错误'; break;
                            }
                        ?></span>
                    </div>
                </div>
                
                <div class="status-grid">
                    <div class="status-card">
                        <h3>核心组件</h3>
                        <div class="status-item">
                            <span class="status-label">API 缓存:</span>
                            <span class="status-<?php echo get_option('wp_rest_api_huiyan_cache_enabled') ? 'ok' : 'warning'; ?>">
                                <?php echo get_option('wp_rest_api_huiyan_cache_enabled') ? '已启用' : '已禁用'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">JWT 认证:</span>
                            <span class="status-<?php echo get_option('wp_rest_api_huiyan_jwt_enabled') ? 'ok' : 'warning'; ?>">
                                <?php echo get_option('wp_rest_api_huiyan_jwt_enabled') ? '已启用' : '已禁用'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">CORS 支持:</span>
                            <span class="status-<?php echo get_option('wp_rest_api_huiyan_cors_enabled') ? 'ok' : 'warning'; ?>">
                                <?php echo get_option('wp_rest_api_huiyan_cors_enabled') ? '已启用' : '已禁用'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="status-card">
                        <h3>环境要求</h3>
                        <div class="status-item">
                            <span class="status-label">PHP 版本:</span>
                            <span class="status-<?php echo $php_ok ? 'ok' : 'error'; ?>">
                                <?php echo $php_version; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">缓存目录:</span>
                            <span class="status-<?php echo $cache_dir_writable ? 'ok' : 'error'; ?>">
                                <?php echo $cache_dir_writable ? '可写' : '不可写'; ?>
                            </span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">JWT 密钥:</span>
                            <span class="status-<?php echo $jwt_secret_set ? 'ok' : 'error'; ?>">
                                <?php echo $jwt_secret_set ? '已设置' : '未设置'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 组件状态选项卡 -->
            <div id="components" class="tab-pane">
                <div class="status-grid">
                    <div class="status-card">
                        <h3>缓存状态</h3>
                        <table class="widefat">
                            <tbody>
                                <tr class="alternate">
                                    <td>API 缓存</td>
                                    <td>
                                        <span class="status-<?php echo get_option('wp_rest_api_huiyan_cache_enabled') ? 'ok' : 'warning'; ?>">
                                            <?php echo get_option('wp_rest_api_huiyan_cache_enabled') ? '已启用' : '已禁用'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="alternate">
                                    <td>缓存目录</td>
                                    <td>
                                        <span class="status-<?php echo $cache_dir_writable ? 'ok' : 'error'; ?>">
                                            <?php echo $cache_dir_writable ? '可写' : '不可写'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="alternate">
                                    <td>缓存持续时间</td>
                                    <td><?php echo get_option('wp_rest_api_huiyan_cache_duration', 3600); ?> 秒</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="status-card">
                        <h3>JWT 认证状态</h3>
                        <table class="widefat">
                            <tbody>
                                <tr class="alternate">
                                    <td>JWT 认证</td>
                                    <td>
                                        <span class="status-<?php echo get_option('wp_rest_api_huiyan_jwt_enabled') ? 'ok' : 'warning'; ?>">
                                            <?php echo get_option('wp_rest_api_huiyan_jwt_enabled') ? '已启用' : '已禁用'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="alternate">
                                    <td>JWT 密钥</td>
                                    <td>
                                        <span class="status-<?php echo $jwt_secret_set ? 'ok' : 'error'; ?>">
                                            <?php echo $jwt_secret_set ? '已设置' : '未设置'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="alternate">
                                    <td>令牌过期时间</td>
                                    <td><?php echo get_option('wp_rest_api_huiyan_jwt_expiration', 3600); ?> 秒</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="status-card">
                        <h3>CORS 状态</h3>
                        <table class="widefat">
                            <tbody>
                                <tr class="alternate">
                                    <td>CORS 支持</td>
                                    <td>
                                        <span class="status-<?php echo get_option('wp_rest_api_huiyan_cors_enabled') ? 'ok' : 'warning'; ?>">
                                            <?php echo get_option('wp_rest_api_huiyan_cors_enabled') ? '已启用' : '已禁用'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="alternate">
                                    <td>允许的来源</td>
                                    <td>
                                        <?php $origins = get_option('wp_rest_api_huiyan_cors_origins', '*'); ?>
                                        <?php if ($origins === '*') : ?>
                                            <span class="status-warning">允许所有来源</span>
                                        <?php else : ?>
                                            <?php echo str_replace(',', ', ', $origins); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 性能信息选项卡 -->
            <div id="performance" class="tab-pane">
                <div class="status-grid">
                    <?php if ($memcached_enabled) : ?>
                    <div class="status-card">
                        <h3>Memcached 状态</h3>
                        <table class="widefat">
                            <tbody>
                                <tr class="alternate">
                                    <td>Memcached 扩展</td>
                                    <td>
                                        <span class="status-ok">
                                            已安装 (<?php echo extension_loaded('memcached') ? 'memcached' : 'memcache'; ?>)
                                        </span>
                                    </td>
                                </tr>
                                <tr class="alternate">
                                    <td>连接状态</td>
                                    <td>
                                        <span class="status-<?php echo $memcached_connected ? 'ok' : 'error'; ?>">
                                            <?php echo $memcached_connected ? '已连接' : '未连接'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($memcached_connected && !empty($memcached_stats)) : ?>
                                <tr class="alternate">
                                    <td>已使用内存</td>
                                    <td><?php echo isset($memcached_stats['bytes']) ? size_format($memcached_stats['bytes']) : 'N/A'; ?></td>
                                </tr>
                                <tr class="alternate">
                                    <td>最大内存</td>
                                    <td><?php echo isset($memcached_stats['limit_maxbytes']) ? size_format($memcached_stats['limit_maxbytes']) : 'N/A'; ?></td>
                                </tr>
                                <tr class="alternate">
                                    <td>缓存项数</td>
                                    <td><?php echo isset($memcached_stats['curr_items']) ? $memcached_stats['curr_items'] : 'N/A'; ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <div class="status-card">
                        <h3>OPcache 状态</h3>
                        <table class="widefat">
                            <tbody>
                                <tr class="alternate">
                                    <td>OPcache</td>
                                    <td>
                                        <span class="status-<?php echo $opcache_enabled ? 'ok' : 'warning'; ?>">
                                            <?php echo $opcache_enabled ? '已启用' : '未启用'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($opcache_enabled && !empty($opcache_status)) : ?>
                                <tr class="alternate">
                                    <td>已使用内存</td>
                                    <td><?php echo isset($opcache_status['memory_usage']['used_memory']) ? size_format($opcache_status['memory_usage']['used_memory']) : 'N/A'; ?></td>
                                </tr>
                                <tr class="alternate">
                                    <td>最大内存</td>
                                    <td><?php echo isset($opcache_status['memory_usage']['total_memory']) ? size_format($opcache_status['memory_usage']['total_memory']) : 'N/A'; ?></td>
                                </tr>
                                <tr class="alternate">
                                    <td>缓存脚本数</td>
                                    <td><?php echo isset($opcache_status['opcache_statistics']['num_cached_scripts']) ? $opcache_status['opcache_statistics']['num_cached_scripts'] : 'N/A'; ?></td>
                                </tr>
                                <tr class="alternate">
                                    <td>缓存命中率</td>
                                    <td><?php echo isset($opcache_status['opcache_statistics']['opcache_hit_rate']) ? round($opcache_status['opcache_statistics']['opcache_hit_rate'], 2) . '%' : 'N/A'; ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 系统信息选项卡 -->
            <div id="system" class="tab-pane">
                <div class="status-card">
                    <h3>系统信息</h3>
                    <table class="widefat">
                        <tbody>
                            <tr class="alternate">
                                <td>WordPress 版本</td>
                                <td><?php echo $wp_version; ?></td>
                            </tr>
                            <tr class="alternate">
                                <td>PHP 版本</td>
                                <td>
                                    <?php echo $php_version; ?>
                                    <span class="status-<?php echo $php_ok ? 'ok' : 'error'; ?>">
                                        (<?php echo $php_ok ? '满足要求' : '建议升级到 7.4.0 或更高版本'; ?>)
                                    </span>
                                </td>
                            </tr>
                            <tr class="alternate">
                                <td>主题版本</td>
                                <td><?php echo WP_REST_API_HUIYAN_VERSION; ?></td>
                            </tr>
                            <tr class="alternate">
                                <td>服务器软件</td>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr class="alternate">
                                <td>操作系统</td>
                                <td><?php echo PHP_OS; ?></td>
                            </tr>
                            <tr class="alternate">
                                <td>MySQL 版本</td>
                                <td><?php global $wpdb; echo $wpdb->db_version(); ?></td>
                            </tr>
                            <tr class="alternate">
                                <td>最大执行时间</td>
                                <td><?php echo ini_get('max_execution_time'); ?> 秒</td>
                            </tr>
                            <tr class="alternate">
                                <td>内存限制</td>
                                <td><?php echo ini_get('memory_limit'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <style type="text/css">
        /* 选项卡样式 */
        .status-tabs {
            margin: 20px 0;
        }
        .tab-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            border-bottom: 1px solid #ccc;
        }
        .tab-nav li {
            padding: 10px 20px;
            cursor: pointer;
            background: #f1f1f1;
            border-right: 1px solid #ddd;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }
        .tab-nav li:hover {
            background: #e9e9e9;
        }
        .tab-nav li.tab-active {
            background: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
            font-weight: bold;
        }
        
        /* 选项卡内容 */
        .tab-content {
            margin-top: 20px;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        
        /* 状态卡片网格布局 */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .status-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .status-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        /* 状态指示器 */
        .status-indicator {
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 15px;
        }
        .status-ok {
            color: #46b450;
            font-weight: bold;
        }
        .status-error {
            color: #dc3232;
            font-weight: bold;
        }
        .status-warning {
            color: #ffb900;
            font-weight: bold;
        }
        
        /* 状态项目 */
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-label {
            font-weight: 500;
        }
        
        /* 操作按钮区域 */
        .status-actions {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #e1e4e8;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        /* 表格样式优化 */
        .widefat {
            margin-bottom: 0;
        }
        .widefat td {
            padding: 8px 12px;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .tab-nav {
                flex-wrap: wrap;
            }
            .tab-nav li {
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
            .status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script type="text/javascript">
        // 选项卡切换功能
        jQuery(document).ready(function($) {
            // 初始化选项卡
            $('.tab-nav li').click(function() {
                var tabId = $(this).data('tab');
                
                // 移除所有活动状态
                $('.tab-nav li').removeClass('tab-active');
                $('.tab-pane').removeClass('active');
                
                // 添加当前活动状态
                $(this).addClass('tab-active');
                $('#' + tabId).addClass('active');
            });
            
            // 支持URL hash导航
            var hash = window.location.hash.substring(1);
            if (hash && $('#' + hash).length) {
                $('.tab-nav li[data-tab="' + hash + '"]').click();
            }
        });
    </script>
    <?php
}

// 清理缓存处理函数
function wp_rest_api_huiyan_clear_cache_handler() {
    // 验证非ce
    if ( ! isset( $_POST['wp_rest_api_huiyan_clear_cache_nonce'] ) || ! wp_verify_nonce( $_POST['wp_rest_api_huiyan_clear_cache_nonce'], 'wp_rest_api_huiyan_clear_cache_nonce' ) ) {
        wp_die( 'Security check failed' );
    }
    
    // 检查权限
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied' );
    }
    
    // 清理缓存
    require_once WP_REST_API_HUIYAN_DIR . '/includes/api-cache.php';
    wp_rest_api_huiyan_clear_cache();
    
    // 重定向回状态页面
    wp_redirect( add_query_arg( 'page', 'wp-rest-api-huiyan-status', admin_url( 'admin.php' ) ) );
    exit;
}
add_action( 'admin_post_wp_rest_api_huiyan_clear_cache', 'wp_rest_api_huiyan_clear_cache_handler' );

// 添加设置链接到主题描述
function wp_rest_api_huiyan_clear_memcached() {
    // 验证nonce
    if (!isset($_POST['wp_rest_api_huiyan_clear_memcached_nonce']) || !wp_verify_nonce($_POST['wp_rest_api_huiyan_clear_memcached_nonce'], 'wp_rest_api_huiyan_clear_memcached_nonce')) {
        wp_die('安全验证失败');
    }
    
    // 清理Memcached
    $result = false;
    
    try {
        if (extension_loaded('memcached')) {
            $memcached = new Memcached();
            $memcached->addServer('localhost', 11211);
            $result = $memcached->flush();
        } elseif (extension_loaded('memcache')) {
            $memcache = new Memcache();
            $memcache->connect('localhost', 11211);
            $result = $memcache->flush();
        }
    } catch (Exception $e) {
        // 记录错误但不中断执行
        error_log('Memcached清理失败: ' . $e->getMessage());
    }
    
    // 设置通知消息
    if ($result) {
        add_settings_error(
            'wp_rest_api_huiyan_messages',
            'memcached-cleared',
            'Memcached缓存已成功清理',
            'updated'
        );
    } else {
        add_settings_error(
            'wp_rest_api_huiyan_messages',
            'memcached-clear-failed',
            'Memcached缓存清理失败，请检查Memcached服务是否正常运行',
            'error'
        );
    }
    
    // 重定向回状态页面
    wp_redirect(add_query_arg('page', 'wp-rest-api-huiyan-status', admin_url('admin.php')));
    exit;
}
add_action('admin_post_wp_rest_api_huiyan_clear_memcached', 'wp_rest_api_huiyan_clear_memcached');

function wp_rest_api_huiyan_clear_opcache() {
    // 验证nonce
    if (!isset($_POST['wp_rest_api_huiyan_clear_opcache_nonce']) || !wp_verify_nonce($_POST['wp_rest_api_huiyan_clear_opcache_nonce'], 'wp_rest_api_huiyan_clear_opcache_nonce')) {
        wp_die('安全验证失败');
    }
    
    // 清理OPcache
    $result = false;
    
    try {
        if (function_exists('opcache_reset')) {
            $result = opcache_reset();
        }
    } catch (Exception $e) {
        // 记录错误但不中断执行
        error_log('OPcache清理失败: ' . $e->getMessage());
    }
    
    // 设置通知消息
    if ($result) {
        add_settings_error(
            'wp_rest_api_huiyan_messages',
            'opcache-cleared',
            'OPcache已成功清理',
            'updated'
        );
    } else {
        add_settings_error(
            'wp_rest_api_huiyan_messages',
            'opcache-clear-failed',
            'OPcache清理失败，请检查OPcache是否启用',
            'error'
        );
    }
    
    // 重定向回状态页面
    wp_redirect(add_query_arg('page', 'wp-rest-api-huiyan-status', admin_url('admin.php')));
    exit;
}
add_action('admin_post_wp_rest_api_huiyan_clear_opcache', 'wp_rest_api_huiyan_clear_opcache');

function wp_rest_api_huiyan_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wp-rest-api-huiyan">设置</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . WP_REST_API_HUIYAN_BASENAME, 'wp_rest_api_huiyan_add_settings_link');
