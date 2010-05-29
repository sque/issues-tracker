<?php

class Uri
{
    public function __construct($resource)
    {
        $this->resource = $resource;
    }
    
    public function __toString()
    {
        return $this->resource;
    }
    
    public function redirect()
    {
        Net_HTTP_Response::redirect($this->resource);
    }
    
    public function anchor($text)
    {
        return tag('a', $text,
            array('href' => $this->resource));
    }
}

?>
