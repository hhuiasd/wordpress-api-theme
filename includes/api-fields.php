<?php
/**
 * WP REST API by Huiyan - API字段精简模块
 * 
 * 优化REST API响应，减少不必要的字段，支持自定义字段暴露控制
 */

// 定义is_wp_rest_request函数用于检测REST API请求
if ( ! function_exists( 'is_wp_rest_request' ) ) {
    /**
     * 判断是否为REST API请求
     * 
     * @return bool 是否是REST API请求
     */
    function is_wp_rest_request() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $http_accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
        return strpos($request_uri, '/wp-json/') !== false || strpos($http_accept, 'application/json') !== false;
    }
}

// 优化API响应 - 移除不必要的字段
function wp_rest_api_huiyan_optimize_api_response($response, $post, $request) {
    if ( ! is_wp_rest_request() ) {
        return $response;
    }
    
    // 确保$response是WP_REST_Response对象
    if ( ! $response instanceof WP_REST_Response ) {
        return $response;
    }
    
    // 获取响应数据
    $data = $response->get_data();
    
    // 根据不同的文章类型优化字段
    $post_type = get_post_type($post);
    
    // 通用字段移除
    $common_removed_fields = array(
        'guid',            // GUID
        'menu_order',      // 菜单顺序
        'meta',            // 原始元数据（我们将在下面处理）
        'ping_status',     // Ping状态
        'sticky',          // 是否置顶
        'template',        // 模板
        'format',          // 格式
        '_links',          // 链接（在某些情况下可能需要）
    );
    
    // 移除通用不需要的字段
    foreach ($common_removed_fields as $field) {
        if (isset($data[$field])) {
            unset($data[$field]);
        }
    }
    
    // 优化分类和标签数据
    if (isset($data['categories']) && is_array($data['categories'])) {
        $formatted_categories = array();
        foreach ($data['categories'] as $category_id) {
            $category = get_category($category_id);
            if ($category) {
                $formatted_categories[] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                );
            }
        }
        $data['categories'] = $formatted_categories;
    }
    
    // 优化标签数据
    if (isset($data['tags']) && is_array($data['tags'])) {
        $formatted_tags = array();
        foreach ($data['tags'] as $tag_id) {
            $tag = get_tag($tag_id);
            if ($tag) {
                $formatted_tags[] = array(
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                );
            }
        }
        $data['tags'] = $formatted_tags;
    }
    
    // 处理特色图片
    if (isset($data['featured_media']) && $data['featured_media'] && $request['_embed']) {
        $media = get_post($data['featured_media']);
        if ($media) {
            $attachment_meta = wp_get_attachment_metadata($media->ID);
            $image_sizes = array();
            
            // 获取所有可用的图片尺寸
            foreach (get_intermediate_image_sizes() as $size) {
                $image = wp_get_attachment_image_src($media->ID, $size);
                if ($image) {
                    $image_sizes[$size] = array(
                        'url' => $image[0],
                        'width' => $image[1],
                        'height' => $image[2],
                    );
                }
            }
            
            $data['featured_media'] = array(
                'id' => $media->ID,
                'url' => wp_get_attachment_url($media->ID),
                'alt' => get_post_meta($media->ID, '_wp_attachment_image_alt', true),
                'title' => $media->post_title,
                'caption' => $media->post_excerpt,
                'sizes' => $image_sizes,
            );
        }
    }
    
    // 添加自定义字段到API响应
    $custom_fields = get_post_meta($post->ID);
    $exposed_meta = array();
    
    // 定义要暴露的自定义字段前缀或特定字段
    $exposed_meta_prefixes = apply_filters('wp_rest_api_huiyan_exposed_meta_prefixes', array(
        'region',         // 地区信息
        'material',       // 材料信息
        'cultural_background', // 文化背景
        'source',         // 来源信息
        'custom_',        // 自定义字段前缀
    ));
    
    foreach ($custom_fields as $key => $value) {
        // 检查字段是否应该被暴露
        foreach ($exposed_meta_prefixes as $prefix) {
            if (strpos($key, $prefix) === 0) {
                // 如果只有一个值，直接使用该值而不是数组
                $exposed_meta[$key] = count($value) === 1 ? maybe_unserialize($value[0]) : array_map('maybe_unserialize', $value);
                break;
            }
        }
    }
    
    // 添加暴露的自定义字段
    if (!empty($exposed_meta)) {
        $data['custom_fields'] = $exposed_meta;
    }
    
    // 更新响应数据
    $response->set_data($data);
    
    return $response;
}

// 应用于所有文章类型的REST API响应
add_filter('rest_prepare_post', 'wp_rest_api_huiyan_optimize_api_response', 10, 3);

