<?php
namespace  WHMCS\Module\Addon\Brizy\Common;

/**
 * Session helper class
 */
class Session {

    /**
     * Name of session container for brz data
     */
    private static  $sessionContainer = 'brz';

    /**
     * Setter
     *
     * @param string $param
     * @param string  $value
     * @return string
     */
    public static function set($param, $value) {
        if (!isset($_SESSION[self::$sessionContainer])) {
            $_SESSION[self::$sessionContainer] = [];
        }

        return $_SESSION[self::$sessionContainer][$param] = $value;
    }

    /**
     * Getter
     *
     * @param string $param
     * @return string
     */
    public static function get($param = null) {

        if ($param === null && isset($_SESSION[self::$sessionContainer])) {
            return $_SESSION[self::$sessionContainer];
        }

        if (isset($_SESSION[self::$sessionContainer]) && isset($_SESSION[self::$sessionContainer][$param])) {
            return $_SESSION[self::$sessionContainer][$param];
        }

        return null;
    }

    /**
     * Deletes session property
     *
     * @param string $param
     * @return void
     */
    public static function delete($param) {
        if (isset($_SESSION[self::$sessionContainer]) && isset($_SESSION[self::$sessionContainer][$param])) {
           unset($_SESSION[self::$sessionContainer][$param]);
        }
    }

    /**
     * Clears brz session data
     *
     * @return void
     */
    public static function clear() {
        if (isset($_SESSION[self::$sessionContainer])) {
            foreach ($_SESSION[self::$sessionContainer] as $key => $value) {
                unset($_SESSION[self::$sessionContainer][$key]);
            }
        }
    }
}
