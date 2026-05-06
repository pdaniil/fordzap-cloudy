<?php
namespace SocialsChats;
class Peer
{
    public $id;
    public $name;
    public $photo;
    public $link;
    public function __construct($id, $name, $photo, $link)
    {
        $this->id = $id;
        $this->name = $name;
        $this->photo = $photo;
        $this->link = $link;
    }
}