<?php
/**
 * WP REST API by Huiyan - API 缓存模块
 * 
 * 提供 REST API 响应的缓存功能，减少重复请求，提高性能
 */

/**
 * 检查 API 请求是否应该被缓存
 * 
 * @return bool 是否应该缓存该请求
 */
function wp_rest_api_huiyan_should_cache_request() {
    // 检查缓存功能是否启用
    if ( ! get_option( 'wp_rest_api_huiyan_cache_enabled', true ) ) {
        return false;
    }
    
    // 只缓存 GET 请求
    if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
        return false;
    }
    
    // 检查是否是 REST API 请求
    $request_uri = $_SERVER['REQUEST_URI'];
    if ( ! preg_match( '#^/wp-json/#', parse_url( $request_uri, PHP_URL_PATH ) ) ) {
        return false;
    }
    
    // 不缓存需要认证的请求
    if ( is_user_logged_in() ) {
        return false;
    }
    
    // 不缓存特定的 API 端点
    $excluded_endpoints = array(
        '/wp-json/wp/v2/users',
        '/wp-json/wp/v2/users/',
        '/wp-json/wp/v2/comments',
        '/wp-json/wp/v2/comments/',
    );
    
    foreach ( $excluded_endpoints as $endpoint ) {
        if ( strpos( $request_uri, $endpoint ) === 0 ) {
            return false;
        }
    }
    
    return true;
}

/**
 * 生成缓存键
 * 
 * @return string 缓存键
 */
function wp_rest_api_huiyan_generate_cache_key() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $query_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
    
    // 组合请求 URI 和查询参数
    $cache_key = $request_uri;
    if ( $query_string ) {
        $cache_key .= '?' . $query_string;
    }
    
    // 使用 MD5 生成缓存键
    return md5( $cache_key ) . '.json';
}

/**
 * 获取缓存目录
 * 
 * @return string 缓存目录路径
 */
function wp_rest_api_huiyan_get_cache_dir() {
    $cache_dir = get_option( 'wp_rest_api_huiyan_cache_dir', WP_CONTENT_DIR . '/cache/wp-rest-api-huiyan' );
    
    // 确保缓存目录存在
    wp_rest_api_huiyan_ensure_cache_dir( $cache_dir );
    
    return $cache_dir;
}

/**
 * 确保缓存目录存在并可写
 * 
 * @param string $cache_dir 缓存目录路径
 * @return bool 是否成功创建目录
 */
function wp_rest_api_huiyan_ensure_cache_dir( $cache_dir ) {
    if ( ! file_exists( $cache_dir ) ) {
        // 尝试创建目录（使用递归方式创建父目录）
        @mkdir( $cache_dir, 0755, true );
        
        // 创建 .htaccess 文件以保护缓存目录
        if ( file_exists( $cache_dir ) ) {
            $htaccess_content = 'deny from all';
            @file_put_contents( $cache_dir . '/.htaccess', $htaccess_content );
        }
    }
    
    return file_exists( $cache_dir ) && is_writable( $cache_dir );
}

/**
 * 检查缓存是否存在且有效
 * 
 * @param string $cache_key 缓存键
 * @return bool 缓存是否有效
 */
function wp_rest_api_huiyan_is_cache_valid( $cache_key ) {
    $cache_dir = wp_rest_api_huiyan_get_cache_dir();
    $cache_file = $cache_dir . '/' . $cache_key;
    
    // 检查缓存文件是否存在
    if ( ! file_exists( $cache_file ) ) {
        return false;
    }
    
    // 检查缓存是否过期
    $cache_duration = get_option( 'wp_rest_api_huiyan_cache_duration', 3600 );
    $file_modified_time = filemtime( $cache_file );
    $current_time = time();
    
    return ( $current_time - $file_modified_time ) <= $cache_duration;
}

/**
 * 获取缓存内容
 * 
 * @param string $cache_key 缓存键
 * @return mixed 缓存的内容，如果缓存不存在或已过期则返回 false
 */
function wp_rest_api_huiyan_get_cache( $cache_key ) {
    if ( ! wp_rest_api_huiyan_is_cache_valid( $cache_key ) ) {
        return false;
    }
    
    $cache_dir = wp_rest_api_huiyan_get_cache_dir();
    $cache_file = $cache_dir . '/' . $cache_key;
    
    // 读取并返回缓存内容
    $content = @file_get_contents( $cache_file );
    if ( $content ) {
        return json_decode( $content, true );
    }
    
    return false;
}

