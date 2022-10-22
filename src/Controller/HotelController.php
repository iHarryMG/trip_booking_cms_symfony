<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;


use Psr\Log\LoggerInterface;
use App\Util\Util;

use App\Entity\HotelList;
use App\Entity\HotelPhoto;
use App\Form\HotelPhotoType;


class HotelController extends AbstractController
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
     * @Route("/hotel", name="hotel")
     */
    public function hotel()
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
        
        $this->logger->info("COUNTRY RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'countryNames' => $queryResult,
            );
        }else{
            $result = array(
                'countryNames' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('hotel/hotel_list.html.twig', $result);
    }
    
    /**
     * @Route("/hotel/list", name="get_hotel_list")
     */
    public function getHotelList()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_hotel_list()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $queryResult = $this->connection->fetchAll('
            SELECT h.id, h.hotel_name, h.hotel_star, h.created_at, ci.city_name, co.country_name , 
                ( SELECT count(*)
                    FROM hotel_photo
                    where hotel_list_id = h.id
                ) as img_count
                
                FROM `hotel_list` as h
                INNER JOIN city_name as ci
                INNER JOIN country_name as co
                ON ( ci.id = h.city_id and co.id = ci.country_id )
                ORDER BY h.id DESC
            ');
        
//        $imageResult = $this->connection->fetchAll('
//            SELECT count(*)
//                FROM hotel_photo
//                where hotel_list_id='.$hotel_id.'
//                ORDER BY id ASC
//            ');
        
        $this->logger->info("HOTEL LIST RESULT:: ".var_export($queryResult, true));

        if ($queryResult) {
            $result = array(
                'hotelList' => $queryResult,
            );
        }else{
            $result = array(
                'hotelList' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
     /**
     * @Route("/city/list", name="get_city")
     */
    public function getCityList()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_city()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $countryId = $request->get('country_id');
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::COUNTRY ID='.$countryId));
        
        $cityResult = $this->connection->fetchAll('
            SELECT *
                FROM city_name
                where country_id='.$countryId.'
                ORDER BY id ASC
            ');
        
        $this->logger->info("CITY RESULT:: ".var_export($cityResult, true));

        if ($cityResult) {
            $result = array(
                'cityNames' => $cityResult,
            );
        }else{
            $result = array(
                'cityNames' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
    /**
     * @Route("/hotel/register", name="register_hotel")
     */
    public function registerHotel()
    {  
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'register_hotel()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $city_id = $request->get('city_id');
        $hotel_name = $request->get('hotel_name');
        $hotel_star = $request->get('hotel_star');
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', CITY ID='.$city_id
                . ', HOTEL NAME='.$hotel_name
                . ', HOTEL STAR='.$hotel_star
                ));
        
        $now = new \DateTime();
        $hotel = new HotelList();
        $entityManager = $this->getDoctrine()->getManager();
        $hotel->setCityId($city_id);
        $hotel->setHotelName($hotel_name);
        $hotel->setHotelStar($hotel_star);
        $hotel->setCreatedAt($now);
        $hotel->setUpdatedAt($now);
            
        $entityManager->persist($hotel);
        $entityManager->flush();

        $this->logger->info("REPO RESULT:: ".var_export('REGISTERED HOTEL ID: '.$hotel->getId(), true));
        
        if ($hotel->getId()) {
            $result = array(
                'result' => $hotel->getId(),
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
     * @Route("/hotel/image/upload", name="upload_image")
     */
    public function uploadImage()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'upload_image()';
        $logId = 'client_ip='.$request->getClientIp();
        $hotel_id = $request->get('hotel_id');
        
        if(empty($request->get('hotel_photo')['is_special'])){
            $is_special = 0;
        }else{
            $is_special = $request->get('hotel_photo')['is_special'] == 1 ? 1 : 0;
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETERS: hotelID='.$hotel_id.', is_special='.$is_special));
        
        $hotelPhoto = new HotelPhoto();
        $now = new \DateTime();
        $form = $this->createForm(HotelPhotoType::class, $hotelPhoto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $request->files->get('hotel_photo')['hotel_img'];

            $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('hotel_images_directory'),
                        $fileName
                    );

                    $hotelPhoto->setHotelImg($fileName);
                    $hotelPhoto->setHotelListId($hotel_id);
                    $hotelPhoto->setIsActive( ($is_special == 0 ? 2 : 1) );
                    $hotelPhoto->setCreatedAt($now);
                    $hotelPhoto->setUpdatedAt($now);
                    
                    $this->logger->info("HOTEL PHOTO RESULT:: ".var_export( $hotelPhoto, true));
                    
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($hotelPhoto);
                    $em->flush();

                } catch (FileException $e) {
                    $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'EXCEPTION OCCURRED!'));
                }
        }
            
//            foreach($files as $file){
//            
//                $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();
//
//                // Move the file to the directory where brochures are stored
//                try {
//                    $file->move(
//                        $this->getParameter('images_directory'),
//                        $fileName
//                    );
//
//                    // updates the 'brochure' property to store the PDF file name
//                    // instead of its contents
//                    $hotelPhoto->setHotelImg($fileName);
//
//                } catch (FileException $e) {
//                    $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'EXCEPTION OCCURRED!'));
//                }
//            }

        return $this->render('hotel/hotel_image_upload.html.twig', [
            'form' => $form->createView(),
            'hotel_id' => $hotel_id,
        ]);
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }
    
    /**
     * @Route("/hotel/photo/list", name="get_hotel_image")
     */
    public function getHotelImage()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'get_hotel_image()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $hotel_id = $request->get('hotel_id');
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::HOTEL ID='.$hotel_id));
        
        $imageResult = $this->connection->fetchAll('
            SELECT *
                FROM hotel_photo
                where hotel_list_id='.$hotel_id.'
                ORDER BY id ASC
            ');
        
        $this->logger->info("HOTEL PHOTO RESULT:: ".var_export($imageResult, true));

        if ($imageResult) {
            $result = array(
                'hotelPhoto' => $imageResult,
                'hotel_id' => $hotel_id,
            );
        }else{
            $result = array(
                'hotelPhoto' => null,
                'hotel_id' => null,
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->json($result);
    }
    
    /**
     * @Route("/hotel/edit", name="edit_hotel")
     */
    public function editHotel()
    {
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'edit_hotel()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $hotel_id = $request->get('hotel_id');
        
        $queryResult = $this->connection->fetchAll('
            SELECT h.id, h.hotel_name, h.hotel_star, ci.id as city_id, ci.city_name, co.country_name, co.id as country_id
                FROM `hotel_list` as h
                INNER JOIN city_name as ci
                INNER JOIN country_name as co
                ON ( ci.id = h.city_id and co.id = ci.country_id )
                where h.id='.$hotel_id.'
                ORDER BY h.id DESC
            ');
        
        $this->logger->info("HOTEL LIST RESULT:: ".var_export($queryResult, true));
        
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
                INNER JOIN hotel_list as hotel 
                on (city.id=hotel.city_id)
                WHERE hotel.id='.$hotel_id.' )
                ORDER BY id ASC
            ');
        
        $this->logger->info("COUNTRY RESULT:: ".var_export($countryResult, true));
        $this->logger->info("CITY RESULT:: ".var_export($cityResult, true));

        if ($queryResult) {
            $result = array(
                'hotelList' => $queryResult,
                'countryList' => $countryResult,
                'cityList' => $cityResult,
                'hotel_id' => $hotel_id
            );
        }else{
            $result = array(
                'hotelList' => null,
                'countryList' => null,
                'cityList' => null,
                'hotel_id' => null
            );
        }
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
        
        return $this->render('hotel/hotel_detail.html.twig', $result);
    }
    
    /**
     * @Route("/hotel/update", name="update_hotel")
     */
    public function updateHotel()
    {    
        $this->logger->info('================================================');
        $request = Request::createFromGlobals();
        
        $logStep = 0;
        $tagName = 'updateHotel()';
        $logId = 'client_ip='.$request->getClientIp();
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'START'));
        
        $hotel_id = $request->get('hotel_id');
        $hotel_name = $request->get('hotel_name');
        $hotel_star = $request->get('hotel_star');
        $city_id = $request->get('city_id');
        
        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'PARAMETER::'
                . ', CITY ID='.$city_id
                . ', HOTEL NAME='.$hotel_name
                . ', HOTEL STAR='.$hotel_star
                ));
        
        try{
            $entityManager = $this->getDoctrine()->getManager();
            $hotel = $entityManager->getRepository(HotelList::class)->find($hotel_id);

            if (!$hotel) {
                $result = array(
                    'result' => null
                );
                return $this->json($result);
            }

            $now = new \DateTime();
            $hotel->setCityId(intval($city_id));
            $hotel->setHotelName($hotel_name);
            $hotel->setHotelStar(intval($hotel_star));
            $hotel->setUpdatedAt($now);
            $entityManager->flush();
            
        } catch (\Exception $e) {
            $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'ERROR OCCURRED WHEN UPDATING HOTEL - ID: '.$hotel_id.' ERROR MSG='.$e->getMessage()));
            $result = array(
                'result' => null
            );
        }

        $this->logger->info($this->util->generateLogMessage($tagName, $logId, $logStep, 'END'));
                
        $result = array(
            'result' => $hotel->getId()
        );
                
        return $this->json($result);
    }
    
    
}
