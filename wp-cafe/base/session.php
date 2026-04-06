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
        $_SESSION[$key] = maybe_serialize($value);
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

        if ( isset( $_SESSION[$key] ) ) {
            return maybe_unserialize($_SESSION[$key]);
        }

        return $default;
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
