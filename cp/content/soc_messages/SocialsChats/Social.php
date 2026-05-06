<?php
namespace SocialsChats;
abstract class Social
{
    public $icon;
    protected $extensions; #Массив расширение = тип документа (константа Attachment)
    protected $options;
    /**
     * Метод для получения разрешенных расширений файлов для загрузки в социальную сеть
    */
    public function __construct($options)
    {
        if(is_array($options)){
            $this->options = $options;
        }
    }
    public function option($name, $value = null)
    {
        if($value !== null){
            $this->options[$name] = $value;
        }
        return isset($this->options[$name]) ? $this->options[$name] : '';
    }
    public function getAllowExtensions()
    {
        $list = array();
        foreach($this->extensions as $ext => $type){
            if(is_string($ext) && $ext){
                $list[$ext] = Attachment::checkType($type);
            }
        }
        return $list;
    }
    /**
     * Метод для получения писем с API социальной сети, возвращать должен массив объектов SocialsChats\Message
     * После получения писем с $offset = 0 нужно помечать чат прочитанным
     */
    abstract public function getMessages($chatId, $offset = 0, $limit = 20);
    /**
     * Метод для получения чатов с API социальной сети, должен возвращать SocHandle, в котором ch - это CurlHandle запрос списка чатов,
     * а handle это анонимная функция для обработки результата запроса, которая принимает единственный аргумент
     * с результатом ответа в JSON формате (array) и должна возвращать массив объектов SocialsChats\Chat.
     */
    abstract public function getChats($offset = 0, $limit = 20);
    /**
     * Метод для отправки писем по API социальной сети, возвращать должен bool
     * В аргумент $attachments передается массив объектов SocialsChats\Attachment
     */
    abstract public function sendMessage($chatId, $text, array $attachments = array());
    /**
     * Метод getCountUnseenMessages должен возвращать SocHandle, в котором ch - это CurlHandle запрос определения количества
     * непрочитанных писем, а handle это анонимная функция для обработки результата запроса, которая принимает единственный аргумент
     * с результатом ответа в JSON формате (array) и должна возвращать (int) значение количества непрочитанных писем.
     * */ 
    abstract public function getCountUnseenMessages();
    abstract public function searchChats($search, $offset = 0, $limit = 20);
}