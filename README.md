# WordPress REST API 无头主题 by Huiyan

一个专为 REST API 优化的 WordPress 主题，提供安全、高效的 API 访问体验，适用于构建现代前端应用。

## 功能特点

- **完整的 REST API 支持**：优化 WordPress REST API 响应，提供更简洁、更高效的数据结构
- **JWT 认证机制**：支持基于 JWT 的无状态认证，便于前后端分离架构
- **API 缓存系统**：内置高效的 API 响应缓存功能，显著提升性能
- **Memcached 支持**：状态检查和一键清理功能
- **OPcache 管理**：状态监控和优化清理功能
- **安全加固**：多重安全措施，包括登录尝试限制、XML-RPC 禁用、安全头部添加等
- **跨域资源共享 (CORS)**：灵活配置的跨域请求支持，方便前端应用调用
- **管理后台设置**：直观的管理界面，可配置所有核心功能
- **改进的系统状态页面**：选项卡式布局，更紧凑、更有条理地展示系统信息和状态
- **自定义 API 端点**：扩展的 API 端点，提供额外的功能
- **完全无头设计**：禁用前端访问，专注于 API 服务

## 安装方法

### 手动安装

1. 下载此主题的 ZIP 文件
2. 登录 WordPress 管理后台
3. 导航至 "外观 > 主题 > 添加新主题 > 上传主题"
4. 选择下载的 ZIP 文件，点击 "安装现在"
5. 安装完成后，点击 "启用"

### 手动上传

1. 将主题文件夹上传至 WordPress 的 `/wp-content/themes/` 目录
2. 登录 WordPress 管理后台
3. 导航至 "外观 > 主题"
4. 找到 "WP REST API by Huiyan" 主题并点击 "启用"

## 配置说明

启用主题后，可以在 WordPress 管理后台的 "设置 > WP REST API 设置" 页面配置各项功能。系统状态页面提供了直观的选项卡式界面，方便查看和管理系统信息。

### 缓存设置

- **启用 API 缓存**：开启或关闭 API 响应缓存
- **缓存时间**：设置缓存有效期（秒）
- **清理缓存**：手动清理所有 API 缓存

### JWT 设置

- **启用 JWT 认证**：开启或关闭 JWT 认证
- **JWT 密钥**：JWT 签名密钥（建议使用随机生成的复杂字符串）
- **令牌有效期**：设置访问令牌的有效期（秒）
- **刷新令牌有效期**：设置刷新令牌的有效期（秒）

### 跨域设置

- **启用 CORS**：开启或关闭跨域资源共享
- **允许的来源**：指定允许访问 API 的域名，多个域名用逗号分隔，或使用 `*` 允许所有来源

### 安全设置

- **禁用 XML-RPC**：禁止使用 XML-RPC 接口
- **隐藏 WordPress 版本**：从前端移除 WordPress 版本信息
- **禁用更新通知**：隐藏管理后台的更新通知
- **限制登录尝试**：限制单个 IP 的登录尝试次数
- **登录尝试次数**：设置允许的最大登录尝试次数
- **锁定时间**：设置登录失败后的锁定时间（秒）

## API 使用方法

### 基础 API 端点

WordPress REST API 的基础端点为：

```
https://your-domain.com/wp-json/
```

### 获取文章列表

```
GET /wp-json/wp/v2/posts
```

### 获取单篇文章

```
GET /wp-json/wp/v2/posts/{post_id}
```

### 用户认证

#### 登录获取令牌

```
POST /wp-json/wp-rest-api-huiyan/v1/auth/login
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}
```

响应示例：

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "roles": ["administrator"]
  }
}
```

#### 使用令牌访问受保护的 API

```
GET /wp-json/wp/v2/users/me
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### 刷新令牌

```
POST /wp-json/wp-rest-api-huiyan/v1/auth/refresh
Content-Type: application/json

{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

#### 注销

```
POST /wp-json/wp-rest-api-huiyan/v1/auth/logout
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 自定义 API 端点

#### 获取 API 状态

