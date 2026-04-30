<?php
namespace WpCafe;

/**
 * Session class
 */
class Session {

    /**
     * Start session if not already started
     */
    public static function start() {
        if ( session_status() === PHP_SESSION_NONE ) {
            session_start();
        }
    }

    /**
     * Set session data
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get session data
     *
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public static function get($key, $default = null) {
        self::start();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_SESSION is server-side storage, plugin-controlled
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     *
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        self::start();
        return isset( $_SESSION[$key] );
    }

    /**
     * Remove a specific session key
     *
     * @param string $key
     */
    public static function delete($key) {
        self::start();
        unset( $_SESSION[$key] );
    }

    /**
     * Clear all session data
     */
    public static function clear() {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}
