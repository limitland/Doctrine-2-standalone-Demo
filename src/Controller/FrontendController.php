<?php

namespace App\Controller;

require_once __DIR__ . '/../Application.php';

use App\Application;
use App\Entity\Author;

class FrontendController extends Application
{
    /**
     * @return mixed
     */
    public function processRequest()
    {
        $viewdata = [
            'config' => $this->config
        ];

        $viewdata['authors'] = $this->em->getRepository(Author::class)->findAll();

        return $this->twig->render('index.html.twig', $viewdata);
    }
}

$app = new FrontendController();
$view = $app->processRequest();
$app->handleResponse($view);
