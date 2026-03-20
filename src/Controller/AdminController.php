<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(EventRepository $eventRepository, ReservationRepository $reservationRepository): Response
    {
        $events = $eventRepository->findAllOrderedByDate();
        $totalReservations = $reservationRepository->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'events' => $events,
            'totalReservations' => $totalReservations,
        ]);
    }

    #[Route('/events/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/event_form.html.twig', [
            'form' => $form,
            'event' => $event,
            'action' => 'Create',
        ]);
    }

    #[Route('/events/{id}/edit', name: 'admin_event_edit', methods: ['GET', 'POST'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/event_form.html.twig', [
            'form' => $form,
            'event' => $event,
            'action' => 'Edit',
        ]);
    }

    #[Route('/events/{id}/delete', name: 'admin_event_delete', methods: ['POST'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'Event deleted successfully!');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/reservations', name: 'admin_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