// 允许自定义文章类型也应用同样的优化
function wp_rest_api_huiyan_optimize_custom_post_types() {
    $post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
    foreach ($post_types as $post_type) {
        add_filter('rest_prepare_' . $post_type, 'wp_rest_api_huiyan_optimize_api_response', 10, 3);
    }
}
add_action('init', 'wp_rest_api_huiyan_optimize_custom_post_types', 100);

// 过滤REST API可用字段 - 移除敏感字段
function wp_rest_api_huiyan_filter_fields($schema, $request) {
    // 移除用户相关端点中的敏感字段
    if (strpos($request->get_route(), '/wp/v2/users') !== false) {
        $sensitive_fields = array('slug', 'link', 'description', 'meta', 'capabilities', 'roles', 'avatar_urls');
        foreach ($sensitive_fields as $field) {
            if (isset($schema['properties'][$field])) {
                unset($schema['properties'][$field]);
            }
        }
    }
    
    return $schema;
}
add_filter('rest_prepare_user', 'wp_rest_api_huiyan_filter_user_fields', 10, 3);

// 过滤用户响应中的敏感信息
function wp_rest_api_huiyan_filter_user_fields($response, $user, $request) {
    $data = $response->get_data();
    
    // 移除敏感字段
    $sensitive_fields = array('slug', 'link', 'description', 'meta', 'capabilities', 'roles', 'avatar_urls');
    foreach ($sensitive_fields as $field) {
        if (isset($data[$field])) {
            unset($data[$field]);
        }
    }
    
    // 更新响应数据
    $response->set_data($data);
    
    return $response;
}

// 优化评论API响应
function wp_rest_api_huiyan_optimize_comment_response($response, $comment, $request) {
    if ( ! is_wp_rest_request() ) {
        return $response;
    }
    
    // 确保$response是WP_REST_Response对象
    if ( ! $response instanceof WP_REST_Response ) {
        return $response;
    }
    
    // 获取响应数据
    $data = $response->get_data();
    
    // 移除评论中的敏感字段
    $sensitive_fields = array('author_ip', 'author_user_agent', 'meta', '_links');
    foreach ($sensitive_fields as $field) {
        if (isset($data[$field])) {
            unset($data[$field]);
        }
    }
    
    // 更新响应数据
    $response->set_data($data);
    
    return $response;
}
add_filter('rest_prepare_comment', 'wp_rest_api_huiyan_optimize_comment_response', 10, 3);

// 自定义JSON错误响应格式
function wp_rest_api_huiyan_json_error_response($error) {
    // 确保是JSON请求
    if ( ! is_wp_rest_request() ) {
        return $error;
    }
    
    // 获取错误数据
    $data = $error->get_data();
    
    // 获取错误消息
    $message = '';
    if (is_wp_error($error)) {
        // 如果是WP_Error对象
        $message = $error->get_error_message();
    } elseif (isset($data['message'])) {
        // 从响应数据中获取消息
        $message = $data['message'];
    }
    
    // 标准化错误格式
    $error_data = array(
        'code' => isset($data['status']) ? $data['status'] : 400,
        'message' => $message,
        'data' => isset($data['params']) ? $data['params'] : array(),
        'timestamp' => current_time('timestamp'),
    );
    
    // 更新错误数据
    $error->set_data($error_data);
    
    return $error;
}
add_filter('rest_request_after_callbacks', 'wp_rest_api_huiyan_json_error_response');

// 优化REST API性能 - 禁用不必要的查询
function wp_rest_api_huiyan_optimize_queries($query) {
    if ( is_wp_rest_request() ) {
        // 禁用修订版本查询
        $query->set('no_found_rows', true);
        
        // 禁用自动加载元数据
        $query->set('update_post_term_cache', false);
        $query->set('update_post_meta_cache', false);
        
        // 仅加载需要的字段
        $query->set('fields', 'ids');
    }
    return $query;
}
add_filter('pre_get_posts', 'wp_rest_api_huiyan_optimize_queries');

// 启用对自定义字段的REST API支持
function wp_rest_api_huiyan_enable_custom_fields() {
    // 允许自定义字段通过REST API访问
    add_filter('is_protected_meta', 'wp_rest_api_huiyan_unprotect_meta', 10, 3);
}
add_action('init', 'wp_rest_api_huiyan_enable_custom_fields');

// 解除特定自定义字段的保护
function wp_rest_api_huiyan_unprotect_meta($protected, $meta_key, $meta_type) {
    // 定义允许通过REST API访问的字段前缀
    $allowed_prefixes = apply_filters('wp_rest_api_huiyan_allowed_meta_prefixes', array(
        'region',
        'material',
        'cultural_background',
        'source',
        'custom_',
    ));
    
    foreach ($allowed_prefixes as $prefix) {
        if (strpos($meta_key, $prefix) === 0) {
            return false;
        }
    }
    
    return $protected;
}
