<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Driver\Connection;

use Psr\Log\LoggerInterface;
use App\Util\Util;

use App\Entity\CarInfo;
use App\Entity\CarPrice;

class CarController extends AbstractController
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
     * @Route("/car", name="car")
     */
    public function car()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'car()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('car/car_list.html.twig');
    }
    
    /**
     * @Route("/car/register", name="register_car")
     */
    public function registerCar()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'register_car()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));

        $flight_id = $this->util->isNull($request->get('flight_id'));
        $car_direction = $this->util->isNull($request->get('car_direction'));
        $adult_price_oneway = $this->util->isNull($request->get('adult_price_oneway'));
        $child_price_oneway = $this->util->isNull($request->get('child_price_oneway'));
        $adult_price_twoway = $this->util->isNull($request->get('adult_price_twoway'));
        $child_price_twoway = $this->util->isNull($request->get('child_price_twoway'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', flight_id='.$flight_id
                . ', car_direction='.$car_direction
                . ', adult_price_oneway='.$adult_price_oneway
                . ', child_price_oneway='.$child_price_oneway
                . ', adult_price_twoway='.$adult_price_twoway
                . ', child_price_twoway='.$child_price_twoway
                ));
        
        $now = new \DateTime();
        $entity = new CarInfo();
        $entityManager = $this->getDoctrine()->getManager();
        $entity->setFlightInfoId($flight_id);
        $entity->setDirection($car_direction);
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
            
        $entityManager->persist($entity);
        $entityManager->flush();

        $car_info_id = $entity->getId();
        $this->logger->info("REPO RESULT:: ".var_export('REGISTERED CAR INFO ID: '.$car_info_id, true));
        
        
        for($i=1; $i <3; $i++){
            $entity = new CarPrice();
            $entityManager = $this->getDoctrine()->getManager();
            $entity->setCarInfoId($car_info_id);
            $entity->setWay($i);
            
            if($i < 2){
                $entity->setAdultPrice($adult_price_oneway);
                $entity->setChildPrice($child_price_oneway);
            }else{
                $entity->setAdultPrice($adult_price_twoway);
                $entity->setChildPrice($child_price_twoway);
            }
            
            $entity->setCreatedAt($now);
            $entity->setUpdatedAt($now);

            $entityManager->persist($entity);
            $entityManager->flush();

            $this->logger->info("REPO RESULT:: ".var_export('REGISTERED CAR PRICE ID: '.$entity->getId(), true));
        }
       
        
        if ($car_info_id) {
            $result = array(
                'result' => $car_info_id,
            );
        }else{
            $result = array(
                'result' => null
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
    /**
     * @Route("/car/list", name="get_car_list")
     */
    public function getCarList()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_car_list()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT car_price.id as car_info_id, flight_info.direction as flight_direction, DATE_FORMAT(flight_info.departure_datetime, "%Y-%m-%d ") as departure_datetime, DATE_FORMAT(flight_info.arrival_datetime, "%Y-%m-%d ") as arrival_datetime, car_info.direction as car_direction, car_price.way, car_price.adult_price, car_price.child_price, car_info.created_at
                FROM car_info
                INNER JOIN car_price ON car_price.car_info_id=car_info.id 
                INNER JOIN flight_info ON flight_info.id=car_info.flight_info_id 
                ORDER BY car_price.id DESC
            ');
 
        $this->logger->info("CAR INFO PRICE RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'carPriceList' => $queryResult,
            );
        }else{
            $result = array(
                'carPriceList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
}
