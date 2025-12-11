<?php
/**
 * WP REST API by Huiyan - 主题功能测试脚本
 * 
 * 用于验证主题核心功能是否正常工作
 */

// 确保从 WordPress 环境加载
if ( ! defined( 'ABSPATH' ) ) {
    require_once( dirname( __FILE__ ) . '/../../wp-load.php' );
}

// 设置页面标题和内容类型
header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP REST API by Huiyan - 功能测试</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-left: 10px;
        }
        .status-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .status-error {
            background-color: #f2dede;
            color: #a94442;
        }
        .status-warning {
            background-color: #fcf8e3;
            color: #8a6d3b;
        }
        pre {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #eee;
        }
        code {
            font-family: 'Courier New', Courier, monospace;
        }
        .info-box {
            background-color: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196f3;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>WP REST API by Huiyan - 功能测试</h1>
    
    <div class="info-box">
        <p>本页面用于测试 WP REST API by Huiyan 主题的核心功能是否正常工作。测试结果仅供参考，不能替代实际环境中的全面测试。</p>
    </div>
    
    <div class="test-section">
        <h2>环境信息</h2>
        <table>
            <tr>
                <th>项目</th>
                <th>值</th>
            </tr>
            <tr>
                <td>WordPress 版本</td>
                <td><?php echo get_bloginfo( 'version' ); ?></td>
            </tr>
            <tr>
                <td>PHP 版本</td>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <td>主题版本</td>
                <td>1.0.0</td>
            </tr>
            <tr>
                <td>REST API 基础路径</td>
                <td><?php echo esc_html( get_rest_url() ); ?></td>
            </tr>
            <tr>
                <td>当前激活主题</td>
                <td><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>功能状态检查</h2>
        
        <h3>REST API 可用性</h3>
        <p>WordPress REST API 支持: <span class="status <?php echo rest_get_server() ? 'status-success' : 'status-error'; ?>"> 
            <?php echo rest_get_server() ? '已启用' : '未启用'; ?> 
        </span></p>
        
        <h3>JWT 认证</h3>
        <p>JWT 认证模块: <span class="status <?php echo function_exists( 'wp_rest_api_huiyan_jwt_init' ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo function_exists( 'wp_rest_api_huiyan_jwt_init' ) ? '已加载' : '未加载'; ?> 
        </span></p>
        <p>JWT 认证状态: <span class="status <?php echo get_option( 'wp_rest_api_huiyan_jwt_enabled', false ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo get_option( 'wp_rest_api_huiyan_jwt_enabled', false ) ? '已启用' : '已禁用'; ?> 
        </span></p>
        
        <h3>API 缓存</h3>
        <p>缓存模块: <span class="status <?php echo function_exists( 'wp_rest_api_huiyan_cache_init' ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo function_exists( 'wp_rest_api_huiyan_cache_init' ) ? '已加载' : '未加载'; ?> 
        </span></p>
        <p>缓存状态: <span class="status <?php echo get_option( 'wp_rest_api_huiyan_cache_enabled', false ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo get_option( 'wp_rest_api_huiyan_cache_enabled', false ) ? '已启用' : '已禁用'; ?> 
        </span></p>
        <?php if ( get_option( 'wp_rest_api_huiyan_cache_enabled', false ) ) : ?>
            <p>缓存时间: <span class="status status-info"><?php echo get_option( 'wp_rest_api_huiyan_cache_duration', 3600 ); ?> 秒</span></p>
        <?php endif; ?>
        
        <h3>跨域设置 (CORS)</h3>
        <p>CORS 模块: <span class="status <?php echo function_exists( 'wp_rest_api_huiyan_load_cors_features' ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo function_exists( 'wp_rest_api_huiyan_load_cors_features' ) ? '已加载' : '未加载'; ?> 
        </span></p>
        <p>CORS 状态: <span class="status <?php echo get_option( 'wp_rest_api_huiyan_cors_enabled', false ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo get_option( 'wp_rest_api_huiyan_cors_enabled', false ) ? '已启用' : '已禁用'; ?> 
        </span></p>
        <?php if ( get_option( 'wp_rest_api_huiyan_cors_enabled', false ) ) : ?>
            <p>允许的来源: <span class="status status-info"><?php echo get_option( 'wp_rest_api_huiyan_cors_origins', '*' ); ?></span></p>
        <?php endif; ?>
        
        <h3>安全功能</h3>
        <p>安全模块: <span class="status <?php echo function_exists( 'wp_rest_api_huiyan_load_security_features' ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo function_exists( 'wp_rest_api_huiyan_load_security_features' ) ? '已加载' : '未加载'; ?> 
        </span></p>
        <p>XML-RPC 禁用: <span class="status <?php echo get_option( 'wp_rest_api_huiyan_disable_xmlrpc', true ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo get_option( 'wp_rest_api_huiyan_disable_xmlrpc', true ) ? '已禁用' : '已启用'; ?> 
        </span></p>
        <p>隐藏版本信息: <span class="status <?php echo get_option( 'wp_rest_api_huiyan_hide_version', true ) ? 'status-success' : 'status-warning'; ?>">
            <?php echo get_option( 'wp_rest_api_huiyan_hide_version', true ) ? '已隐藏' : '已显示'; ?> 
        </span></p>
    </div>
    
    <div class="test-section">
        <h2>API 端点测试</h2>
        <p>以下是可用于测试的 API 端点:</p>
        
        <h3>公共端点</h3>
        <pre>
# 获取文章列表
GET <?php echo esc_html( get_rest_url() ); ?>wp/v2/posts

# 获取页面列表
GET <?php echo esc_html( get_rest_url() ); ?>wp/v2/pages

# 获取分类列表
GET <?php echo esc_html( get_rest_url() ); ?>wp/v2/categories

# 获取标签列表
GET <?php echo esc_html( get_rest_url() ); ?>wp/v2/tags

# 获取主题状态信息
GET <?php echo esc_html( get_rest_url() ); ?>wp-rest-api-huiyan/v1/status</pre>
        
        <h3>认证端点</h3>
        <pre>
# 用户登录 (获取令牌)
POST <?php echo esc_html( get_rest_url() ); ?>wp-rest-api-huiyan/v1/auth/login
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}

