<?php
// /fordzap.com/cp/content/yandex-ai/classes/CacheManager.php
class CacheManager {
    private $cacheDir;
    
    public function __construct($subdir = 'responses') {
        $this->cacheDir = CACHE_DIR . '/' . $subdir;
    }
    
    public function getCacheFile($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    public function get($key, $ttl = 3600) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        if (time() - filemtime($file) > $ttl) {
            unlink($file);
            return null;
        }
        
        $data = file_get_contents($file);
        return unserialize($data);
    }
    
    public function set($key, $data) {
        $file = $this->getCacheFile($key);
        file_put_contents($file, serialize($data));
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
?>