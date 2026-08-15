<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\AttendanceStatus;
use App\Booking\Domain\Enum\BookingStatus;
use App\Identity\Domain\Entity\OrganizerApplication;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\Role;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use App\Workshop\Domain\Enum\WorkshopMode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public const DEMO_PASSWORD = 'SkillSpot2026!';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->user('admin@skillspot.local', 'Alice', 'Admin', Role::Admin);
        $organizer = $this->user('organizer@skillspot.local', 'Sofia', 'Martin', Role::Organizer);
        $participant = $this->user('participant@skillspot.local', 'Thomas', 'Bernard');
        $secondParticipant = $this->user('lea@skillspot.local', 'Léa', 'Robert');
        $thirdParticipant = $this->user('karim@skillspot.local', 'Karim', 'Petit');
        $candidate = $this->user('candidate@skillspot.local', 'Camille', 'Durand');
        foreach ([$admin, $organizer, $participant, $secondParticipant, $thirdParticipant, $candidate] as $user) {
            $manager->persist($user);
        }

        $manager->persist(new OrganizerApplication(
            $candidate,
            'Je conçois des produits numériques depuis six ans et souhaite proposer des ateliers pratiques sur la recherche utilisateur et le prototypage rapide.',
        ));

        $workshops = [
            $this->workshop($organizer, 'Symfony sans magie noire', 'symfony-sans-magie-noire', 'Comprenez réellement le conteneur de services, le cycle HTTP et les événements Symfony. Nous construirons une fonctionnalité complète en privilégiant des objets explicites, des tests utiles et des décisions architecturales faciles à expliquer.', WorkshopCategory::Development, WorkshopLevel::Intermediate),
            $this->workshop($organizer, 'Concevoir un design system utile', 'concevoir-un-design-system-utile', 'Passez des composants isolés à un langage visuel partagé. Cet atelier couvre les tokens, les variantes, la documentation et les règles de contribution au travers d’exercices collaboratifs directement applicables en équipe.', WorkshopCategory::Design, WorkshopLevel::Beginner),
            $this->workshop($organizer, 'SQL pour analyser un produit', 'sql-pour-analyser-un-produit', 'Explorez un jeu de données produit réaliste avec PostgreSQL. Segmentation, rétention et cohortes seront abordées progressivement, avec un soin particulier porté à la lisibilité et à la performance des requêtes.', WorkshopCategory::Data, WorkshopLevel::Intermediate),
            $this->workshop($organizer, 'Préparer un entretien technique', 'preparer-un-entretien-technique', 'Transformez votre expérience en récits techniques structurés. Nous travaillerons la présentation d’un projet, les compromis d’architecture et une méthode concrète pour répondre aux exercices de conception sans réciter un cours.', WorkshopCategory::Career, WorkshopLevel::Beginner),
        ];
        foreach ($workshops as $workshop) {
            $manager->persist($workshop);
        }

        $firstSession = $this->session($workshops[0], '+5 days 18:30', '+5 days 20:30', 2, WorkshopMode::Onsite, 'La Cordée, 6 rue de la Part-Dieu, Lyon');
        $this->session($workshops[0], '+18 days 18:30', '+18 days 20:30', 14, WorkshopMode::Online, meetingUrl: 'https://meet.example.com/symfony');
        $this->session($workshops[1], '+8 days 09:30', '+8 days 12:30', 10, WorkshopMode::Onsite, 'Le Laptop, 7 rue Geoffroy-l’Angevin, Paris');
        $this->session($workshops[2], '+12 days 18:00', '+12 days 20:00', 16, WorkshopMode::Online, meetingUrl: 'https://meet.example.com/sql');
        $this->session($workshops[3], '+15 days 14:00', '+15 days 17:00', 12, WorkshopMode::Onsite, 'La Maison du Coworking, Lille');

        $past = $this->session($workshops[3], '-8 days 14:00', '-8 days 17:00', 12, WorkshopMode::Online, meetingUrl: 'https://meet.example.com/career');
        $past->complete(new \DateTimeImmutable());

        $bookingA = new Booking($participant, $firstSession, BookingStatus::Confirmed);
        $bookingB = new Booking($secondParticipant, $firstSession, BookingStatus::Confirmed);
        $bookingC = new Booking($thirdParticipant, $firstSession, BookingStatus::Waitlisted);
        $pastBookingA = new Booking($participant, $past, BookingStatus::Confirmed, new \DateTimeImmutable('-10 days'));
        $pastBookingA->markAttendance(AttendanceStatus::Attended, new \DateTimeImmutable('-8 days'));
        $pastBookingB = new Booking($secondParticipant, $past, BookingStatus::Confirmed, new \DateTimeImmutable('-10 days'));
        $pastBookingB->markAttendance(AttendanceStatus::NoShow, new \DateTimeImmutable('-8 days'));
        foreach ([$bookingA, $bookingB, $bookingC, $pastBookingA, $pastBookingB] as $booking) {
            $manager->persist($booking);
        }

        $manager->flush();
    }

    private function user(string $email, string $firstName, string $lastName, ?Role $role = null): User
    {
        $user = new User($email, $firstName, $lastName);
        $user->changePassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));
        $user->verify();
        if ($role) {
            $user->grantRole($role);
        }

        return $user;
    }

    private function workshop(User $owner, string $title, string $slug, string $description, WorkshopCategory $category, WorkshopLevel $level): Workshop
    {
        $workshop = new Workshop($owner, $title, $slug, $description, $category, $level);
        $workshop->setWorkflowState('published');

        return $workshop;
    }

    private function session(Workshop $workshop, string $startsAt, string $endsAt, int $capacity, WorkshopMode $mode, ?string $location = null, ?string $meetingUrl = null): WorkshopSession
    {
        $timezone = new \DateTimeZone('Europe/Paris');

        return new WorkshopSession(
            $workshop,
            new \DateTimeImmutable($startsAt, $timezone),
            new \DateTimeImmutable($endsAt, $timezone),
            $capacity,
            $mode,
            $location,
            $meetingUrl,
        );
    }
}
