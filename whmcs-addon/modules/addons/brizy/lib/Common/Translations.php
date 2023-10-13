<?php

namespace  WHMCS\Module\Addon\Brizy\Common;

/**
 * Handling translations
 */
class Translations
{

    /**
     * Instance
     *
     * @var Translations|null
     */
    private static ?Translations $instance = null;

    /**
     * Translations array
     *
     * @var array
     */
    public static $_ = [];

    /**
     * Undocumented function
     *
     * @return Translations
     */
    public static function getInstance(): Translations
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
    }

    /**
     * Setter for translations
     *
     * @param array $translationsArray
     * @return void
     */
    private function setTranslations($translationsArray)
    {
        self::$_ = $translationsArray;
    }

    /**
     * Default setter for translations - from whmcs params
     *
     * @param [type] $moduleParams
     * @return Translations
     */
    public static function set($moduleParams = null)
    {

        $translations = self::getInstance();

        if (isset($moduleParams['_lang'])) {
            $translations->setTranslations($moduleParams['_lang']);
        } else {
            include ROOTDIR . "/modules/addons/brizy/lang/english.php";
            $translations->setTranslations($_ADDONLANG);
        }

        return $translations;
    }

    /**
     * Convert array to dot notation - for automatic replace
     *
     * @param string $prefix
     * @param boolean $brackets
     * @return array
     */
    public static function convertToDot($prefix = "LANG", $brackets = true)
    {
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator(self::$_));
        $result = [];
        foreach ($ritit as $leafValue) {
            $keys = [];
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }

            if ($prefix) {
                array_unshift($keys, $prefix);
            }
            $keyValue = join('.', $keys);

            if ($brackets) {
                $keyValue = '{' . $keyValue . '}';
            }

            $result[$keyValue] = $leafValue;
        }

        return $result;
    }
}

