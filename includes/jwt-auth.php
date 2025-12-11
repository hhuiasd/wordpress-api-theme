<?php
/**
 * WP REST API by Huiyan - JWT 认证模块
 * 
 * 提供基于 JWT 的 API 认证功能
 */

/**
 * 检查 JWT 功能是否启用
 * 
 * @return bool JWT 功能是否启用
 */
function wp_rest_api_huiyan_is_jwt_enabled() {
    return get_option( 'wp_rest_api_huiyan_jwt_enabled', true );
}

/**
 * 获取 JWT 密钥
 * 
 * @return string JWT 密钥
 */
function wp_rest_api_huiyan_get_jwt_secret() {
    $secret = get_option( 'wp_rest_api_huiyan_jwt_secret' );
    
    // 如果没有设置密钥，生成一个新的
    if ( ! $secret ) {
        $secret = wp_generate_password( 32, true, true );
        update_option( 'wp_rest_api_huiyan_jwt_secret', $secret );
    }
    
    return $secret;
}

/**
 * 获取 JWT 令牌过期时间
 * 
 * @return int 过期时间（秒）
 */
function wp_rest_api_huiyan_get_jwt_expiration() {
    return get_option( 'wp_rest_api_huiyan_jwt_expiration', 3600 );
}

/**
 * 生成 JWT 令牌
 * 
 * @param int $user_id 用户 ID
 * @return string JWT 令牌
 */
function wp_rest_api_huiyan_generate_jwt_token( $user_id ) {
    // 获取 JWT 密钥和过期时间
    $secret = wp_rest_api_huiyan_get_jwt_secret();
    $expiration = wp_rest_api_huiyan_get_jwt_expiration();
    
    // 创建 JWT 头部
    $header = array(
        'typ' => 'JWT',
    'alg' => 'HS256'
    );
    
    // 创建 JWT 载荷
    $payload = array(
        'iat' => time(),              // 签发时间
    'exp' => time() + $expiration, // 过期时间
    'sub' => $user_id,              // 主题（用户 ID）
    'jti' => wp_generate_uuid4()    // JWT ID（唯一标识）
    );
    
    // 编码头部和载荷
    $header_encoded = wp_rest_api_huiyan_base64url_encode( json_encode( $header ) );
    $payload_encoded = wp_rest_api_huiyan_base64url_encode( json_encode( $payload ) );
    
    // 创建签名
    $signature = hash_hmac( 'sha256', $header_encoded . '.' . $payload_encoded, $secret, true );
    $signature_encoded = wp_rest_api_huiyan_base64url_encode( $signature );
    
    // 组合 JWT 令牌
    $token = $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    
    return $token;
}

/**
 * 验证 JWT 令牌
 * 
 * @param string $token JWT 令牌
 * @return array|false 解码后的令牌数据，如果验证失败则返回 false
 */
function wp_rest_api_huiyan_validate_jwt_token( $token ) {
    // 获取 JWT 密钥
    $secret = wp_rest_api_huiyan_get_jwt_secret();
    
    // 分割令牌
    $token_parts = explode( '.', $token );
    
    // 检查令牌格式
    if ( count( $token_parts ) !== 3 ) {
        return false;
    }
    
    list( $header_encoded, $payload_encoded, $signature_encoded ) = $token_parts;
    
    // 解码头部和载荷
    $header = json_decode( wp_rest_api_huiyan_base64url_decode( $header_encoded ), true );
    $payload = json_decode( wp_rest_api_huiyan_base64url_decode( $payload_encoded ), true );
    
    // 检查解码是否成功
    if ( ! $header || ! $payload ) {
        return false;
    }
    
    // 检查令牌是否过期
    if ( isset( $payload['exp'] ) && $payload['exp'] < time() ) {
        return false;
    }
    
    // 验证签名
    $expected_signature = hash_hmac( 'sha256', $header_encoded . '.' . $payload_encoded, $secret, true );
    $expected_signature_encoded = wp_rest_api_huiyan_base64url_encode( $expected_signature );
    
    if ( ! hash_equals( $signature_encoded, $expected_signature_encoded ) ) {
        return false;
    }
    
    return array(
        'header' => $header,
        'payload' => $payload,
        'user_id' => isset( $payload['sub'] ) ? $payload['sub'] : null
    );
}

/**
 * Base64 URL 编码
 * 
 * @param string $data 要编码的数据
 * @return string 编码后的字符串
 */
