<?php
namespace SocialsChats\API;
class Api
{
    const ERROR_METHOD = 'Метод не найден';
    const ERROR_DB_CONNECT = 'Нет подключения к базе данных';
    const ERROR_NO_RIGHTS = 'Нет прав';
    const ERROR_UNKNOWN = 'Неизвестная ошибка';
    protected $db, $params, $rights;
    public function __construct($db = false, $init = true, $rights = false)
    {
        if($db){
            $this->db = $db;
        }
        $this->params = $_REQUEST;
        $this->rights = $rights;
        if($init){
            $this->handle();
        }
    }
    public function param($name, $value = null)
    {
        if($value !== null){
            $this->params[$name] = $value;
            return true;
        }else if(isset($this->params[$name])){
            return strval($this->params[$name]);
        }
        return '';
    }
    public function paramId($name, $value = null)
    {
        if($value !== null){
            return $this->param($name, intval($value));
        }
        return intval($this->param($name));
    }
    public function paramList($name, $value = null)
    {
        if($value !== null){
            if(is_array($value)){
                $value = implode(",", $value);
            }
            return $this->param($name, strval($value));
        }
        $value = explode(",", $this->param($name));
        if(count($value) == 1 && $value[0] == ''){
            $value = array();
        }
        return $value;
    }
    public function JSApi($apiUrl)
    {
        print '
        <script>
            function api(method, params, success, error)
            {
                if(typeof(params) == "function"){
                    error = success;
                    success = params;
                    params = {};
                }
                if(typeof(params) !== "object"){
                    params = {};
                }
                if(window.api_hash !== undefined){
                    params.api_hash = window.api_hash;
                }
                params.api = method;
                let url = "'. $apiUrl . '";
                $.post(url, params, function(res){
                    if(res.success !== undefined && typeof(success) == "function"){
                        success.call(null, res.success);
                        return true;
                    }
                    if(res.error === undefined){
                        res = {
                            error: "'. self::ERROR_UNKNOWN .'"
                        };
                    }
                    if(res.error !== undefined && typeof(error) == "function"){
                        error.call(null, res.error);
                        return true;
                    }
                }).fail(function(){
                    if(typeof(error) == "function"){
                        error.call(null, {
                            error: "'. self::ERROR_UNKNOWN .'"
                        });
                    }
                });
            }
        </script>
        ';
    }
    public function handle()
    {
        if(isset($_REQUEST['api'])){
            $method = 'api' . $_REQUEST['api'];
            $result = $this->error(self::ERROR_METHOD);
            if(!is_object($this->db)){
                $dbError = strval($this->db);
                $result = $this->error(mb_strlen($dbError) ? $dbError : self::ERROR_DB_CONNECT);
            }else if(!$this->rights){
                $result = $this->error(self::ERROR_NO_RIGHTS);
            }else if(method_exists($this, $method)){
                $result = call_user_func( array($this, $method));
            }
            if(!isset($result['error']) && !isset($result['success'])){
                $result = $this->success($result);
            }
            header('Content-Type: application/json; charset=utf-8;');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            die;
        }
    }
    public function query()
    {
        if(!is_object($this->db)){
            return false;
        }
        $args = func_get_args();
        if(count($args) == 0){
            return false;
        }
        $sql = array_shift($args);
        $query = $this->db->prepare($sql);
        if(!$query){
            return false;
        }
        if(!$query->execute($args)){
            return false;
        }
        return $query;
    }
    public function error($msg)
    {
        return array(
            'error' => $msg
        );
    }
    public function success($data)
    {
        return array(
            'success' => $data
        );
    }
}