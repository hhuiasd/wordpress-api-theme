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

// 确保REST API正确初始化和启用
function wp_rest_api_huiyan_ensure_rest_api() {
    // 确保REST API基础功能不被禁用
    if ( !defined( 'REST_API_REQUEST' ) ) {
        define( 'REST_API_REQUEST', true );
    }
    
    // 确保REST API正确加载
    if ( !function_exists( 'rest_get_server' ) ) {
        require_once ABSPATH . 'wp-includes/rest-api.php';
    }
    
    // 关键修复：确保REST API路由正常工作
    // 在REST API初始化之前注册必要的钩子
    add_action( 'rest_api_init', function() {
        // 确保默认的REST API路由可用
        // 不做任何阻止或重写默认路由的操作
    }, 1 );
}
add_action( 'init', 'wp_rest_api_huiyan_ensure_rest_api', 5 ); // 尽早执行，确保REST API正常初始化

// 加载必要的功能模块
function wp_rest_api_huiyan_load_modules() {
    // 临时屏蔽API优化模块，使用原生REST API
    // 仅保留管理功能（如果需要后台访问）
    if ( is_admin() ) {
        require_once WP_REST_API_HUIYAN_DIR . '/includes/admin-settings.php';
        require_once WP_REST_API_HUIYAN_DIR . '/includes/api-status.php';
        require_once WP_REST_API_HUIYAN_DIR . '/includes/api-endpoints.php'; // API端点展示功能
    }
    
    // 加载API缓存功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/api-cache.php';
    
    // 加载JWT认证功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/jwt-auth.php';
    
    // 加载安全和跨域配置功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/security-cors.php';
    
    // 加载API字段精简功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/api-fields.php';
    
    // 加载性能优化功能
    require_once WP_REST_API_HUIYAN_DIR . '/includes/optimization.php';
}
add_action( 'after_setup_theme', 'wp_rest_api_huiyan_load_modules' );

// 前端访问控制 - 限制非API请求
// 使用init钩子在WordPress处理过程早期执行访问控制
function wp_rest_api_huiyan_restrict_frontend() {
    // 获取当前请求信息
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    
    // 允许的请求类型
    $is_admin = is_admin() || strpos($request_uri, '/wp-admin/') !== false;
    $is_login = $request_uri === '/wp-login.php' || $request_uri === '/wp-register.php';
    $is_api = strpos($request_uri, '/wp-json/') !== false;
    $is_options = $request_method === 'OPTIONS';
    $is_cron = $request_uri === '/wp-cron.php';
    
    // 只允许特定类型的请求
    if ( !$is_admin && !$is_login && !$is_api && !$is_options && !$is_cron ) {
        // 清除所有可能的输出缓冲
        if ( ob_get_level() > 0 ) {
            ob_end_clean();
        }
        
        // 设置403响应头
        status_header( 403 );
        header( 'Content-Type: text/plain' );
        
        // 简单的403响应
        echo '403 Forbidden';
        exit;
    }
}
// 使用init钩子在早期执行，避免输出冲突
add_action( 'init', 'wp_rest_api_huiyan_restrict_frontend', 1 );

// 登录页面和REST API请求检查已集成到wp_rest_api_huiyan_restrict_frontend函数中

// 清理WordPress头部不必要的输出
add_action('wp_enqueue_scripts', function() {
    wp_dequeue_all_scripts();
    wp_dequeue_all_styles();
}, 999);
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

// 禁用XML-RPC功能但保留REST API功能
function wp_rest_api_huiyan_disable_xmlrpc() {
    add_filter( 'xmlrpc_enabled', '__return_false' );
    // 保留REST API相关功能
}
add_action( 'init', 'wp_rest_api_huiyan_disable_xmlrpc' );

// 访问控制已通过init钩子中的wp_rest_api_huiyan_restrict_frontend函数处理

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

// JSON导入功能已移除