function wp_rest_api_huiyan_base64url_encode( $data ) {
    return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

/**
 * Base64 URL 解码
 * 
 * @param string $data 要解码的数据
 * @return string 解码后的字符串
 */
function wp_rest_api_huiyan_base64url_decode( $data ) {
    return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=', STR_PAD_RIGHT ) );
}

/**
 * 从请求中获取 JWT 令牌
 * 
 * @return string|false JWT 令牌，如果未找到则返回 false
 */
function wp_rest_api_huiyan_get_token_from_request() {
    // 尝试从 Authorization 头部获取令牌
    if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if ( preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
            return $matches[1];
        }
    }
    
    // 尝试从查询参数获取令牌
    if ( isset( $_GET['token'] ) ) {
        return $_GET['token'];
    }
    
    // 尝试从请求体获取令牌（POST 请求）
    if ( isset( $_POST['token'] ) ) {
        return $_POST['token'];
    }
    
    return false;
}

/**
 * 处理 JWT 认证
 * 
 * @return WP_User|false 认证的用户对象，如果认证失败则返回 false
 */
function wp_rest_api_huiyan_authenticate_jwt() {
    // 检查 JWT 功能是否启用
    if ( ! wp_rest_api_huiyan_is_jwt_enabled() ) {
        return false;
    }
    
    // 获取令牌
    $token = wp_rest_api_huiyan_get_token_from_request();
    if ( ! $token ) {
        return false;
    }
    
    // 验证令牌
    $token_data = wp_rest_api_huiyan_validate_jwt_token( $token );
    if ( ! $token_data || ! $token_data['user_id'] ) {
        return false;
    }
    
    // 获取用户对象
    $user = get_user_by( 'id', $token_data['user_id'] );
    if ( ! $user || is_wp_error( $user ) ) {
        return false;
    }
    
    // 检查用户是否仍然有效
    if ( ! $user->exists() || ! user_can( $user, 'read' ) ) {
        return false;
    }
    
    return $user;
}

/**
 * 注册 JWT 认证路由
 */
function wp_rest_api_huiyan_register_jwt_routes() {
    // 检查 JWT 功能是否启用
    if ( ! wp_rest_api_huiyan_is_jwt_enabled() ) {
        return;
    }
    
    // 登录端点
    register_rest_route( 'wp-rest-api-huiyan/v1', '/auth/login', array(
        'methods' => 'POST',
        'callback' => 'wp_rest_api_huiyan_handle_login',
        'permission_callback' => '__return_true',
        'args' => array(
            'username' => array(
                'required' => true,
                'type' => 'string',
                'description' => '用户登录名'
            ),
            'password' => array(
                'required' => true,
                'type' => 'string',
                'description' => '用户密码'
            )
        )
    ) );
    
    // 刷新令牌端点
    register_rest_route( 'wp-rest-api-huiyan/v1', '/auth/refresh', array(
        'methods' => 'POST',
        'callback' => 'wp_rest_api_huiyan_handle_refresh',
        'permission_callback' => 'wp_rest_api_huiyan_jwt_permission_check'
    ) );
    
    // 验证令牌端点
    register_rest_route( 'wp-rest-api-huiyan/v1', '/auth/verify', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_api_huiyan_handle_verify',
        'permission_callback' => 'wp_rest_api_huiyan_jwt_permission_check'
    ) );
    
    // 登出端点
    register_rest_route( 'wp-rest-api-huiyan/v1', '/auth/logout', array(
        'methods' => 'POST',
        'callback' => 'wp_rest_api_huiyan_handle_logout',
        'permission_callback' => 'wp_rest_api_huiyan_jwt_permission_check'
    ) );
}
add_action( 'rest_api_init', 'wp_rest_api_huiyan_register_jwt_routes' );

/**
 * 处理登录请求
 * 
 * @param WP_REST_Request $request 请求对象
 * @return WP_REST_Response 响应对象
 */
function wp_rest_api_huiyan_handle_login( $request ) {
    // 获取请求参数
    $username = $request-\u003get_param( 'username' );
    $password = $request-\u003get_param( 'password' );
    
    // 检查是否达到最大登录尝试次数
    if ( ! wp_rest_api_huiyan_check_login_attempts() ) {
        return new WP_REST_Response( array(
            'code' => 'too_many_attempts',
            'message' => '登录尝试次数过多，请稍后再试。'
        ), 429 );
    }
    
    // 尝试登录
    $user = wp_authenticate( $username, $password );
    
    // 检查登录是否成功
    if ( is_wp_error( $user ) ) {
        // 记录失败尝试
        wp_rest_api_huiyan_record_login_attempt( false );
        
        return new WP_REST_Response( array(
            'code' => 'invalid_credentials',
            'message' => '用户名或密码错误。'
        ), 401 );
    }
    
    // 登录成功，记录成功尝试
    wp_rest_api_huiyan_record_login_attempt( true );
    
    // 生成 JWT 令牌
    $token = wp_rest_api_huiyan_generate_jwt_token( $user->ID );
    
    // 获取令牌过期时间
    $expiration = wp_rest_api_huiyan_get_jwt_expiration();
    
    // 返回令牌和用户信息
    return new WP_REST_Response( array(
        'token' => $token,
        'user' => array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles
        ),
        'expires_in' => $expiration,
        'token_type' => 'Bearer'
    ), 200 );
}

