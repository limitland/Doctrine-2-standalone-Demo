<?php

namespace App;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

class Application
{
    /** @var array */
    protected $config;

    /** @var Environment */
    protected $twig;

    /** @var Connection */
    protected $conn;

    /** @var EntityManagerInterface */
    protected $em;

    public function __construct()
    {
        require_once __DIR__ . '/../bootstrap.php';

        $this->config = $config;
        $this->twig = $twig;
        $this->conn = $conn;
        $this->em = $em;
    }

    /**
     * @return mixed
     */
    public function processRequest()
    {
        return parse_url($_SERVER['REQUEST_URI']);
    }

    /**
     * @param string $view
     * @return void
     */
    public function handleResponse(string $view): void
    {
        echo $view;
    }
}
