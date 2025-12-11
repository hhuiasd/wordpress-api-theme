<?php
/**
 * WP REST API by Huiyan - 对象缓存支持
 * 
 * 为 WP REST API by Huiyan 主题提供增强的对象缓存功能
 * 本文件需要复制到 wp-content 目录下才能启用
 */

// 防止直接访问
if ( ! defined( 'ABSPATH' ) ) {
    die();
}

/**
 * WP_Rest_API_Huiyan_Object_Cache 类
 * 增强 WordPress 对象缓存功能
 */
class WP_Rest_API_Huiyan_Object_Cache {
    
    /**
     * 缓存对象实例
     * 
     * @var WP_Object_Cache
     */
    private $wp_object_cache;
    
    /**
     * 缓存统计
     * 
     * @var array
     */
    public $cache_stats = array(
        'gets' => 0,
        'hits' => 0,
        'sets' => 0,
        'deletes' => 0,
        'flushes' => 0,
    );
    
    /**
     * 缓存前缀
     * 
     * @var string
     */
    private $cache_prefix = 'wp_rest_api_huiyan:';
    
    /**
     * 构造函数
     */
    public function __construct() {
        // 检查是否已加载 WordPress 对象缓存
        if ( ! class_exists( 'WP_Object_Cache' ) ) {
            require_once( ABSPATH . WPINC . '/cache.php' );
        }
        
        // 创建 WordPress 对象缓存实例
        $this->wp_object_cache = new WP_Object_Cache();
    }
    
    /**
     * 获取缓存数据
     * 
     * @param string $key 缓存键
     * @param string $group 缓存组
     * @param bool $force 是否强制从源获取
     * @param bool $found 是否找到缓存
     * @return mixed 缓存数据或 false
     */
    public function get( $key, $group = 'default', $force = false, &$found = null ) {
        $this->cache_stats['gets']++;
        
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
        }
        
        $prefixed_key = $this->get_prefixed_key( $key, $group );
        $value = $this->wp_object_cache->get( $prefixed_key, $group, $force, $found );
        
