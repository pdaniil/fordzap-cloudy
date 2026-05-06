<?php
namespace SocialsChats;
class Handle
{
    public $ch;
    public $handle;
    public function __construct($ch, $handle)
    {
        if(is_resource($ch) && get_resource_type($ch) == "curl"){
            $this->ch = $ch;
        }
        if(is_callable($handle)){
            $this->handle = $handle;
        }
    }
}