# 刷新令牌
POST <?php echo esc_html( get_rest_url() ); ?>wp-rest-api-huiyan/v1/auth/refresh
Content-Type: application/json
Authorization: Bearer your_access_token

{
  "refresh_token": "your_refresh_token"
}

# 用户注销
POST <?php echo esc_html( get_rest_url() ); ?>wp-rest-api-huiyan/v1/auth/logout
Authorization: Bearer your_access_token

# 清理缓存
DELETE <?php echo esc_html( get_rest_url() ); ?>wp-rest-api-huiyan/v1/cache
Authorization: Bearer your_access_token</pre>
    </div>
    
    <div class="test-section">
        <h2>使用指南</h2>
        
        <h3>测试 API 访问</h3>
        <p>可以使用以下工具测试 API 访问:</p>
        <ul>
            <li>Postman: 可视化 API 测试工具</li>
            <li>curl: 命令行工具</li>
            <li>Insomnia: 类似 Postman 的 API 测试工具</li>
        </ul>
        
        <h3>curl 测试示例</h3>
        <pre>
# 测试公共 API 访问
curl "<?php echo esc_html( get_rest_url() ); ?>wp/v2/posts"

# 测试登录
curl -X POST "<?php echo esc_html( get_rest_url() ); ?>wp-rest-api-huiyan/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"your_username","password":"your_password"}'

# 使用令牌访问受保护的 API
curl "<?php echo esc_html( get_rest_url() ); ?>wp/v2/users/me" \
  -H "Authorization: Bearer your_access_token"</pre>
    </div>
    
    <div class="test-section">
        <h2>故障排除</h2>
        
        <h3>常见问题</h3>
        <ul>
            <li><strong>API 返回 403 Forbidden:</strong> 检查是否已登录，或当前用户是否有权限访问该端点</li>
            <li><strong>JWT 令牌无效:</strong> 确保令牌未过期，或尝试重新登录获取新令牌</li>
            <li><strong>跨域请求失败:</strong> 检查 CORS 设置是否正确，确认请求来源在允许列表中</li>
            <li><strong>缓存不更新:</strong> 尝试清理缓存或调整缓存时间</li>
        </ul>
        
        <h3>启用调试模式</h3>
        <p>在 wp-config.php 中添加以下代码启用调试模式:</p>
        <pre>
// 启用调试模式
define( 'WP_DEBUG', true );

// 将错误记录到日志文件
define( 'WP_DEBUG_LOG', true );

// 不在前端显示错误
define( 'WP_DEBUG_DISPLAY', false );</pre>
        <p>错误日志将保存在 wp-content/debug.log 文件中。</p>
    </div>
    
    <div class="footer">
        <p>WP REST API by Huiyan 主题功能测试脚本 | 版本 1.0.0</p>
        <p>作者: 老胡 (https://www.j6s.net)</p>
    </div>
</body>
</html>
