<?php

class ProxyHandler
{
    private $base_path;
    private $translated_url;
    private $curl_handler;

    function __construct($base_path, $proxy_url)
    {   
        $this->base_path = $base_path;
        $this->proxy_url = $proxy_url;

        $ret = preg_match('#' . preg_quote($this->base_path, '#') . '(?P<clean_url>.+)#', dirname($_SERVER['SCRIPT_NAME']) . $_SERVER['PATH_INFO'], $matches);
        if (!isset($matches['clean_url']))
            throw new RuntimeException('Cannot proxy request as the requested url does not contain the same base path!.');
        $this->translated_url = $proxy_url . $matches['clean_url'];
        
        if ($_SERVER['QUERY_STRING'] !== '')
            $this->translated_url .= '?' .  $_SERVER['QUERY_STRING'];

        $this->curl_handler = curl_init($this->translated_url);

        $this->setCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOption(CURLOPT_BINARYTRANSFER, true); // For images, etc.
        $this->setCurlOption(CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
        $this->setCurlOption(CURLOPT_WRITEFUNCTION, array($this,'readResponse'));
        $this->setCurlOption(CURLOPT_HEADERFUNCTION, array($this,'readHeaders'));
        $this->setCurlOption(CURLOPT_HTTPHEADER, array('X-Forwarded-Host: ' . $_SERVER['HTTP_HOST']));
        

        // Process post data.
        if (count($_POST))
        {   
            // Empty the post data
            $post=array();

            // Set the post data
            $this->setCurlOption(CURLOPT_POST, true);

            // Encode and form the post data
            foreach($_POST as $key=>$value)
            {   
                $post[] = urlencode($key)."=".urlencode($value);
            }

            $this->setCurlOption(CURLOPT_POSTFIELDS, implode('&',$post));

            unset($post);
        }
        elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') // Default request method is 'get'
        {   
            // Set the request method
            $this->setCurlOption(CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
        }

    }

    // Executes the proxy.
    public function execute()
    {
        curl_exec($this->curl_handler);
    }

    // Get the information about the request.
    // Should not be called before exec.
    public function getCurlInfo()
    {
        return curl_getinfo($this->curl_handler);
    }

    // Sets a curl option.
    public function setCurlOption($option, $value)
    {
        curl_setopt($this->curl_handler, $option, $value);
    }

    protected function readHeaders(&$cu, $string)
    {
        $length = strlen($string);
        if (preg_match(',^Location:,', $string))
        {
            $string = str_replace($this->proxy_url, $this->url, $string);
        }
        header($string);
        return $length;
    }

    protected function readResponse(&$cu, $string)
    {
        $length = strlen($string);
        echo $string;
        return $length;
    }
}
?>