/**
 * 处理刷新令牌请求
 * 
 * @param WP_REST_Request $request 请求对象
 * @return WP_REST_Response 响应对象
 */
function wp_rest_api_huiyan_handle_refresh( $request ) {
    // 获取当前用户
    $user = wp_get_current_user();
    if ( ! $user || ! $user->exists() ) {
        return new WP_REST_Response( array(
            'code' => 'unauthorized',
            'message' => '未授权的访问。'
        ), 401 );
    }
    
    // 生成新的令牌
    $token = wp_rest_api_huiyan_generate_jwt_token( $user->ID );
    
    // 获取令牌过期时间
    $expiration = wp_rest_api_huiyan_get_jwt_expiration();
    
    // 返回新令牌
    return new WP_REST_Response( array(
        'token' => $token,
        'expires_in' => $expiration,
        'token_type' => 'Bearer'
    ), 200 );
}

/**
 * 处理验证令牌请求
 * 
 * @param WP_REST_Request $request 请求对象
 * @return WP_REST_Response 响应对象
 */
function wp_rest_api_huiyan_handle_verify( $request ) {
    // 获取当前用户
    $user = wp_get_current_user();
    if ( ! $user || ! $user->exists() ) {
        return new WP_REST_Response( array(
            'code' => 'unauthorized',
            'message' => '未授权的访问。'
        ), 401 );
    }
    
    // 返回用户信息
    return new WP_REST_Response( array(
        'valid' => true,
        'user' => array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles
        )
    ), 200 );
}

/**
 * 处理登出请求
 * 
 * @param WP_REST_Request $request 请求对象
 * @return WP_REST_Response 响应对象
 */
function wp_rest_api_huiyan_handle_logout( $request ) {
    // 在实际应用中，你可能需要维护一个已失效令牌的黑名单
    // 这里简单地返回成功响应
    return new WP_REST_Response( array(
        'message' => '登出成功。'
    ), 200 );
}

/**
 * JWT 权限检查
 * 
 * @return bool 是否有权限访问
 */
function wp_rest_api_huiyan_jwt_permission_check() {
    // 验证 JWT 令牌
    $user = wp_rest_api_huiyan_authenticate_jwt();
    
    if ( $user ) {
        // 设置当前用户
        wp_set_current_user( $user->ID );
        return true;
    }
    
    return false;
}

/**
 * 记录登录尝试
 * 
 * @param bool $success 是否登录成功
 */
function wp_rest_api_huiyan_record_login_attempt( $success ) {
    // 获取客户端 IP
    $client_ip = wp_rest_api_huiyan_get_client_ip();
    
    // 获取登录尝试数据
    $login_attempts = get_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip );
    if ( ! $login_attempts ) {
        $login_attempts = array(
            'count' => 0,
            'last_attempt' => time()
        );
    }
    
    if ( ! $success ) {
        // 增加失败尝试次数
        $login_attempts['count']++;
        $login_attempts['last_attempt'] = time();
        
        // 设置临时数据（1小时）
        set_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip, $login_attempts, 3600 );
    } else {
        // 登录成功，重置尝试次数
        delete_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip );
    }
}

/**
 * 检查登录尝试次数
 * 
 * @return bool 是否允许登录尝试
 */
function wp_rest_api_huiyan_check_login_attempts() {
    // 获取客户端 IP
    $client_ip = wp_rest_api_huiyan_get_client_ip();
    
    // 获取最大尝试次数和锁定时间
    $max_attempts = get_option( 'wp_rest_api_huiyan_max_login_attempts', 5 );
    $lockout_duration = get_option( 'wp_rest_api_huiyan_lockout_duration', 3600 );
    
    // 获取登录尝试数据
    $login_attempts = get_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip );
    if ( ! $login_attempts || $login_attempts['count'] < $max_attempts ) {
        return true;
    }
    
    // 检查是否还在锁定时间内
    $time_passed = time() - $login_attempts['last_attempt'];
    if ( $time_passed > $lockout_duration ) {
        // 锁定时间已过，重置尝试次数
        delete_transient( 'wp_rest_api_huiyan_login_attempts_' . $client_ip );
        return true;
    }
    
    return false;
}

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

/**
 * REST API 身份验证过滤器
 * 
 * @param WP_User|bool $user 当前用户
 * @return WP_User|bool 验证后的用户
 */
function wp_rest_api_huiyan_rest_authentication_handler( $user ) {
    // 如果已经有认证用户，跳过
    if ( ! empty( $user ) ) {
        return $user;
    }
    
    // 尝试使用 JWT 认证
    return wp_rest_api_huiyan_authenticate_jwt();
}
add_filter( 'rest_authentication_errors', 'wp_rest_api_huiyan_rest_authentication_handler' );
