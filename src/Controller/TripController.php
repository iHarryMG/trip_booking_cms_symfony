<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

use Psr\Log\LoggerInterface;
use App\Util\Util;

use App\Entity\FlightInfo;
use App\Entity\TripPackage;
use App\Entity\RoomPrice;

class TripController extends AbstractController
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
     * @Route("/trip/list", name="get_trip")
     */
    public function index()
    {
        return $this->render('trip/index.html.twig', [
            'controller_name' => 'TripController',
        ]);
    }
    
    /**
     * @Route("/trip/register/form", name="register_trip_form")
     */
    public function registerTripForm()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'hotel()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT *
                FROM country_name
                ORDER BY id ASC
            ');
        
        $hotelResult = $this->connection->fetchAll('
            SELECT id, hotel_name, hotel_star
                FROM hotel_list
                ORDER BY id ASC
            ');
        
        $roomResult = $this->connection->fetchAll('
            SELECT id, room_name
                FROM room_name
                ORDER BY id ASC
            ');
        
        $this->logger->info("COUNTRY RESULT:: ".var_export($queryResult, true));
        $this->logger->info("HOTEL RESULT:: ".var_export($hotelResult, true));
        $this->logger->info("ROOM RESULT:: ".var_export($roomResult, true));

        if ($queryResult) {
            $result = array(
                'countryNames' => $queryResult,
                'hotelNames' => $hotelResult,
                'roomNames' => $roomResult,
            );
        }else{
            $result = array(
                'countryNames' => null,
                'hotelNames' => null,
                'roomNames' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('trip/trip_form.html.twig', $result);
    }
    
    /**
     * @Route("/flight/register", name="register_flight")
     */
    public function registerFlight()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'register_flight()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));

        $country_id = $this->util->isNull($request->get('country_id'));
        $city_id = $this->util->isNull($request->get('city_id'));
        $departure_datetime = $this->util->isNull($request->get('departure_datetime'));
        $arrival_datetime = $this->util->isNull($request->get('arrival_datetime'));
        $direction = $this->util->isNull($request->get('direction'));
        $night_count = $this->util->isNull($request->get('night_count'));
        $night_count_plus = $this->util->isNull($request->get('night_count_plus'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', country_id='.$country_id
                . ', city_id='.$city_id
                . ', departure_datetime='.$departure_datetime
                . ', arrival_datetime='.$arrival_datetime
                . ', direction='.$direction
                . ', night_count='.$night_count
                . ', night_count_plus='.$night_count_plus
                ));
        
        $now = new \DateTime();
        $entity = new FlightInfo();
        $entityManager = $this->getDoctrine()->getManager();
        $entity->setCountryId($country_id);
        $entity->setCityId($city_id);
        $entity->setDepartureDatetime(new \DateTime($departure_datetime));
        $entity->setArrivalDatetime(new \DateTime($arrival_datetime));
        $entity->setDirection($direction);
        $entity->setNightCount($night_count);
        $entity->setNightCountPlus($night_count_plus);
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
            
        $entityManager->persist($entity);
        $entityManager->flush();

        $this->logger->info("REPO RESULT:: ".var_export('REGISTERED FLIGHT INFO ID: '.$entity->getId(), true));
        
        if ($entity->getId()) {
            $result = array(
                'result' => $entity->getId(),
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
     * @Route("/flight/list", name="get_flight")
     */
    public function getFlight()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_flight()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT f.id, co.country_name , ci.city_name, f.direction, f.departure_datetime, f.arrival_datetime, f.night_count, f.night_count_plus, f.created_at, f.updated_at
                FROM `flight_info` as f 
                INNER JOIN country_name as co
                INNER JOIN city_name as ci 
                ON (co.id = f.country_id and ci.id = f.city_id)
                ORDER by f.id DESC
            ');
 
        $this->logger->info("FLIGHT RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'flightList' => $queryResult,
            );
        }else{
            $result = array(
                'flightList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
    /**
     * @Route("/flight/info", name="flight_info")
     */
    public function flightInfo()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'flight_info()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT f.id, co.country_name , ci.city_name, f.direction, f.departure_datetime, f.arrival_datetime, f.night_count, f.night_count_plus, f.created_at, f.updated_at
                FROM `flight_info` as f 
                INNER JOIN country_name as co
                INNER JOIN city_name as ci 
                ON (co.id = f.country_id and ci.id = f.city_id)
                ORDER by f.id DESC
            ');
        
        $this->logger->info("FLIGHT INFO RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'flightList' => $queryResult,
            );
        }else{
            $result = array(
                'flightList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('trip/flight_list.html.twig', $result);
    }
    
    /**
     * @Route("/flight/edit/{flight_id}", name="edit_flight")
     */
    public function editFlight($flight_id)
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'edit_flight()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT f.id as flight_id,  co.id as country_id, co.country_name , ci.id as city_id, ci.city_name, f.direction, f.departure_datetime, f.arrival_datetime, f.night_count, f.night_count_plus
                FROM `flight_info` as f 
                INNER JOIN country_name as co
                INNER JOIN city_name as ci 
                ON (co.id = f.country_id and ci.id = f.city_id)
                WHERE f.id='.$flight_id
            );
        
        $this->logger->info("FLIGHT INFO RESULT:: ".var_export($queryResult, true));
        
        $countryResult = $this->connection->fetchAll('
            SELECT *
                FROM country_name
                ORDER BY id ASC
            ');
        
        $cityResult = $this->connection->fetchAll('
            SELECT * from city_name 
                WHERE country_id = (
                SELECT city.country_id
                FROM city_name as city 
                INNER JOIN flight_info as flight 
                on (city.id=flight.city_id)
                WHERE flight.id='.$flight_id.' )
                ORDER BY id ASC
            ');
        
        $this->logger->info("COUNTRY RESULT:: ".var_export($countryResult, true));
        $this->logger->info("CITY RESULT:: ".var_export($cityResult, true));

        if ($queryResult) {
            $result = array(
                'flightList' => $queryResult,
                'countryList' => $countryResult,
                'cityList' => $cityResult,
                'flight_id' => $flight_id
            );
        }else{
            $result = array(
                'flightList' => null,
                'countryList' => null,
                'cityList' => null,
                'flight_id' => null
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('trip/flight_detail.html.twig', $result);
    }
    
    /**
     * @Route("/flight/update", name="update_flight")
     */
    public function updateFlight()
    {    
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'update_flight()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $flight_id = $this->util->isNull($request->get('flight_id'));
        $country_id = $this->util->isNull($request->get('country_id'));
        $city_id = $this->util->isNull($request->get('city_id'));
        $departure_datetime = $this->util->isNull($request->get('departure_datetime'));
        $arrival_datetime = $this->util->isNull($request->get('arrival_datetime'));
        $direction = $this->util->isNull($request->get('direction'));
        $night_count = $this->util->isNull($request->get('night_count'));
        $night_count_plus = $this->util->isNull($request->get('night_count_plus'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', flight_id='.$flight_id
                . ', country_id='.$country_id
                . ', city_id='.$city_id
                . ', departure_datetime='.$departure_datetime
                . ', arrival_datetime='.$arrival_datetime
                . ', direction='.$direction
                . ', night_count='.$night_count
                . ', night_count_plus='.$night_count_plus
                ));
        
        try{
            $entityManager = $this->getDoctrine()->getManager();
            $flight = $entityManager->getRepository(FlightInfo::class)->find($flight_id);

            if (!$flight) {
                $result = array(
                    'result' => null
                );
                return $this->json($result);
            }

            $now = new \DateTime();

            $flight->setCountryId(intval($country_id));
            $flight->setCityId(intval($city_id));
            $flight->setDepartureDatetime(new \DateTime($departure_datetime));
            $flight->setArrivalDatetime(new \DateTime($arrival_datetime));
            $flight->setDirection($direction);
            $flight->setNightCount(intval($night_count));
            $flight->setNightCountPlus(intval($night_count_plus));
            $flight->setUpdatedAt($now);
        
            $entityManager->flush();
            
        } catch (\Exception $e) {
            $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'ERROR OCCURRED WHEN UPDATING FLIGHT - ID: '.$hotel_id.' ERROR MSG='.$e->getMessage()));
            $result = array(
                'result' => null
            );
        }

        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
                
        $result = array(
            'result' => $flight->getId()
        );
                
        return $this->json($result);
    }
   
    /**
     * @Route("/trip/service/register", name="register_trip_service")
     */
    public function registerTripService()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'register_trip_service()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));

        $flight_id = $this->util->isNull($request->get('flight_id'));
        $hotel_id = $this->util->isNull($request->get('hotel_id'));
        $meal = $this->util->isNull($request->get('meal'));
        $insurance = $this->util->isNull($request->get('insurance'));
        $welcome_service = $this->util->isNull($request->get('welcome_service'));
        $is_special = $this->util->isNull($request->get('is_special'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', flight_id='.$flight_id
                . ', hotel_id='.$hotel_id
                . ', meal='.$meal
                . ', insurance='.$insurance
                . ', welcome_service='.$welcome_service
                . ', is_special='.$is_special
                ));
        
        $now = new \DateTime();
        
        $entity = new TripPackage();
        $entityManager = $this->getDoctrine()->getManager();
        $entity->setFlightInfoId($flight_id);
        $entity->setHotelListId($hotel_id);
        $entity->setMeal($meal);
        $entity->setInsurance($insurance);
        $entity->setWelcomeService($welcome_service);
        $entity->setIsSpecial($is_special);
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
            
        $entityManager->persist($entity);
        $entityManager->flush();

        $this->logger->info("REPO RESULT:: ".var_export('REGISTERED TRIP PACKAGE INFO ID: '.$entity->getId(), true));
        
        if ($entity->getId()) {
            $result = array(
                'result' => $entity->getId(),
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
     * @Route("/trip/edit/{trip_id}", name="edit_trip")
     */
    public function editTrip($trip_id)
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'edit_trip()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
//        , co.country_name , ci.city_name, f.direction, f.departure_datetime, f.arrival_datetime, f.night_count, f.night_count_plus, f.created_at, f.updated_at
        
        $queryResult = $this->connection->fetchAll('
            SELECT f.id as flight_id 
                FROM `flight_info` as f 
                WHERE f.id=(select flight_info_id from trip_package WHERE id='.$trip_id.' )'
            );
        
        $hotelResult = $this->connection->fetchAll('
            SELECT hotel.id, hotel.hotel_name, city.city_name
                FROM hotel_list as hotel 
                INNER JOIN city_name as city 
                ON(city.id=hotel.city_id)
                ORDER BY city.city_name ASC
            ');
        
        $trip = $this->connection->fetchAll('
            SELECT trip.id as trip_id, trip.hotel_list_id, hotel.hotel_name , trip.is_special, trip.meal, trip.insurance, trip.welcome_service, trip.is_active
                FROM `trip_package` as trip  
                INNER JOIN hotel_list as hotel 
                ON (hotel.id=trip.hotel_list_id)
                WHERE trip.id='.$trip_id
            );
        
        
        $this->logger->info("FLIGHT INFO RESULT:: ".var_export($queryResult, true));
        
        if ($queryResult) {
            $result = array(
                'flight_id' => $queryResult[0],
                'trip' => $trip[0],
                'hotelNames' => $hotelResult,
                'trip_id' => $trip_id
            );
        }else{
            $result = array(
                'flight_id' => null,
                'trip' => null,
                'hotelNames' => null,
                'trip_id' => null
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('trip/trip_detail.html.twig', $result);
    }
    
    /**
     * @Route("/trip/update", name="update_trip")
     */
    public function updateTrip()
    {    
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'update_trip()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $trip_id = $this->util->isNull($request->get('trip_id'));
        $flight_id = $this->util->isNull($request->get('flight_id'));
        $hotel_id = $this->util->isNull($request->get('hotel_id'));
        $meal = $this->util->isNull($request->get('meal'));
        $insurance = $this->util->isNull($request->get('insurance'));
        $welcome_service = $this->util->isNull($request->get('welcome_service'));
        $is_special = $this->util->isNull($request->get('is_special'));
        $is_active = $this->util->isNull($request->get('is_active'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', trip_id='.$trip_id
                . ', flight_id='.$flight_id
                . ', hotel_id='.$hotel_id
                . ', meal='.$meal
                . ', insurance='.$insurance
                . ', welcome_service='.$welcome_service
                . ', is_special='.$is_special
                . ', is_active='.$is_active
                ));
        
        $trip_update_result = null;
        try{
            $entityManager = $this->getDoctrine()->getManager();
            $trip = $entityManager->getRepository(TripPackage::class)->find($trip_id);

            if (!$trip) {
                $result = array(
                    'result' => null
                );
                return $this->json($result);
            }

            $now = new \DateTime();
            $trip->setFlightInfoId(intval($flight_id));
            $trip->setHotelListId(intval($hotel_id));
            $trip->setMeal($meal);
            $trip->setInsurance($insurance);
            $trip->setWelcomeService($welcome_service);
            $trip->setIsSpecial(intval($is_special));
            $trip->setIsActive(intval($is_active));
            $trip->setUpdatedAt($now);
        
            $entityManager->flush();
            
            $trip_update_result = $trip->getId();
            
        } catch (\Exception $e) {
            $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'ERROR OCCURRED WHEN UPDATING TRIP - ID: '.$trip_id.' ERROR MSG='.$e->getMessage()));
            $result = array(
                'result' => null
            );
        }

        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
                
        $result = array(
            'result' => $trip_update_result
        );
                
        return $this->json($result);
    }
    
    /**
     * @Route("/trip/package/list", name="get_trip_package")
     */
    public function getTripPackage()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_trip()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT t.id, t.is_special, f.departure_datetime, f.arrival_datetime, co.country_name, ci.city_name, h.hotel_name
                FROM `trip_package` as t
                INNER JOIN flight_info as f 
                INNER JOIN country_name as co 
                INNER JOIN city_name as ci 
                INNER JOIN hotel_list as h 
                ON ( f.id=t.flight_info_id and h.id=t.hotel_list_id and co.id=f.country_id and ci.id=f.city_id )
                ORDER BY t.id DESC
            ');
 
        $this->logger->info("TRIP PACKAGE RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'tripList' => $queryResult,
            );
        }else{
            $result = array(
                'tripList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
        /**
     * @Route("/trip/info", name="trip_info")
     */
    public function tripInfo()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'trip_info()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT t.id, co.country_name, ci.city_name, f.departure_datetime, f.arrival_datetime, t.is_special, t.meal, t.insurance,t.welcome_service, t.created_at, t.is_active
                FROM `trip_package` as t
                INNER JOIN flight_info as f 
                INNER JOIN country_name as co 
                INNER JOIN city_name as ci 
                INNER JOIN hotel_list as h 
                ON ( f.id=t.flight_info_id and h.id=t.hotel_list_id and co.id=f.country_id and ci.id=f.city_id )
                ORDER BY t.id DESC
            ');
 
        $this->logger->info("TRIP PACKAGE RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'tripList' => $queryResult,
            );
        }else{
            $result = array(
                'tripList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('trip/trip_package.html.twig', $result);
    }
    
    /**
     * @Route("/room/register", name="register_room")
     */
    public function registerRoom()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'register_room()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));

        $trip_id = $this->util->isNull($request->get('trip_id'));
        $room_name_id = $this->util->isNull($request->get('room_id'));
        $price_a = $this->util->isNull($request->get('price_a'));
        $price_bb = $this->util->isNull($request->get('price_bb'));
        $price_discounted = $this->util->isNull($request->get('price_discounted'));
        $price_discounted_bb = $this->util->isNull($request->get('price_discounted_bb'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', trip_id='.$trip_id
                . ', room_name_id='.$room_name_id
                . ', price_a='.$price_a
                . ', price_bb='.$price_bb
                . ', price_discounted='.$price_discounted
                . ', price_discounted_bb='.$price_discounted_bb
                ));
        
        $now = new \DateTime();
        
        $entity = new RoomPrice();
        $entityManager = $this->getDoctrine()->getManager();
        $entity->setTripId($trip_id);
        $entity->setRoomNameId($room_name_id);
        $entity->setPriceA($price_a);
        $entity->setPriceBb($price_bb);
        $entity->setPriceDiscounted($price_discounted);
        $entity->setPriceDiscountedBb($price_discounted_bb);
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
            
        $entityManager->persist($entity);
        $entityManager->flush();

        $this->logger->info("REPO RESULT:: ".var_export('REGISTERED ROOM INFO ID: '.$entity->getId(), true));
        
        if ($entity->getId()) {
            $result = array(
                'result' => $entity->getId(),
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
     * @Route("/room/list", name="get_room")
     */
    public function getRoom()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_room()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT rp.id, rp.trip_id, rp.room_name_id, rn.room_name, h.hotel_name, h.hotel_star, rp.price_a, rp.price_bb, rp.price_discounted, t.is_special, rp.created_at 
                FROM `room_price`as rp
                INNER JOIN trip_package as t 
                INNER JOIN room_name as rn
                INNER JOIN hotel_list as h 
                ON (t.id=rp.trip_id and rn.id=rp.room_name_id and h.id=t.hotel_list_id)
                ORDER by rp.id DESC
            ');
 
        $this->logger->info("ROOM RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'roomList' => $queryResult,
            );
        }else{
            $result = array(
                'roomList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
       /**
     * @Route("/room/info", name="room_info")
     */
    public function roomInfo()
    {
         $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'room_info()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $hotel_id = $this->util->isNull($request->get('hotel_name'));
        
        if($hotel_id != null){
            $queryResult = $this->connection->fetchAll('
                SELECT rp.id, rp.trip_id, rp.room_name_id, rn.room_name, h.hotel_name, h.hotel_star, rp.price_a, rp.price_bb, rp.price_discounted, rp.price_discounted_bb, t.is_special, rp.created_at 
                    FROM `room_price`as rp
                    INNER JOIN trip_package as t 
                    INNER JOIN room_name as rn
                    INNER JOIN hotel_list as h 
                    ON (t.id=rp.trip_id and rn.id=rp.room_name_id and h.id=t.hotel_list_id)
                    WHERE h.id='.$hotel_id.'
                    ORDER by rp.id DESC
                ');
        }else{
            $queryResult = $this->connection->fetchAll('
                SELECT rp.id, rp.trip_id, rp.room_name_id, rn.room_name, h.hotel_name, h.hotel_star, rp.price_a, rp.price_bb, rp.price_discounted, rp.price_discounted_bb, t.is_special, rp.created_at 
                    FROM `room_price`as rp
                    INNER JOIN trip_package as t 
                    INNER JOIN room_name as rn
                    INNER JOIN hotel_list as h 
                    ON (t.id=rp.trip_id and rn.id=rp.room_name_id and h.id=t.hotel_list_id)
                    ORDER by rp.id DESC
                ');
        }
        
        $this->logger->info("ROOM RESULT:: ".var_export($queryResult, true));
        
        $hotelNameResult = $this->connection->fetchAll('
            SELECT h.id, h.hotel_name
                FROM hotel_list as h
                ORDER BY h.hotel_name ASC
            ');
 
        $this->logger->info("HOTEL NAME RESULT:: ".var_export($hotelNameResult, true));

        if ($queryResult) {
            $result = array(
                'roomList' => $queryResult,
            );
        }else{
            $result = array(
                'roomList' => null,
            );
        }
        
        if($hotelNameResult){
            $result['hotelNames'] = $hotelNameResult;
        }else{
            $result['hotelNames'] = null;
        }
        
        if($hotel_id){
            $result['hotel_id'] = $hotel_id;
        }else{
            $result['hotel_id'] = null;
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('hotel/room_list.html.twig', $result);
    }
    
    /**
     * @Route("/room/edit/{room_id}/{trip_id}", name="edit_room")
     */
    public function editRoom($room_id, $trip_id)
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'edit_room()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $selectedRoom = $this->connection->fetchAll('
            SELECT rp.id, rp.trip_id, rp.room_name_id, rn.room_name, h.hotel_name, h.hotel_star, rp.price_a, rp.price_bb, rp.price_discounted, rp.price_discounted_bb, t.is_special, rp.created_at 
                FROM `room_price`as rp
                INNER JOIN trip_package as t 
                INNER JOIN room_name as rn
                INNER JOIN hotel_list as h 
                ON (t.id=rp.trip_id and rn.id=rp.room_name_id and h.id=t.hotel_list_id)
                WHERE rp.id='.$room_id.'
                ORDER by rp.id DESC
            ');
        $this->logger->info("SELECTED ROOM RESULT:: ".var_export($selectedRoom, true));
        
        $selectedTrip = $this->connection->fetchAll('
            SELECT t.id, t.is_special, f.departure_datetime, f.arrival_datetime, co.country_name, ci.city_name, h.hotel_name
                FROM `trip_package` as t
                INNER JOIN flight_info as f 
                INNER JOIN country_name as co 
                INNER JOIN city_name as ci 
                INNER JOIN hotel_list as h 
                ON ( f.id=t.flight_info_id and h.id=t.hotel_list_id and co.id=f.country_id and ci.id=f.city_id )
                WHERE t.id='.$trip_id.'
                ORDER BY t.id DESC
            ');
        $this->logger->info("SELECTED TRIP PACKAGE RESULT:: ".var_export($selectedTrip, true));

        $roomResult = $this->connection->fetchAll('
            SELECT id, room_name
                FROM room_name
                ORDER BY id ASC
            ');        
        $this->logger->info("ROOM LIST RESULT:: ".var_export($roomResult, true));
        
        if ($selectedRoom) {
            $result = array(
                'room' => $selectedRoom[0],
                'trip' => $selectedTrip[0],
                'roomTypes' => $roomResult,
                'room_price_id' => $room_id,
            );
        }else{
            $result = array(
                'room' => null,
                'trip' => null,
                'roomTypes' => null,
                'room_price_id' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('hotel/room_detail.html.twig', $result);
    }
    
    /**
     * @Route("/room/update", name="update_room")
     */
    public function updateRoom()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'update_room()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));

        $room_price_id = $this->util->isNull($request->get('room_price_id'));
        $trip_id = $this->util->isNull($request->get('trip_id'));
        $room_name_id = $this->util->isNull($request->get('room_id'));
        $price_a = $this->util->isNull($request->get('price_a'));
        $price_bb = $this->util->isNull($request->get('price_bb'));
        $price_discounted = $this->util->isNull($request->get('price_discounted'));
        $price_discounted_bb = $this->util->isNull($request->get('price_discounted_bb'));
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', room_price_id='.$room_price_id
                . ', trip_id='.$trip_id
                . ', room_name_id='.$room_name_id
                . ', price_a='.$price_a
                . ', price_bb='.$price_bb
                . ', price_discounted='.$price_discounted
                . ', price_discounted_bb='.$price_discounted_bb
                ));
        
        try{
            $entityManager = $this->getDoctrine()->getManager();
            $room = $entityManager->getRepository(RoomPrice::class)->find($room_price_id);

            if (!$room) {
                $result = array(
                    'result' => null
                );
                return $this->json($result);
            }

            $now = new \DateTime();
            $room->setTripId($trip_id);
            $room->setRoomNameId($room_name_id);
            $room->setPriceA($price_a);
            $room->setPriceBb($price_bb);
            $room->setPriceDiscounted($price_discounted);
            $room->setPriceDiscountedBb($price_discounted_bb);
            $room->setUpdatedAt($now);
    
            $entityManager->flush();
            
            $result = array(
                'result' => $room->getId()
            );
            
        } catch (\Exception $e) {
            $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'ERROR OCCURRED WHEN UPDATING ROOM - ID: '.$room_price_id.' ERROR MSG='.$e->getMessage()));
            $result = array(
                'result' => null
            );
        }

        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
                
        return $this->json($result);
    }
}