```
GET /wp-json/wp-rest-api-huiyan/v1/status
```

#### 系统状态管理

在 WordPress 管理后台，系统状态页面提供了选项卡式布局，包含以下主要部分：
- **总览**：核心系统状态概览
- **组件状态**：详细的组件运行状态
  - **REST API**：API 可用性和性能指标
  - **JWT 认证**：认证系统状态
  - **缓存状态**：API 缓存使用情况
  - **CORS 设置**：跨域配置状态
  - **Memcached**：内存缓存服务状态和使用情况
  - **OPcache**：PHP 操作码缓存状态和统计信息
- **性能信息**：系统性能指标
- **系统信息**：详细的环境和配置信息

响应示例：

```json
{
  "status": "ok",
  "version": "1.0.0",
  "wp_version": "6.5",
  "features": {
    "jwt_enabled": true,
    "cache_enabled": true,
    "cors_enabled": true
  }
}
```

## 安全考虑

- 请确保使用强密码和复杂的 JWT 密钥
- 定期更新 WordPress 和此主题到最新版本
- 只允许必要的跨域来源访问 API
- 对于生产环境，建议配置 HTTPS
- 考虑使用额外的安全插件增强保护

## 性能优化

- 启用 API 缓存可显著提升性能
- 适当配置缓存时间，平衡性能和数据实时性
- 考虑使用 CDN 缓存静态资源
- 优化数据库查询和索引

## 自定义开发

### 扩展 API 端点

可以通过在主题的 `functions.php` 文件或自定义插件中添加新的 REST API 端点：

```php
add_action( 'rest_api_init', function () {
    register_rest_route( 'your-namespace/v1', '/your-endpoint', array(
        'methods' => 'GET',
        'callback' => 'your_callback_function',
        'permission_callback' => '__return_true' // 或其他权限检查函数
    ));
});
```

### 修改 API 响应

可以通过过滤器修改 API 响应：

```php
add_filter( 'rest_prepare_post', function( $response, $post, $request ) {
    // 修改响应数据
    $data = $response->data;
    $data['custom_field'] = get_post_meta( $post->ID, 'your_custom_field', true );
    $response->data = $data;
    return $response;
}, 10, 3 );
```

## 故障排除

### API 访问问题

1. 确认主题已正确启用
2. 检查是否配置了正确的 JWT 密钥
3. 验证跨域设置是否正确
4. 检查是否存在插件冲突
5. 通过管理后台的系统状态页面查看详细错误信息

### 缓存问题

1. 尝试手动清理缓存
2. 检查缓存目录的权限设置
3. 临时禁用缓存功能排查问题
4. 通过系统状态页面检查和清理 Memcached 缓存
5. 通过系统状态页面查看 OPcache 状态并进行清理

### JWT 认证问题

1. 确认 JWT 密钥已正确配置
2. 检查令牌格式是否正确
3. 验证令牌是否已过期

## 常见问题

### 问：如何完全禁用 WordPress 前端访问？

答：本主题已默认禁用前端访问，所有直接访问网站的请求将返回 403 错误。

### 问：如何修改 API 缓存时间？

答：可以在 "设置 > WP REST API 设置 > 缓存设置" 中修改缓存时间。

### 问：如何添加新的自定义字段到 API 响应？

答：可以使用 `register_meta` 函数注册自定义字段，或通过过滤器修改 API 响应。

## 许可证

此主题采用 MIT 许可证。详见 LICENSE 文件。

## 版本历史

### 1.1.0
- 改进的系统状态页面：实现选项卡式布局
- Memcached 状态检查和清理功能
- OPcache 状态监控和优化清理功能
- 响应式设计优化
- UI 体验改进

### 1.0.0
- 初始版本
- 完整的 REST API 支持
- JWT 认证机制
- API 缓存系统
- 安全加固
- 跨域资源共享支持
- 管理后台设置界面

## 支持

如需支持，请联系开发者或在 GitHub 上提交 issue。

## 贡献

欢迎提交 Pull Request 改进此主题。
