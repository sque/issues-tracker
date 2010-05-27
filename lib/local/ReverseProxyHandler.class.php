<?php

//! Handler to perfrom reverse proxying
class ReverseProxyHandler
{
    //! The base path of the front server
    private $front_base_path;
    
    //! The back server full url
    private $back_url;
    
    //! The current request uri
    private $request_uri;
    
    //! The Curl handler
    private $curl_handler;
    
    //! Received data
    private $data = '';

    //! Construct a reverse proxy handler
    function __construct($front_base_path, $back_url, $request_uri = null)
    {   
        // Normalize parameters
        if (substr($front_base_path, -1) == '/')
            $front_base_path = substr($front_base_path, 0, -1);
        if (substr($back_url, -1) == '/')
            $back_url = substr($back_url, 0, -1);
        if ($request_uri === null)
            $request_uri =  $_SERVER['REQUEST_URI'];

        // Save parameters            
        $this->front_base_path = $front_base_path;
        $this->back_url = $back_url;
        $this->request_uri = $request_uri;

        $this->curl_handler = curl_init($this->translate_url_front_to_back($request_uri));

        $this->set_curl_option(CURLOPT_RETURNTRANSFER, true);
        $this->set_curl_option(CURLOPT_BINARYTRANSFER, true); // For images, etc.
        $this->set_curl_option(CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   // Forward user agent
        $this->set_curl_option(CURLOPT_WRITEFUNCTION, array($this,'read_response'));
        $this->set_curl_option(CURLOPT_HEADERFUNCTION, array($this,'read_headers'));
//        $this->set_curl_option(CURLOPT_HTTPHEADER, array('X-Forwarded-Host: ' . $_SERVER['HTTP_HOST']));
        
        // Process post data.
        if (count($_POST))
        {   
            // Encode and form the post data
            $post=array();
            foreach($_POST as $key=>$value)
            {   
                $post[] = urlencode($key)."=".urlencode($value);
            }

            // Set the post data
            $this->set_curl_option(CURLOPT_POST, true);
            $this->set_curl_option(CURLOPT_POSTFIELDS, implode('&',$post));
        }
        elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') // Default request method is 'get'
        {   
            // Set the request method
            $this->set_curl_option(CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
        }

    }

    //! Translate a frontend url to a backend one
    public function translate_url_front_to_back($uri)
    {
        // Find match point of front uri
        preg_match('#' . preg_quote($this->front_base_path, '#') . '(?P<clean_url>.+)#', $uri, $matches);
        if (!isset($matches['clean_url']))
            throw new RuntimeException('Cannot proxy request as the requested url does not contain the same base path!.');

        $suburl = $matches['clean_url'];
        return $this->back_url . $suburl;
    }
    
    //! Translate a backend url to a frontend one
    public function translate_url_back_to_front($url)
    {
        if ($url[0] === '/')
            return $this->front_base_path . $url;
        else if (substr($url, 0, 7) == 'http://')
        {
            if (substr($url, 0, strlen($this->back_url)) !== $this->back_url)
                return $url;    // Foreign url, leave it untouched
            else
                return $this->front_base_path . substr($url, strlen($this->back_url));
        }
        else
            return $url;
    }
    
    //! Executes the proxy.
    public function execute()
    {
        curl_exec($this->curl_handler);
        $content_type = $this->curl_getinfo(CURLINFO_CONTENT_TYPE);

        if ($content_type == 'text/html')
            echo $this->translate_document($this->data);
        else
            echo $this->data;
    }

    //! Get the information about the request.
    //! Should not be called before exec.
    public function curl_getinfo($opt = 0)
    {
        return curl_getinfo($this->curl_handler, $opt);
    }

    // Sets a curl option.
    public function set_curl_option($option, $value)
    {
        curl_setopt($this->curl_handler, $option, $value);
    }

    //! Read the headers from server
    protected function read_headers(&$cu, $string)
    {
        $length = strlen($string);
        if (preg_match('/^Location:\s+(?P<url>.+?)\s/i', $string, $matches))
            if (isset($matches['url']))
                $string = 'Location: ' . $this->translate_url_back_to_front($matches['url']);
        header($string);
        return $length;
    }

    //! Parse and translate all urls on documetn
    public function translate_document($data)
    {
        try
        {
            // Specify configuration
            $config = array(
                'clean' => false,
                'output-xhtml'   => true,
                'indent' => true,
                'alt-text' => 'Here was an image'
            );

            // Tidy
            $tidy = new tidy;
            $data = $tidy->repairString($data, $config, 'utf8');

            // Dom parsing
            libxml_use_internal_errors(true);
            $dom = new SimpleXMLElement($data);
        
            // Parse urls
            foreach($dom->xpath('//*[@src]') as $el)
                $el['src'] = $this->translate_url_back_to_front((string)$el['src']);

            foreach($dom->xpath('//*[@href]') as $el)
                $el['href'] = $this->translate_url_back_to_front((string)$el['href']);

            foreach($dom->xpath('//*[@action]') as $el)
                $el['action'] = $this->translate_url_back_to_front((string)$el['action']);
                
            return $dom->asXML();
        }
        catch(Exception $e)
        {   
            return $data;
        }
    }
    
    //! Read the response from server
    protected function read_response(&$cu, $string)
    {
        $orig_length = strlen($string);
        $this->data .= $string;
        
        return $orig_length;
    }
}
?>
