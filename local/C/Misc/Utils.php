<?php

namespace C\Misc;

class Utils{

    public static $stdoutHandle;
    public static $stderrHandle;
    public static function stderr ($message) {
        fwrite(self::$stderrHandle, "$message\n");
    }

    public static function stdout ($message) {
        fwrite(self::$stdoutHandle, "$message\n");
    }

    public static function fileToEtag ($file) {
        if (is_string($file)) $file = [$file];
        $h = '-';
        foreach ($file as $i=>$f) {
            $h .= $i . '-';
            $h .= $f . '-';
            if (file_exists($f)) {
                $h .= filemtime($f) . '-';
            }
        }
        return $h;
    }

    public static function objectToArray($d) {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }
        if (is_array($d)) {
            return array_map(__METHOD__, $d);
        }
        return $d;
    }

    public static function arrayPick ($arr, $pick) {
        if (count($pick)>0 && $arr) {
            $opts = [];
            foreach($pick as $n) {
                if (is_array($arr) && isset($arr[$n])) $opts[$n] = $arr[$n];
                else if (is_object($arr) && isset($arr->{$n})) $opts[$n] = $arr->{$n};
            }
            $arr = $opts;
        }
        return $arr;
    }

    public static function arrayRemove (&$arr, $value) {
        $index = array_keys($arr, $value);
        $ret = [];
        if (count($index)) {
            $ret = array_splice($arr, $index[0], 1);
        }
        return $ret;
    }

    public static function shorten ($path) {
        $path = realpath($path);
        if (substr($path, 0, strlen(getcwd()))===getcwd()) {
            $path = substr($path, strlen(getcwd())+1);
        }
        return $path;
    }

    public static function mergeMultiBlockOptions ($options, $defaults) {
        $options = array_merge($defaults, $options);
        foreach ($defaults as $n => $d) {
            $options[$n] = array_merge($d, $options[$n]);
        }
        return $options;
    }


    /**
     * @param $dest
     * @param string $root
     * @param string $dir_sep
     * @return string
     */
    public static function relativePath($dest, $root = '', $dir_sep = DIRECTORY_SEPARATOR) {
        $root = explode($dir_sep, $root);
        $dest = explode($dir_sep, $dest);
        $path = '.';
        $fix = '';
        $diff = 0;
        for($i = -1; ++$i < max(($rC = count($root)), ($dC = count($dest)));)
        {
            if(isset($root[$i]) and isset($dest[$i]))
            {
                if($diff)
                {
                    $path .= $dir_sep. '..';
                    $fix .= $dir_sep. $dest[$i];
                    continue;
                }
                if($root[$i] != $dest[$i])
                {
                    $diff = 1;
                    $path .= $dir_sep. '..';
                    $fix .= $dir_sep. $dest[$i];
                    continue;
                }
            }
            elseif(!isset($root[$i]) and isset($dest[$i]))
            {
                for($j = $i-1; ++$j < $dC;)
                {
                    $fix .= $dir_sep. $dest[$j];
                }
                break;
            }
            elseif(isset($root[$i]) and !isset($dest[$i]))
            {
                for($j = $i-1; ++$j < $rC;)
                {
                    $fix = $dir_sep. '..'. $fix;
                }
                break;
            }
        }
        return $path. $fix;
    }

    /**
     * @return array
     */
    public static function getStackTrace () {
        $ex = new \Exception('An exception to generate a trace');
        $stack = [];
        foreach($ex->getTrace() as $trace){
            unset($trace['args']);
            $stack[] = (array)$trace;
        }
        return $stack;
    }
    /**
     * @param array $stack
     * @param $classType
     * @return array|null
     */
    public static function findCaller ($stack, $classType) {
        $caller = null;
        $lineInfo = null;
        foreach($stack as $trace) {
            if (isset($trace['class'])) {
                if ( is_subclass_of($trace['class'], $classType) || $trace['class']===$classType) {
                    $caller = $trace;
                    if (!isset($caller['line']) && $lineInfo) {
                        $caller = array_merge($lineInfo, $caller);
                    }
                }
            }
            $lineInfo = $trace;
        }
        return $caller;
    }
}
Utils::$stderrHandle = fopen('php://stderr', 'w+');
Utils::$stdoutHandle = fopen('php://stdout', 'w+');