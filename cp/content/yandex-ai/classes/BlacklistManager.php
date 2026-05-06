<?php
// /fordzap.com/cp/content/yandex-ai/classes/BlacklistManager.php
class BlacklistManager {
    private $blacklistFile;
    private $blacklist = [];
    
    public function __construct() {
        $this->blacklistFile = __DIR__ . '/../blacklist.json';
        $this->load();
    }
    
    private function load() {
        if (file_exists($this->blacklistFile)) {
            $content = file_get_contents($this->blacklistFile);
            $this->blacklist = json_decode($content, true) ?: [];
        }
    }
    
    private function save() {
        file_put_contents(
            $this->blacklistFile,
            json_encode($this->blacklist, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    public function isBlacklisted($domain) {
        $domain = $this->normalizeDomain($domain);
        return isset($this->blacklist[$domain]);
    }
    
    public function add($domain, $reason, $details = []) {
        $domain = $this->normalizeDomain($domain);
        
        if (isset($this->blacklist[$domain])) {
            return false;
        }
        
        $this->blacklist[$domain] = [
            'domain' => $domain,
            'reason' => $reason,
            'details' => $details,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        $this->save();
        writeLog("Added to blacklist: $domain - $reason", 'blacklist');
        
        return true;
    }
    
    public function remove($domain) {
        $domain = $this->normalizeDomain($domain);
        
        if (isset($this->blacklist[$domain])) {
            unset($this->blacklist[$domain]);
            $this->save();
            writeLog("Removed from blacklist: $domain", 'blacklist');
            return true;
        }
        
        return false;
    }
    
    public function getAll() {
        return $this->blacklist;
    }
    
    private function normalizeDomain($domain) {
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];
        return strtolower(trim($domain));
    }
}
?>