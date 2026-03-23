<?php

namespace App\Service;

use App\Entity\Reservation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class ReservationMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $senderEmail = 'noreply@event-reservation.local'
    ) {}

    public function sendConfirmation(Reservation $reservation): void
    {
        $event = $reservation->getEvent();

        $html = $this->twig->render('emails/reservation_confirmation.html.twig', [
            'reservation' => $reservation,
            'event' => $event,
        ]);

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($reservation->getEmail())
            ->subject('Reservation Confirmed: ' . $event->getTitle())
            ->html($html);

        $this->mailer->send($email);
    }
}
