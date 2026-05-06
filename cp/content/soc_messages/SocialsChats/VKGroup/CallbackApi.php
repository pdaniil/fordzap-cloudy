<?php
namespace SocialsChats\VKGroup;
class CallbackApi
{
    private $confirmation;
    private $secret;
    private $main;
    private $peerId;
    private $chatId;
    private $data;
    private $db;
    public function __construct($db, $main, $confirmation, $secret)
    {
        $this->confirmation = $confirmation;
        $this->db = $db;
        $this->secret = $secret;
        if(is_object($main) && is_a($main, 'SocialsChats\Main')){
            $this->main = $main;
            $data = file_get_contents('php://input'); 
            if(mb_strlen($data)){
                $data = @json_decode($data, true);
                if(is_array($data) && isset($data['type']) && isset($data['group_id']) && $this->secret == $data['secret']){
                    $this->peerId = -1 * $data['group_id'];
                    $this->data = $data;
                }
            }
        }
    }
    public function handle()
    {
        error_reporting(E_ALL);
        ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/php-e.log');
        $data = $this->data;
        if(is_array($data)){
            switch($data['type']){
                case 'confirmation':
                    return $this->ok($this->confirmation);
                case 'message_new':
                    $message = $data['object']['message'];
                    $this->chatId = $message['peer_id'];
                    $text = $message['text'];
                    if(mb_substr($text, 0, 2) === '+7'){
                        $phone = preg_replace("/[^\d]/", '', $text);
                        if(mb_strlen($phone) == 11){
                            $phone = mb_substr($phone, 1);
                            $phoneFormat = '+7 (' . mb_substr($phone, 0, 3) . ') ' . mb_substr($phone, 3, 3) . '-' . mb_substr($phone, 6, 2) . '-' . mb_substr($phone, 8, 2);
                            $user = $this->query("SELECT * FROM users WHERE phone = ?", $phoneFormat)->fetch(\PDO::FETCH_ASSOC);
                            if($user){
                                $userVkConnection = $this->query("SELECT * FROM vk_users_connections WHERE user_id=?", $user['user_id'])->fetch(\PDO::FETCH_ASSOC);
                                if($userVkConnection){
                                    if($userVkConnection['vk_user_id'] == $this->chatId){
                                        $this->send('Вы уже связали профиль для получения уведомлений');
                                    }else{
                                        $this->send('Профиль на сайте связан уже с другим аккаунтом');
                                    }
                                }else{
                                    $this->query("INSERT INTO vk_users_connections (user_id,vk_user_id,notify) VALUES (?,?,?)", $user['user_id'], $this->chatId, 1);
                                    $this->send("Аккаунт успешно связан с профилем на сайте (ID: {$user['user_id']})\nУведомления подключены\nДля отключения уведомлений напишите: Отключить уведомления");
                                }
                            }else{
                                $this->send('Профиль не найден');
                            }
                        }else{
                            $this->send('Некорректный номер телефона');
                        }
                        return $this->ok();
                    }
                    $userVkConnection = $this->query("SELECT * FROM vk_users_connections WHERE vk_user_id=?", $this->chatId)->fetch(\PDO::FETCH_ASSOC);
                    if($text == 'Отключить уведомления'){
                        if($userVkConnection){
                            $this->query("UPDATE vk_users_connections SET notify=0 WHERE id=?", $userVkConnection['id']);
                            $this->send("Уведомления отключены\nДля подключения уведомлений напишите: Подключить уведомления");
                            return $this->ok();
                        }
                    }
                    if($text == 'Подключить уведомления'){
                        if($userVkConnection){
                            $this->query("UPDATE vk_users_connections SET notify=1 WHERE id=?", $userVkConnection['id']);
                            $this->send("Уведомления подключены\nДля отключения уведомлений напишите: Отключить уведомления");
                            return $this->ok();
                        }
                    }
                break;
            }
            return $this->ok();
        }
        return false;
    }
    public function ok($result = 'ok')
    {
        echo $result;
        return true;
    }
    public function query()
    {
        $args = func_get_args();
        if($this->db && count($args) > 0){
            $query = $this->db->prepare(array_shift($args));
            if($query->execute($args)){
                return $query;
            }
        }
        return false;
    }
    public function send($message, array $attachments = array())
    {
        $socialIndex = is_object($this->main) ? $this->main->getSocialIndexByPeerId($this->peerId) : false;
        return $socialIndex ? $this->main->sendMessage($socialIndex, $this->chatId, $message, $attachments) : false;
    }
}