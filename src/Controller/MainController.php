<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Driver\Connection;


use Psr\Log\LoggerInterface;
use App\Util\Util;

class MainController extends AbstractController
{
    private $logger;
    private $util;
    private $connection;
    
    public function __construct(LoggerInterface $logger, Connection $connection)
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->util = new Util();
    }
    
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        return $this->forward('App\Controller\OrderController::getOrderList');
    }
    
    /**
     * @Route("/login", name="login")
     */
    public function login()
    {
        return $this->render('main/login.html.twig');
    }
    
    /**
     * @Route("/login/submit", name="login_auth")
     */
    public function loginAuth()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'login_auth()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        
        if($request->isMethod('POST') && $request->get('user_id') && $request->get('user_pw')){
            
            
            
            $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
            return $this->forward('App\Controller\OrderController::getOrderList');
        }else{
            $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
            return $this->render('main/login.html.twig');
        }
        
        
    }
}
