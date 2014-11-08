<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 07.11.2014
 * Time: 12:05
 */

namespace mpf\tools\install;


class File {

    protected static $tabContent = "    ";

    /**
     * @param $filePath
     * @param $original
     * @param $new
     * @return int
     */
    public static function searchAndReplace($filePath, $original, $new){
        return file_put_contents($filePath, str_replace($original, $new, file_get_contents($filePath)));
    }


    /**
     * @param $config
     * @param $filePath
     * @return int
     */
    public static function createConfig($config, $filePath){
        echo "\nPreview:\n";
        echo $t = "<?php\n return [\n" .  implode(",\n",self::toPHPString($config)) . "\n];\n";
        return file_put_contents($filePath, $t);
    }

    /**
     * Returns php code for array
     * @param array $config
     * @param string $prefix
     * @return string[]
     */
    public static function toPHPString($config, $prefix = ""){
        $r = [];

        foreach ($config as $key=>$value){
            if (is_int($key)){
                $line = $prefix . self::$tabContent;
            } else {
                $key = addslashes($key);
                $line = $prefix . self::$tabContent . "\"$key\" => ";
            }
            if (is_string($value)){
                $value = addslashes($value);
                $line .= "\"$value\"";
                $r[] = $line;
                continue;
            } elseif (is_bool($value)){
                $line .= ($value?"true":"false");
                $r[] = $line;
                continue;
            } elseif (is_int($value)){
                $line .= "$value";
                $r[] = $line;
                continue;
            }
            $line .= "[\n" .
                implode(",\n", self::toPHPString($value, $prefix.self::$tabContent))
                . "\n$prefix".self::$tabContent."]";
            $r[] = $line;
        }

        return $r;
    }
} 