/**
 * 设置缓存内容
 * 
 * @param string $cache_key 缓存键
 * @param mixed $content 要缓存的内容
 * @return bool 是否成功设置缓存
 */
function wp_rest_api_huiyan_set_cache( $cache_key, $content ) {
    $cache_dir = wp_rest_api_huiyan_get_cache_dir();
    $cache_file = $cache_dir . '/' . $cache_key;
    
    // 检查目录是否可写
    if ( ! is_writable( $cache_dir ) ) {
        return false;
    }
    
    // 将内容编码为 JSON 并写入文件
    $json_content = json_encode( $content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    return @file_put_contents( $cache_file, $json_content ) !== false;
}

/**
 * 清理所有 API 缓存
 * 
 * @return int 已删除的缓存文件数量
 */
function wp_rest_api_huiyan_clear_cache() {
    $cache_dir = wp_rest_api_huiyan_get_cache_dir();
    $deleted_count = 0;
    
    if ( ! file_exists( $cache_dir ) || ! is_dir( $cache_dir ) ) {
        return 0;
    }
    
    // 打开目录
    $dir = opendir( $cache_dir );
    if ( $dir ) {
        // 遍历目录中的所有文件
        while ( ( $file = readdir( $dir ) ) !== false ) {
            // 跳过 .htaccess 文件和特殊目录
            if ( $file === '.' || $file === '..' || $file === '.htaccess' ) {
                continue;
            }
            
            $file_path = $cache_dir . '/' . $file;
            
            // 只删除常规文件
            if ( is_file( $file_path ) ) {
                if ( @unlink( $file_path ) ) {
                    $deleted_count++;
                }
            }
        }
        
        closedir( $dir );
    }
    
    return $deleted_count;
}

/**
 * 获取缓存统计信息
 * 
 * @return array 缓存统计信息
 */
function wp_rest_api_huiyan_get_file_cache_stats() {
    $cache_dir = wp_rest_api_huiyan_get_cache_dir();
    $stats = array(
        'cache_files' => 0,
        'cache_size' => 0,
        'cache_dir' => $cache_dir,
        'cache_enabled' => get_option( 'wp_rest_api_huiyan_cache_enabled', true ),
        'cache_duration' => get_option( 'wp_rest_api_huiyan_cache_duration', 3600 ),
    );
    
    if ( ! file_exists( $cache_dir ) || ! is_dir( $cache_dir ) ) {
        return $stats;
    }
    
    // 打开目录
    $dir = opendir( $cache_dir );
    if ( $dir ) {
        // 遍历目录中的所有文件
        while ( ( $file = readdir( $dir ) ) !== false ) {
            // 跳过 .htaccess 文件和特殊目录
            if ( $file === '.' || $file === '..' || $file === '.htaccess' ) {
                continue;
            }
            
            $file_path = $cache_dir . '/' . $file;
            
            // 只计算常规文件
            if ( is_file( $file_path ) ) {
                $stats['cache_files']++;
                $stats['cache_size'] += filesize( $file_path );
            }
        }
        
        closedir( $dir );
    }
    
    return $stats;
}

/**
 * 缓存中间件 - 处理 API 请求的缓存
 */
function wp_rest_api_huiyan_cache_middleware() {
    // 检查是否应该缓存该请求
    if ( ! wp_rest_api_huiyan_should_cache_request() ) {
        return;
    }
    
    // 生成缓存键
    $cache_key = wp_rest_api_huiyan_generate_cache_key();
    
    // 尝试获取缓存内容
    $cached_response = wp_rest_api_huiyan_get_cache( $cache_key );
    
    if ( $cached_response ) {
        // 发送缓存的响应
        header( 'Content-Type: application/json; charset=UTF-8' );
        header( 'X-WP-REST-API-Cache: HIT' );
        
        // 恢复缓存的响应头
        if ( isset( $cached_response['headers'] ) ) {
            foreach ( $cached_response['headers'] as $header => $value ) {
                if ( ! in_array( strtolower( $header ), array( 'content-type', 'content-length' ) ) ) {
                    header( sprintf( '%s: %s', $header, $value ) );
                }
            }
        }
        
        echo json_encode( $cached_response['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        exit;
    }
    
    // 如果缓存不存在，设置响应拦截器来缓存响应
    ob_start();
    
    add_action( 'shutdown', function() use ( $cache_key ) {
        // 获取当前缓冲区的内容
        $output = ob_get_clean();
        
        // 检查是否是 JSON 响应
        $content_type = '';
        foreach ( headers_list() as $header ) {
            if ( strpos( strtolower( $header ), 'content-type:' ) === 0 ) {
                $content_type = $header;
                break;
            }
        }
        
        // 只缓存 JSON 响应
        if ( strpos( strtolower( $content_type ), 'application/json' ) !== false ) {
            try {
                // 解析 JSON 响应
                $response_body = json_decode( $output, true );
                
                // 确保解析成功
                if ( json_last_error() === JSON_ERROR_NONE ) {
                    // 收集响应头
                    $response_headers = array();
                    foreach ( headers_list() as $header ) {
                        $parts = explode( ':', $header, 2 );
                        if ( count( $parts ) === 2 ) {
                            $response_headers[ trim( $parts[0] ) ] = trim( $parts[1] );
                        }
                    }
                    
                    // 缓存响应
                    $cached_data = array(
                        'body' => $response_body,
                        'headers' => $response_headers,
                        'timestamp' => time()
                    );
                    
                    wp_rest_api_huiyan_set_cache( $cache_key, $cached_data );
                    
                    // 添加缓存标记
                    header( 'X-WP-REST-API-Cache: MISS' );
                }
            } catch ( Exception $e ) {
                // 忽略缓存错误
            }
        }
        
        // 输出响应内容
        echo $output;
    }, 0 );
}

/**
 * 清除缓存的定时任务
 */
function wp_rest_api_huiyan_scheduled_cache_cleanup() {
    wp_rest_api_huiyan_clear_cache();
}

/**
 * 注册定时任务
 */
function wp_rest_api_huiyan_register_cron() {
    // 如果缓存功能启用，注册定时清理任务
    if ( get_option( 'wp_rest_api_huiyan_cache_enabled', true ) ) {
        if ( ! wp_next_scheduled( 'wp_rest_api_huiyan_daily_cleanup' ) ) {
            // 每天清理一次缓存
            wp_schedule_event( time(), 'daily', 'wp_rest_api_huiyan_daily_cleanup' );
        }
    } else {
        // 如果缓存功能禁用，清除定时任务
        $timestamp = wp_next_scheduled( 'wp_rest_api_huiyan_daily_cleanup' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'wp_rest_api_huiyan_daily_cleanup' );
        }
    }
}
add_action( 'admin_init', 'wp_rest_api_huiyan_register_cron' );
add_action( 'wp_rest_api_huiyan_daily_cleanup', 'wp_rest_api_huiyan_scheduled_cache_cleanup' );

/**
 * 当文章被更新时清除相关缓存
 */
function wp_rest_api_huiyan_clear_cache_on_post_update( $post_id ) {
    // 检查是否是自动保存或修订版本
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    
    // 只对发布的文章进行缓存清理
    $post_status = get_post_status( $post_id );
    if ( $post_status !== 'publish' ) {
        return;
    }
    
    // 检查是否启用了缓存
    if ( ! get_option( 'wp_rest_api_huiyan_cache_enabled', true ) ) {
        return;
    }
    
    // 清除首页和相关分类/标签的缓存
    // 获取文章的分类和标签
    $terms = wp_get_object_terms( $post_id, array( 'category', 'post_tag' ) );
    $term_ids = array();
    
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $term_ids[] = $term->term_id;
        }
    }
    
    // 清理所有缓存
    wp_rest_api_huiyan_clear_cache();
}
add_action( 'save_post', 'wp_rest_api_huiyan_clear_cache_on_post_update' );

/**
 * 当评论被添加时清除相关缓存
 */
function wp_rest_api_huiyan_clear_cache_on_comment( $comment_id, $comment_approved ) {
    // 检查是否启用了缓存
    if ( ! get_option( 'wp_rest_api_huiyan_cache_enabled', true ) ) {
        return;
    }
    
    // 只有在评论被批准时才清除缓存
    if ( $comment_approved == 1 ) {
        // 获取评论关联的文章
        $comment = get_comment( $comment_id );
        if ( $comment ) {
            wp_rest_api_huiyan_clear_cache_on_post_update( $comment->comment_post_ID );
        }
    }
}
add_action( 'comment_post', 'wp_rest_api_huiyan_clear_cache_on_comment', 10, 2 );
