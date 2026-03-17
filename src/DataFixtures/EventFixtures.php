<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $events = [
            [
                'title' => 'Tech Conference 2026',
                'description' => 'Join us for the biggest tech conference of the year! Discover the latest innovations in AI, cloud computing, and cybersecurity. Network with industry leaders and attend hands-on workshops.',
                'date' => '+2 weeks',
                'location' => 'Convention Center, Sousse',
                'seats' => 300,
                'image' => 'tech-conference.jpg',
            ],
            [
                'title' => 'Jazz Night Live',
                'description' => 'An evening of smooth jazz featuring local and international artists. Enjoy world-class performances in an intimate setting with premium dining options available.',
                'date' => '+3 weeks',
                'location' => 'Théâtre Municipal, Sousse',
                'seats' => 150,
                'image' => 'jazz-night.jpg',
            ],
            [
                'title' => 'Startup Pitch Competition',
                'description' => 'Watch innovative startups compete for funding! Ten finalist teams will present their business ideas to a panel of investors. Networking reception follows.',
                'date' => '+1 week',
                'location' => 'ISSAT Sousse Amphitheater',
                'seats' => 200,
                'image' => 'startup-pitch.jpg',
            ],
            [
                'title' => 'Photography Workshop',
                'description' => 'Learn professional photography techniques from award-winning photographers. Covers composition, lighting, and post-processing. Bring your own camera.',
                'date' => '+4 weeks',
                'location' => 'Art Gallery, Port El Kantaoui',
                'seats' => 30,
                'image' => 'photography-workshop.jpg',
            ],
            [
                'title' => 'Mediterranean Food Festival',
                'description' => 'Celebrate the flavors of the Mediterranean! Taste authentic dishes from Tunisia, Italy, Greece, and Spain. Cooking demonstrations and live music throughout the day.',
                'date' => '+5 weeks',
                'location' => 'Marina Park, Sousse',
                'seats' => 500,
                'image' => 'food-festival.jpg',
            ],
            [
                'title' => 'Cybersecurity Summit',
                'description' => 'Essential training for IT professionals. Learn about the latest threats, defense strategies, and compliance requirements. Certification opportunities available.',
                'date' => '+6 weeks',
                'location' => 'Hotel Mövenpick, Sousse',
                'seats' => 100,
                'image' => 'cybersecurity-summit.jpg',
            ],
            [
                'title' => 'Classical Music Concert',
                'description' => 'The Tunisian National Orchestra performs Mozart, Beethoven, and contemporary compositions. A magical evening of classical masterpieces.',
                'date' => '+2 months',
                'location' => 'Palace of Culture, Sousse',
                'seats' => 400,
                'image' => 'classical-concert.jpg',
            ],
            [
                'title' => 'Web Development Bootcamp',
                'description' => 'Intensive 2-day workshop covering modern web development. Learn React, Node.js, and cloud deployment. Perfect for beginners and intermediate developers.',
                'date' => '+3 days',
                'location' => 'ISSAT Sousse Lab 3',
                'seats' => 40,
                'image' => 'web-bootcamp.jpg',
            ],
            [
                'title' => 'Art Exhibition Opening',
                'description' => 'Opening night of "Horizons" - a contemporary art exhibition featuring 20 Tunisian artists. Wine reception and artist meet-and-greet included.',
                'date' => '+10 days',
                'location' => 'Musée Archéologique, Sousse',
                'seats' => 80,
                'image' => 'art-exhibition.jpg',
            ],
            [
                'title' => 'Business Networking Gala',
                'description' => 'Annual networking event for entrepreneurs and business professionals. Keynote speakers, panel discussions, and exclusive networking opportunities.',
                'date' => '+7 weeks',
                'location' => 'Sheraton Hotel, Sousse',
                'seats' => 250,
                'image' => 'business-gala.jpg',
            ],
        ];

        foreach ($events as $eventData) {
            $event = new Event();
            $event->setTitle($eventData['title']);
            $event->setDescription($eventData['description']);
            $event->setDate(new \DateTimeImmutable($eventData['date']));
            $event->setLocation($eventData['location']);
            $event->setSeats($eventData['seats']);
            $event->setImage($eventData['image']);

            $manager->persist($event);
            
            // Store reference for potential use in other fixtures
            $this->addReference('event-' . strtolower(str_replace(' ', '-', $eventData['title'])), $event);
        }

        $manager->flush();
    }
}
