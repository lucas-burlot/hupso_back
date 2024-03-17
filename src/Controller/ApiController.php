<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiController extends AbstractController
{

    private $security;
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/books', name: 'app_api_books')]
    public function getBooks(Request $request, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $title = $request->query->get('title');
        $category = $request->query->get('category');
        $publicationYear = $request->query->get('publicationYear');

        if($title || $category || $publicationYear) {
            $books = $bookRepository->findByFilters($title, $category, $publicationYear);
        } else {
            $books = $bookRepository->findAll();
        }
        
        $booksData = $serializer->serialize($books, 'json', [
            'groups' => 'book_read',
        ]);

    
        return new JsonResponse($booksData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'app_api_book')]
    public function getBook($id, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $book = $bookRepository->find($id);

        if(!$book) {
            return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
        }

        $bookData = $serializer->serialize($book, 'json', [
            'groups' => 'book_read',
        ]);

        return new JsonResponse($bookData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/bookings', name: 'app_api_bookings', methods: ['POST'])]
    public function creatBookings(Request $request, BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(isset($data['book_id']) && isset($data['start_date']) && isset($data['end_date'])) {
            $book = $bookRepository->find($data['book_id']);

            if(!$book) {
                return new JsonResponse(['message' => 'Book not found'], JsonResponse::HTTP_NOT_FOUND);
            }
            
            $booking = new Booking();
            $booking->setBook($book);
            $booking->setStartDate(new \DateTime($data['start_date']));
            $booking->setEndDate(new \DateTime($data['end_date']));
            $booking->setStatus('active');
            $booking->setUser($this->security->getUser());

            $this->entityManager->persist($booking);
            $this->entityManager->flush();

        } else {

            return new JsonResponse(['message' => 'Missing book_id, start_date or end_date parameters'], JsonResponse::HTTP_BAD_REQUEST);
        }
        return new JsonResponse($data, JsonResponse::HTTP_OK, []);
    }

    #[Route('/api/bookings', name: 'app_api_bookings_get', methods: ['GET'])]
    public function getBookings(BookingRepository $bookingRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookings = $bookingRepository->findAll();

        $bookingsData = $serializer->serialize($bookings, 'json', [
            'groups' => 'booking_read',
        ]);

        return new JsonResponse($bookingsData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/bookings/user', name: 'app_api_bookings_user_get', methods: ['GET'])]
    public function getBookingsByUser(BookingRepository $bookingRepository, SerializerInterface $serializer): JsonResponse
    {
        $bookings = $bookingRepository->findByUser($this->security->getUser());

        $bookingsData = $serializer->serialize($bookings, 'json', [
            'groups' => 'booking_read',
        ]);

        return new JsonResponse($bookingsData, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/bookings/{id}/cancel', name: 'app_api_bookings_cancel', methods: ['POST'])]
    public function cancelBooking($id, BookingRepository $bookingRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        if($id === null) {
            return new JsonResponse(['message' => 'Booking Id not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $booking = $bookingRepository->find($id);
        if(!$booking) {
            return new JsonResponse(['message' => 'Booking not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $booking->setStatus('cancelled');
        $this->entityManager->flush();
        $bookingsData = $serializer->serialize($booking, 'json', [
            'groups' => 'booking_read',
        ]);

        return new JsonResponse($bookingsData, JsonResponse::HTTP_OK, [], true);
    }

    public function getUser(): UserInterface
    {
        $user = $this->security->getUser();
        return $user;
    }

}