// 实现父级分类ID查询子分类文章的功能
function wp_rest_api_huiyan_expand_category_query( $args, $request ) {
    // 放宽路由检查，使用strpos检查是否包含'/wp/v2/posts'
    $route = $request->get_route();
    if ( strpos( $route, '/wp/v2/posts' ) === false ) {
        return $args;
    }
    
    // 从多个来源获取categories参数
    $categories = null;
    
    // 方法1：直接从request对象获取
    $categories = $request->get_param( 'categories' );
    
    // 方法2：如果方法1失败，尝试从原始查询参数获取
    if ( $categories === null ) {
        $params = $request->get_params();
        if ( isset( $params['categories'] ) ) {
            $categories = $params['categories'];
        }
    }
    
    // 方法3：尝试从URL中直接解析
    if ( $categories === null && isset( $_SERVER['REQUEST_URI'] ) ) {
        $request_uri = $_SERVER['REQUEST_URI'];
        if ( preg_match( '/categories=([^&]+)/', $request_uri, $matches ) ) {
            $categories = $matches[1];
        }
    }
    
    // 如果找到了categories参数
    if ( $categories !== null ) {
        // 确保categories是数组格式
        if ( !is_array( $categories ) ) {
            // 处理逗号分隔的字符串
            $categories = array_filter( array_map( 'trim', explode( ',', $categories ) ) );
        }
        
        // 存储所有要查询的分类ID
        $all_category_ids = array();
        
        foreach ( $categories as $category_id ) {
            // 验证是否为有效的数字ID
            if ( is_numeric( $category_id ) ) {
                $category_id = intval( $category_id );
                
                // 添加原始分类ID
                $all_category_ids[] = $category_id;
                
                // 获取该分类的所有子分类ID
                $child_categories = get_term_children( $category_id, 'category' );
                
                // 确保$child_categories是数组且不是错误对象
                if ( !is_wp_error( $child_categories ) && is_array( $child_categories ) ) {
                    // 过滤掉无效的子分类ID
                    $valid_child_categories = array_filter( $child_categories, function( $id ) {
                        return is_numeric( $id ) && intval( $id ) > 0;
                    });
                    
                    // 将有效的子分类ID转换为整数并合并
                    $valid_child_categories = array_map( 'intval', $valid_child_categories );
                    $all_category_ids = array_merge( $all_category_ids, $valid_child_categories );
                }
            }
        }
        
        // 移除重复的ID
        $all_category_ids = array_unique( $all_category_ids );
        
        // 确保数组中的所有元素都是正整数
        $all_category_ids = array_filter( $all_category_ids, function( $id ) {
            return is_numeric( $id ) && intval( $id ) > 0;
        });
        $all_category_ids = array_map( 'intval', $all_category_ids );
        
        // 如果有有效的分类ID，更新查询参数
        if ( !empty( $all_category_ids ) ) {
            // 直接修改查询参数，确保category__in被正确设置
            $args['category__in'] = $all_category_ids;
            
            // 移除可能冲突的参数
            if ( isset( $args['category'] ) ) {
                unset( $args['category'] );
            }
            if ( isset( $args['cat'] ) ) {
                unset( $args['cat'] );
            }
            
            // 确保分类参数不会被其他方式覆盖
            unset( $args['tax_query'] ); // 移除tax_query避免冲突
        }
    }
    
    return $args;
}

// 将过滤器添加到REST API的文章查询中 - 使用最高优先级确保它在所有其他过滤器之前运行
add_filter( 'rest_post_query', 'wp_rest_api_huiyan_expand_category_query', 1, 2 );

// 添加额外的过滤器，直接影响WP_Query，确保分类查询逻辑被正确应用
function wp_rest_api_huiyan_modify_wp_query( $query ) {
    // 只处理REST API请求和文章查询
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST && $query->is_main_query() && $query->is_post_type_archive( 'post' ) ) {
        // 从请求中获取categories参数
        $categories = null;
        if ( isset( $_GET['categories'] ) ) {
            $categories = $_GET['categories'];
        }
        
        // 如果有categories参数，应用与rest_post_query相同的逻辑
        if ( $categories !== null ) {
            // 复用相同的分类扩展逻辑
            $request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
            $request->set_param( 'categories', $categories );
            $args = array();
            $args = wp_rest_api_huiyan_expand_category_query( $args, $request );
            
            // 将扩展后的分类ID应用到查询
            if ( isset( $args['category__in'] ) ) {
                $query->set( 'category__in', $args['category__in'] );
            }
        }
    }
    return $query;
}
// 使用pre_get_posts钩子，确保在查询执行前修改它
add_action( 'pre_get_posts', 'wp_rest_api_huiyan_modify_wp_query', 1 );

// 添加一个调试端点，用于测试分类查询功能
function wp_rest_api_huiyan_register_debug_endpoint() {
    register_rest_route( 'wp-rest-api-huiyan/v1', '/debug/category/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_api_huiyan_debug_category_callback',
        'permission_callback' => '__return_true', // 公开访问，生产环境中应限制
    ));
}
add_action( 'rest_api_init', 'wp_rest_api_huiyan_register_debug_endpoint' );

// 调试端点的回调函数
function wp_rest_api_huiyan_debug_category_callback( $request ) {
    $category_id = intval( $request['id'] );
    
    // 获取分类信息
    $category = get_category( $category_id );
    if ( !$category || is_wp_error( $category ) ) {
        return new WP_Error( 'invalid_category', '分类不存在', array( 'status' => 404 ) );
    }
    
    // 获取子分类
    $child_categories = get_term_children( $category_id, 'category' );
    $valid_children = array();
    if ( !is_wp_error( $child_categories ) && is_array( $child_categories ) ) {
        foreach ( $child_categories as $child_id ) {
            $child = get_category( $child_id );
            if ( $child && !is_wp_error( $child ) ) {
                $valid_children[] = array(
                    'id' => $child->term_id,
                    'name' => $child->name,
                    'count' => $child->count
                );
            }
        }
    }
    
    // 构建扩展的分类ID列表
    $expanded_ids = array( $category_id );
    foreach ( $valid_children as $child ) {
        $expanded_ids[] = $child['id'];
    }
    
    // 测试查询
    $query = new WP_Query( array(
        'category__in' => $expanded_ids,
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    $posts = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $posts[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'categories' => wp_get_post_categories( get_the_ID() )
            );
        }
        wp_reset_postdata();
    }
    
    // 返回调试信息
    return array(
        'category' => array(
            'id' => $category->term_id,
            'name' => $category->name,
            'count' => $category->count
        ),
        'child_categories' => $valid_children,
        'expanded_category_ids' => $expanded_ids,
        'posts_count' => $query->found_posts,
        'sample_posts' => $posts
    );
}

