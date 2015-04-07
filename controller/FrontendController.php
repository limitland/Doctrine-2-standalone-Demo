<?php

require_once __DIR__ . '/../app/app.php';

class FrontendController extends Application
{
    public function handleRequests( array $request )
    {
        $view = '';
        
        $viewdata = array(
            'config' => $this->config
        );
        
        $viewdata['authors'] = $this->em->getRepository('Entity\Author')->findAll();
            
        $view = $this->twig->render('index.html.twig', $viewdata);
        
        return $view;
    }
}

$app = new FrontendController();
$view = $app->handleRequest();
$app->handleResponse( $view );
