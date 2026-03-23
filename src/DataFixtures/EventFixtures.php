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
                'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800',
            ],
            [
                'title' => 'Jazz Night Live',
                'description' => 'An evening of smooth jazz featuring local and international artists. Enjoy world-class performances in an intimate setting with premium dining options available.',
                'date' => '+3 weeks',
                'location' => 'Théâtre Municipal, Sousse',
                'seats' => 150,
                'image' => 'https://images.unsplash.com/photo-1511192336575-5a79af67a629?w=800',
            ],
            [
                'title' => 'Startup Pitch Competition',
                'description' => 'Watch innovative startups compete for funding! Ten finalist teams will present their business ideas to a panel of investors. Networking reception follows.',
                'date' => '+1 week',
                'location' => 'ISSAT Sousse Amphitheater',
                'seats' => 200,
                'image' => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=800',
            ],
            [
                'title' => 'Photography Workshop',
                'description' => 'Learn professional photography techniques from award-winning photographers. Covers composition, lighting, and post-processing. Bring your own camera.',
                'date' => '+4 weeks',
                'location' => 'Art Gallery, Port El Kantaoui',
                'seats' => 30,
                'image' => 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?w=800',
            ],
            [
                'title' => 'Mediterranean Food Festival',
                'description' => 'Celebrate the flavors of the Mediterranean! Taste authentic dishes from Tunisia, Italy, Greece, and Spain. Cooking demonstrations and live music throughout the day.',
                'date' => '+5 weeks',
                'location' => 'Marina Park, Sousse',
                'seats' => 500,
                'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800',
            ],
            [
                'title' => 'Cybersecurity Summit',
                'description' => 'Essential training for IT professionals. Learn about the latest threats, defense strategies, and compliance requirements. Certification opportunities available.',
                'date' => '+6 weeks',
                'location' => 'Hotel Mövenpick, Sousse',
                'seats' => 100,
                'image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800',
            ],
            [
                'title' => 'Classical Music Concert',
                'description' => 'The Tunisian National Orchestra performs Mozart, Beethoven, and contemporary compositions. A magical evening of classical masterpieces.',
                'date' => '+2 months',
                'location' => 'Palace of Culture, Sousse',
                'seats' => 400,
                'image' => 'https://images.unsplash.com/photo-1507838153414-b4b713384a76?w=800',
            ],
            [
                'title' => 'Web Development Bootcamp',
                'description' => 'Intensive 2-day workshop covering modern web development. Learn React, Node.js, and cloud deployment. Perfect for beginners and intermediate developers.',
                'date' => '+3 days',
                'location' => 'ISSAT Sousse Lab 3',
                'seats' => 40,
                'image' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800',
            ],
            [
                'title' => 'Art Exhibition Opening',
                'description' => 'Opening night of "Horizons" - a contemporary art exhibition featuring 20 Tunisian artists. Wine reception and artist meet-and-greet included.',
                'date' => '+10 days',
                'location' => 'Musée Archéologique, Sousse',
                'seats' => 80,
                'image' => 'https://images.unsplash.com/photo-1531243269054-5ebf6f34081e?w=800',
            ],
            [
                'title' => 'Business Networking Gala',
                'description' => 'Annual networking event for entrepreneurs and business professionals. Keynote speakers, panel discussions, and exclusive networking opportunities.',
                'date' => '+7 weeks',
                'location' => 'Sheraton Hotel, Sousse',
                'seats' => 250,
                'image' => 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800',
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
