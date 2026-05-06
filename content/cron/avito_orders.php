<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

function pre($v)
{
    print '<pre>' . print_r($v, 1) . '</pre>';
}

class AvitoApi
{
    private $clientId, $clientSecret, $userId;
    private $url = 'https://api.avito.ru/';
    public function __construct($clientId, $clientSecret, $userId)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    public function getToken()
    {
        $tokenFile = sys_get_temp_dir() . '/avito.token';
        $token = '';
        if(!file_exists($tokenFile) || (time() - filemtime($tokenFile) > 24 * 3600)){
            $ch = curl_init($this->url . 'token/');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            )));
            $authResult = @json_decode(curl_exec($ch), true);
            curl_close($ch);
            if(is_array($authResult) && isset($authResult['access_token'])){
                file_put_contents($tokenFile, $authResult['access_token']);
                $token = $authResult['access_token'];
            }
        }else{
            $token = file_get_contents($tokenFile);
        }
        return $token;
    }
    public function api($method, array $params = array(), $requestMethod = true)
    {
        $requestMethod = (bool)$requestMethod;
        $paramsString = http_build_query($params);
        $ch = curl_init($this->url . $method . (!$requestMethod && count($params) ? '?' . $paramsString : ''));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, $requestMethod);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $this->getToken()
        ));
        if(count($params) && $requestMethod){
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return @json_decode($result, true);
    }
}
class AvitoOrders
{
    private $db;
    public function __construct()
    {
        $DP_Config = new DP_Config;//Конфигурация CMS
        //Подключение к БД
        try
        {
            $this->db = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
        }
        catch (PDOException $e) 
        {
            exit("No DB connect");
        }
        $this->db->query("SET NAMES utf8;");        
        $this->api = new AvitoApi($DP_Config->soc_messages_avito_client_id, $DP_Config->soc_messages_avito_client_secret, $DP_Config->soc_messages_avito_user_id);
    }
    public function query()
    {
        $num = func_num_args();
        $args = func_get_args();
        if(!$num){
            return false;
        }
        $sql = array_shift($args);
        $query = $this->db->prepare($sql);
        if(!$query){
            return false;
        }
        return $query->execute($args) ? $query : false;
    }
    public function api($method, $params = [], $requestMethod = false)
    {
        return $this->api->api($method, $params, $requestMethod);
    }
    public function getOrders($ids = [], $dateFrom = 0, $page = 1)
    {
        $params = [];
        if(count($ids)){
            $params['ids'] = implode(",", $ids);
        }
        if($dateFrom > 0){
            $params['dateFrom'] = $dateFrom;
        }
        $params['page'] = $page;
        $params['limit'] = 20;
        $result = $this->api('order-management/1/orders', $params);
        pre($result);
    }
}

$avitoOrders = new AvitoOrders();
$avitoOrders->getOrders();