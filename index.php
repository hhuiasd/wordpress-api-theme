<?php
/**
 * WP REST API by Huiyan - 主题主模板文件
 * 
 * 这个主题是一个无头主题，主要提供REST API功能
 * 所有直接访问前端的请求将返回API指引页面
 */

// 发送403状态码，表示前端访问被禁止
header('HTTP/1.1 403 Forbidden');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP REST API by Huiyan - API访问指引</title>
    <?php wp_head(); ?>
</head>
<body>
    <div id="content">
        <div class="container">
            <h1>🔒 前端访问已禁用</h1>
            <p>这是一个 <strong>WP REST API by Huiyan</strong> 无头主题，WordPress已被配置为纯后端API服务器。</p>
            
            <h2>📚 API访问指引</h2>
            <p>请通过以下API端点访问内容：</p>
            <p><code><?php echo home_url('/wp-json/'); ?></code></p>
            
            <h3>🔐 认证访问</h3>
            <p>使用JWT令牌进行认证：</p>
            <p><code>POST <?php echo home_url('/wp-json/wp-rest-api-huiyan/v1/login'); ?></code></p>
            
            <h3>🔧 管理API缓存</h3>
            <p>清除API缓存：</p>
            <p><code>DELETE <?php echo home_url('/wp-json/wp-rest-api-huiyan/v1/cache'); ?></code></p>
            
            <p>© 2023 老胡（<a href="https://www.j6s.net" target="_blank">https://www.j6s.net</a>）</p>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
