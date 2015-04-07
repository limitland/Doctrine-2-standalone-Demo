<?php

define('DS', DIRECTORY_SEPARATOR);

class Application
{
    protected $config;
    protected $twig;
    protected $em;
    protected $conn;
    
    public function __construct()
    {
        require_once 'bootstrap.php';
        
        $config['base'] = $_SERVER['BASE'];
        
        $this->config = $config;
        $this->twig = $twig;
        $this->conn = $conn;
        $this->em = $em;
    }

    /**
     * Handle the client request.
     * 
     * @return unknown
     */
    public function handleRequest()
    {
        // sanitize the request uri
        $uri = $_SERVER['REQUEST_URI'];
        $uri = str_replace($this->config['base'], '', $uri);
        $uri = $this->trimpath($uri);

        $request = array();
        if( strlen($uri) > 0 ) {
          $request = explode('/', $uri);
        }

        $view = $this->handleRequests( $request );
        return $view;
    }

    /**
     * Process the response.
     * 
     * @param unknown $view
     */
    public function handleResponse( $view )
    {
        echo $view;
    }

    /**
     * Trim a path from leading or trailing spaces, dots, slashes and backslashes.
     */
    public function trimpath( $path )
    {
        return trim($path, '.\/ ');
    }
    
}
