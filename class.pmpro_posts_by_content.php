<?php

/**
 * Created by Thomas Sjolshagen at PMPro <thomas@eigthy20results.com>
 * Copyright 2016 (c) - Stranger Studios, LLC
 *
 * Class pmpro_posts_by_content is based on code from:
 * http://wordpress.stackexchange.com/questions/49549/how-to-get-posts-by-content/49556#49556
 */
class pmpro_posts_by_content
{
    protected static $content = '';
    protected static $like = true;

    /**
     * Mapper function for get_posts() supporting extra arguments 'content' and 'like'
     *
     * @param 'content' => String w/optional wildcard ('%') for free values
     * @param 'like' => true or false
     *
     * @return WP_Post
     * @since .4
     */
    public static function get( $args )
    {
        if ( isset( $args['content'] ) ) {
            // 'suppress_filters' is TRUE by default for the get_posts() function
            // it needs to be FALSE if we need the 'WHERE' filter to work
            $args['suppress_filters'] = false;
            self::$content = $args['content'];
            add_filter('posts_where', array(__CLASS__, 'where_filter'));
        }

        // isset( $args['like'] ) and self::$like = (bool) $like;
        $posts = get_posts( $args);

        if( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG )
            error_log("pmpro_posts_by_content: " . print_r($posts, true));

        return $posts;
    }

    public static function where_filter( $where )
    {
        remove_filter('posts_where', array(__CLASS__, 'where_filter'));

        if( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG )
            error_log("pmpro_posts_by_content: " . print_r( $where, true ));

        global $wpdb;
        $like = self::$like ? 'LIKE' : 'NOT LIKE';

        $extra = $wpdb->prepare('%s', self::$content);

        // reset variables
        self::$content = '';
        self::$like = true;

        $new_where = "{$where} AND post_content {$like} {$extra}";

        if( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG )
            error_log("pmpro_posts_by_content: " . print_r($new_where, true));

        return $new_where;
    }
}