        if ( $found ) {
            $this->cache_stats['hits']++;
            
            // 记录缓存命中信息（用于调试）
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $this->log_cache_hit( $key, $group );
            }
        }
        
        return $value;
    }
    
    /**
     * 设置缓存数据
     * 
     * @param string $key 缓存键
     * @param mixed $data 缓存数据
     * @param string $group 缓存组
     * @param int $expire 过期时间（秒）
     * @return bool 是否设置成功
     */
    public function set( $key, $data, $group = 'default', $expire = 0 ) {
        $this->cache_stats['sets']++;
        
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
            
            // 为 API 请求设置默认过期时间
            if ( $expire === 0 ) {
                $expire = 3600; // 默认 1 小时
            }
        }
        
        $prefixed_key = $this->get_prefixed_key( $key, $group );
        return $this->wp_object_cache->set( $prefixed_key, $data, $group, $expire );
    }
    
    /**
     * 替换缓存数据
     * 
     * @param string $key 缓存键
     * @param mixed $data 缓存数据
     * @param string $group 缓存组
     * @param int $expire 过期时间（秒）
     * @return bool 是否替换成功
     */
    public function replace( $key, $data, $group = 'default', $expire = 0 ) {
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
        }
        
        $prefixed_key = $this->get_prefixed_key( $key, $group );
        return $this->wp_object_cache->replace( $prefixed_key, $data, $group, $expire );
    }
    
    /**
     * 添加缓存数据（如果不存在）
     * 
     * @param string $key 缓存键
     * @param mixed $data 缓存数据
     * @param string $group 缓存组
     * @param int $expire 过期时间（秒）
     * @return bool 是否添加成功
     */
    public function add( $key, $data, $group = 'default', $expire = 0 ) {
        $this->cache_stats['sets']++;
        
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
            
            // 为 API 请求设置默认过期时间
            if ( $expire === 0 ) {
                $expire = 3600; // 默认 1 小时
            }
        }
        
        $prefixed_key = $this->get_prefixed_key( $key, $group );
        return $this->wp_object_cache->add( $prefixed_key, $data, $group, $expire );
    }
    
    /**
     * 增加缓存数值
     * 
     * @param string $key 缓存键
     * @param int $offset 增加值
     * @param string $group 缓存组
     * @return int|bool 新值或 false
     */
    public function incr( $key, $offset = 1, $group = 'default' ) {
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
        }
        
        $prefixed_key = $this->get_prefixed_key( $key, $group );
        return $this->wp_object_cache->incr( $prefixed_key, $offset, $group );
    }
    
    /**
     * 减少缓存数值
     * 
     * @param string $key 缓存键
     * @param int $offset 减少值
     * @param string $group 缓存组
     * @return int|bool 新值或 false
     */
    public function decr( $key, $offset = 1, $group = 'default' ) {
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
        }
        
        $prefixed_key = $this->get_prefixed_key( $key, $group );
        return $this->wp_object_cache->decr( $prefixed_key, $offset, $group );
    }
    
    /**
     * 删除缓存数据
     * 
     * @param string $key 缓存键
     * @param string $group 缓存组
     * @param bool $deprecated 废弃参数
     * @return bool 是否删除成功
     */
    public function delete( $key, $group = 'default', $deprecated = false ) {
        $this->cache_stats['deletes']++;
        
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
        }
        
        $prefixed_key = $this->get_prefixed_key( $key, $group );
        return $this->wp_object_cache->delete( $prefixed_key, $group, $deprecated );
    }
    
    /**
     * 清理特定组的缓存
     * 
     * @param string $group 缓存组
     * @return bool 是否清理成功
     */
    public function flush_group( $group ) {
        // 为 REST API 请求添加特殊处理
        if ( $this->is_rest_api_request() ) {
            $group = 'rest_api_' . $group;
        }
        
        // 如果 WordPress 对象缓存支持 flush_group 方法，则使用它
        if ( method_exists( $this->wp_object_cache, 'flush_group' ) ) {
            return $this->wp_object_cache->flush_group( $group );
        }
        
        // 否则返回 false
        return false;
    }
    
    /**
     * 清空所有缓存
     * 
     * @return bool 是否清空成功
     */
    public function flush() {
        $this->cache_stats['flushes']++;
        return $this->wp_object_cache->flush();
    }
    
    /**
     * 关闭缓存
     * 
     * @return bool 是否关闭成功
     */
    public function close() {
        return $this->wp_object_cache->close();
    }
    
    /**
     * 获取缓存统计信息
     * 
     * @return array 缓存统计
     */
    public function stats() {
        // 计算缓存命中率
        $hit_rate = 0;
        if ( $this->cache_stats['gets'] > 0 ) {
            $hit_rate = round( ( $this->cache_stats['hits'] / $this->cache_stats['gets'] ) * 100, 2 ) . '%';
        }
        
        return array_merge( $this->cache_stats, array( 'hit_rate' => $hit_rate ) );
    }
    
    /**
     * 生成带前缀的缓存键
     * 
     * @param string $key 缓存键
     * @param string $group 缓存组
     * @return string 带前缀的缓存键
     */
    private function get_prefixed_key( $key, $group ) {
        // 为 REST API 请求添加特殊前缀
        if ( $this->is_rest_api_request() ) {
            return $this->cache_prefix . 'rest:' . $group . ':' . $key;
        }
        
        return $this->cache_prefix . $group . ':' . $key;
    }
    
    /**
     * 检查是否是 REST API 请求
     * 
     * @return bool 是否是 REST API 请求
     */
    private function is_rest_api_request() {
        // 检查是否是 REST API 请求
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        
        // 检查请求 URI 是否包含 wp-json
        if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'wp-json' ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 记录缓存命中信息
     * 
     * @param string $key 缓存键
     * @param string $group 缓存组
     */
    private function log_cache_hit( $key, $group ) {
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( "[WP REST API Cache] Cache hit: {$group}:{$key}" );
        }
    }
}

/**
 * 初始化对象缓存
 */
function wp_rest_api_huiyan_init_object_cache() {
    global $wp_object_cache;
    
    // 创建自定义对象缓存实例
    $wp_object_cache = new WP_Rest_API_Huiyan_Object_Cache();
}

// 初始化对象缓存
wp_rest_api_huiyan_init_object_cache();

/**
 * 清理 REST API 缓存
 * 
 * @param string $group 可选的缓存组
 * @return bool 是否清理成功
 */
