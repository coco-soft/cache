<?php
namespace coco\cache;

/**
 * Class FileCache
 * @package coco\cache
 * @date 2017-02-10
 */
class FileCache
{
    protected $cacheDir;

    /**
     * set cache dir
     * @param string $dir
     */
    public function setCacheDir($dir)
    {
        $this->cacheDir = realpath($dir);
    }

    public function getCacheDir(){
        if(!file_exists($this->cacheDir)){
            throw new Exception('Cache directory is invalided');
        }

        if(!is_writable($this->cacheDir)){
            throw new Exception('Cache directory is not writable');
        }

        return $this->cacheDir;
    }

    /**
     * get
     * @param string $key
     * @return mixed|bool false
     */
    public function get($key)
    {
        $cacheFile = $this->getCacheDir() . DIRECTORY_SEPARATOR . base64_encode($key);
        if (file_exists($cacheFile)) {
            $res = file($cacheFile);
            if ($res[0] == -1 || time() <= $res[0]) {
                return unserialize($res[1]);
            }
            unlink($cacheFile);
            return false;
        }
        return false;
    }

    /**
     * set
     * @param string $key
     * @param mixed $value
     * @param int $expire -1 forever
     * @return bool|int
     */
    public function set($key, $value, $expire = -1)
    {
        $cacheFile = $this->getCacheDir() . DIRECTORY_SEPARATOR . base64_encode($key);
        if ($expire > 0) {
            $value = (time() + $expire) . PHP_EOL . serialize($value);
            return (bool)file_put_contents($cacheFile, $value);
        } else if ($expire == -1) {
            $value = '-1' . PHP_EOL . serialize($value);
            return (bool)file_put_contents($cacheFile, $value);
        }
        return false;
    }

    /**
     * remove key
     * @param string $key
     */
    public function remove($key)
    {
        $cacheFile = $this->getCacheDir() . DIRECTORY_SEPARATOR . base64_encode($key);
        @unlink($cacheFile);
    }

    /**
     * list keys
     * @param string $pattern
     * @return array
     */
    public function keys($pattern = '*')
    {
        $list = array();
        $handle = opendir($this->getCacheDir());
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $file = base64_decode($file);
                if (!$this->keyExists($file)) {
                    continue;
                }
                if ($pattern != '*') {
                    $res = preg_match('/^' . $pattern . '$/', $file);
                    if (!$res) {
                        continue;
                    }
                }
                $list[] = $file;
            }
        }
        closedir($handle);
        return $list;
    }

    /**
     * check key if exists
     * @param string $key
     * @return bool
     */
    public function keyExists($key)
    {
        $cacheFile = $this->getCacheDir() . DIRECTORY_SEPARATOR . base64_encode($key);
        if (file_exists($cacheFile)) {
            $res = file($cacheFile);
            if ($res[0] == -1 || time() <= $res[0]) {
                return true;
            }
            unlink($cacheFile);
            return false;
        }
        return false;
    }

    /**
     * auto increment by step
     * @param $key
     * @param int $step
     * @return bool|int
     */
    public function increment($key, $step = 1)
    {
        $value = (int)$this->get($key);
        $value += $step;
        $expire = $this->getExpire($key);
        if ($expire == 0) {
            $value = 1;
            $expire = -1;
        }
        return $this->set($key, $value, $expire);
    }

    /**
     * auto decrement by step
     * @param string $key
     * @param int $step
     * @return bool|int
     */
    public function decrement($key, $step = 1)
    {
        $value = (int)$this->get($key);
        $value -= $step;
        $expire = $this->getExpire($key);
        if ($expire == 0) {
            $value = -1;
            $expire = -1;
        }
        return $this->set($key, $value, $expire);
    }

    public function getExpire($key)
    {
        $cacheFile = $this->getCacheDir() . DIRECTORY_SEPARATOR . base64_encode($key);
        $now = intval(time());
        if (file_exists($cacheFile)) {
            $res = file($cacheFile);
            if ($res[0] == -1) {
                return -1;
            } else if ($now <= $res[0]) {
                return intval($res[0]) - $now;
            }
            unlink($cacheFile);
        }
        return 0;
    }
}