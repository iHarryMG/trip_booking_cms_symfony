<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Driver\Connection;

use Psr\Log\LoggerInterface;
use App\Util\Util;


class OrderController extends AbstractController
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
     * @Route("/order/list", name="get_order_list")
     */
    public function getOrderList()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_order_list()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT ot.id as order_id, user.last_name, user.first_name, ot.order_status, ot.created_at
                FROM order_trip as ot 
                INNER JOIN user 
                ON(user.id=ot.user_id)
                ORDER by ot.id DESC
            ');

        $this->logger->info("TRIP ORDER LIST RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'orderList' => $queryResult,
            );
        }else{
            $result = array(
                'orderList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('order/order_list.html.twig', $result);
    }
    
    /**
     * @Route("/order/detail/{order_id}", name="order_detail")
     */
    public function orderDetail($order_id)
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'order_detail()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT ot.user_id, ot.id as order_trip_id, trip.id as trip_id, trip.is_special, ot.invoice_num, ot.order_status, ot.updated_at, ot.total_amount, 
                    u.last_name, u.first_name, u.email, country.country_name, city.city_name, hotel.hotel_name, hotel.hotel_star, 
                    ot.adult_count, ot.children_count, ot.children_age, ot.car_price_id,
                    flight.departure_datetime, flight.arrival_datetime, flight.night_count, flight.night_count_plus, flight.direction, trip.insurance
                    FROM order_trip as ot
                    INNER JOIN user as u ON u.id=ot.user_id 
                    INNER JOIN trip_package as trip ON trip.id=ot.trip_id
                    INNER JOIN flight_info as flight ON flight.id=trip.flight_info_id 
                    INNER JOIN hotel_list as hotel ON hotel.id=trip.hotel_list_id 
                    INNER JOIN country_name as country ON country.id=flight.country_id 
                    INNER JOIN city_name as city ON city.id=flight.city_id 
                    WHERE ot.id='.$order_id
                );
        $this->logger->info("ORDER DETAIL RESULT:: ".var_export($queryResult, true));

        $roomResult = $this->connection->fetchAll('
            SELECT room_name, room_qty 
                FROM order_room
                WHERE order_id='.$order_id
                );
        
        $this->logger->info("ORDER DETAIL RESULT:: ".var_export($roomResult , true));

        $carInfo = null;
        if($queryResult[0]['car_price_id'] != null && $queryResult[0]['car_price_id'] != ''){
            $carInfo = $this->connection->fetchAll('
                SELECT car_info_id, car_info.direction as car_direction, way, adult_price, child_price
                    FROM car_price
                    INNER JOIN car_info 
                    ON car_info.id=car_price.car_info_id
                    WHERE car_price.id='.$queryResult[0]['car_price_id']
                );

            $this->logger->info("SELECTED CAR PRICE RESULT:: ".var_export($carInfo, true));
        }
        
        $photoResult = $this->connection->fetchAll('
            SELECT *
                FROM passport_photo
                where 
                    user_id='.$queryResult[0]['user_id'].' and 
                    trip_id='.$queryResult[0]['trip_id'].'
                ORDER BY id ASC
            ');
        $this->logger->info("PASSPORT PHOTO RESULT:: ".var_export($photoResult, true));
        
        
        if ($queryResult) {
            $result = array(
                'orderDetail' => $queryResult,
                'roomDetail' => $roomResult,
                'carInfo' => $carInfo,
                'passPhoto' => $photoResult,
            );
        }else{
            $result = array(
                'orderDetail' => null,
                'roomDetail' => null,
                'carInfo' => null,
                'passPhoto' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('order/order_detail.html.twig', $result);
    }
    
    /**
    * @Route("/photo/download/{photo_name}", name="download_photo")
    **/
    public function downloadPhoto($photo_name){
       $response = new BinaryFileResponse($this->getParameter('passport_photo_directory').$photo_name);
       $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$photo_name);
       return $response;
    }
    
}
