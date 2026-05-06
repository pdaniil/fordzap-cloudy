<?php
namespace SocialsChats;
class Chat
{
    public $socialId;
    public $socialIcon;
    public $socialTitle;
    public $id;
    public $date;
    public $unseen;
    public $lastMessage;
    public $peer;
    public $item;
    public function __construct($id, $date, $unseen, $peer, $lastMessage)
    {
        $this->socialId = 0;
        $this->socialIcon = '';
        $this->socialTitle = '';
        $this->id = $id;
        $this->date = $date;
        $this->unseen = $unseen;
        $this->peer = is_object($peer) && is_a($peer, 'SocialsChats\Peer') ? $peer : false;
        $this->lastMessage = is_object($lastMessage) && is_a($lastMessage, 'SocialsChats\Message') ? $lastMessage : false;
        $this->item = false;
    }
}