<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(EventRepository $eventRepository): Response
    {
        // Get upcoming events for homepage (limited to 6)
        $events = $eventRepository->findUpcoming();
        $featuredEvents = array_slice($events, 0, 6);

        return $this->render('event/home.html.twig', [
            'events' => $featuredEvents,
        ]);
    }

    #[Route('/events', name: 'event_index')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAllOrderedByDate();

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/events/{id}', name: 'event_show')]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }
}