function wp_rest_api_huiyan_clear_rest_cache( $group = null ) {
    global $wp_object_cache;
    
    // 确保对象缓存已初始化
    if ( ! $wp_object_cache || ! method_exists( $wp_object_cache, 'flush_group' ) ) {
        return false;
    }
    
    // 如果指定了组，则只清理该组
    if ( $group ) {
        return $wp_object_cache->flush_group( 'rest_api_' . $group );
    }
    
    // 否则清理所有 REST API 缓存
    // 这里我们清理一些常见的缓存组
    $groups_to_flush = array(
        'rest_api_post',
        'rest_api_page',
        'rest_api_category',
        'rest_api_tag',
        'rest_api_taxonomy',
        'rest_api_postmeta',
        'rest_api_comment',
        'rest_api_user',
        'rest_api_default',
    );
    
    $success = true;
    foreach ( $groups_to_flush as $group_to_flush ) {
        if ( ! $wp_object_cache->flush_group( $group_to_flush ) ) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * 在内容更新时清理相关缓存
 */
function wp_rest_api_huiyan_clear_cache_on_update( $post_id ) {
    // 检查是否是自动保存或修订版本
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    
    // 清理相关缓存
    wp_rest_api_huiyan_clear_rest_cache( 'post' );
    
    // 如果使用了主题的 API 缓存，也清理它
    if ( function_exists( 'wp_rest_api_huiyan_clean_all_cache' ) ) {
        wp_rest_api_huiyan_clean_all_cache();
    }
}

// 添加钩子在内容更新时清理缓存
add_action( 'save_post', 'wp_rest_api_huiyan_clear_cache_on_update' );
add_action( 'delete_post', 'wp_rest_api_huiyan_clear_cache_on_update' );
add_action( 'edit_terms', 'wp_rest_api_huiyan_clear_cache_on_update', 10, 3 );
add_action( 'delete_term', 'wp_rest_api_huiyan_clear_cache_on_update', 10, 3 );

/**
 * 获取缓存统计信息
 * 
 * @return array 缓存统计
 */
function wp_rest_api_huiyan_get_cache_stats() {
    global $wp_object_cache;
    
    if ( $wp_object_cache && method_exists( $wp_object_cache, 'stats' ) ) {
        return $wp_object_cache->stats();
    }
    
    return array();
}

/**
 * 注册缓存统计 REST API 端点
 */
function wp_rest_api_huiyan_register_cache_stats_endpoint() {
    // 注册 REST API 端点
    register_rest_route( 'wp-rest-api-huiyan/v1', '/cache/stats', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_api_huiyan_get_cache_stats_callback',
        'permission_callback' => 'wp_rest_api_huiyan_check_admin_permission',
    ));
    
    // 注册清理缓存端点
    register_rest_route( 'wp-rest-api-huiyan/v1', '/cache/clear', array(
        'methods' => 'DELETE',
        'callback' => 'wp_rest_api_huiyan_clear_cache_callback',
        'permission_callback' => 'wp_rest_api_huiyan_check_admin_permission',
        'args' => array(
            'group' => array(
                'type' => 'string',
                'description' => '要清理的缓存组',
                'required' => false,
            ),
        ),
    ));
}

/**
 * 获取缓存统计的回调函数
 * 
 * @param WP_REST_Request $request 请求对象
 * @return WP_REST_Response 响应对象
 */
function wp_rest_api_huiyan_get_cache_stats_callback( $request ) {
    $stats = wp_rest_api_huiyan_get_cache_stats();
    
    return new WP_REST_Response( array(
        'success' => true,
        'data' => $stats,
    ));
}

/**
 * 清理缓存的回调函数
 * 
 * @param WP_REST_Request $request 请求对象
 * @return WP_REST_Response 响应对象
 */
function wp_rest_api_huiyan_clear_cache_callback( $request ) {
    $group = $request->get_param( 'group' );
    $result = wp_rest_api_huiyan_clear_rest_cache( $group );
    
    if ( $result ) {
        return new WP_REST_Response( array(
            'success' => true,
            'message' => $group ? "缓存组 '{$group}' 已清理" : '所有 REST API 缓存已清理',
        ));
    } else {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => '清理缓存失败',
        ), 500 );
    }
}

/**
 * 检查管理员权限
 * 
 * @return bool 是否有权限
 */
function wp_rest_api_huiyan_check_admin_permission() {
    return current_user_can( 'manage_options' );
}

/**
 * 在 REST API 初始化时注册端点
 */
function wp_rest_api_huiyan_register_cache_endpoints() {
    // 检查是否是 REST API 请求
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        wp_rest_api_huiyan_register_cache_stats_endpoint();
    }
}

// 添加钩子注册 REST API 端点
add_action( 'rest_api_init', 'wp_rest_api_huiyan_register_cache_endpoints' );
