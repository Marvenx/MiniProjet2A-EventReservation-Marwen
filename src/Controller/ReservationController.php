<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    #[Route('/events/{id}/reserve', name: 'reservation_new', methods: ['GET', 'POST'])]
    public function new(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        // Check if event is sold out
        if ($event->isSoldOut()) {
            $this->addFlash('danger', 'Sorry, this event is sold out!');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        $reservation = new Reservation();
        $reservation->setEvent($event);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Double-check availability (race condition protection)
            if ($event->isSoldOut()) {
                $this->addFlash('danger', 'Sorry, this event just sold out!');
                return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
            }

            $reservation->setCreatedAt(new \DateTimeImmutable());
            
            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', 'Your reservation has been confirmed! Check your email for details.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('reservation/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }
}
