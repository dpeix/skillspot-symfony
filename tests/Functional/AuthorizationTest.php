<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\Entity\User;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthorizationTest extends WebTestCase
{
    public function testAnonymousUsersAreRedirectedFromDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dashboard');

        self::assertResponseRedirects('http://localhost/connexion');
    }

    public function testEachDemoRoleCanAccessItsWorkspace(): void
    {
        $client = static::createClient();
        $users = self::getContainer()->get(UserRepository::class);

        $participant = $users->findByEmail('participant@skillspot.local');
        self::assertInstanceOf(User::class, $participant);
        $client->loginUser($participant);
        $client->request('GET', '/dashboard');
        self::assertResponseIsSuccessful();
        $client->request('GET', '/organizer');
        self::assertResponseStatusCodeSame(403);

        $organizer = $users->findByEmail('organizer@skillspot.local');
        self::assertInstanceOf(User::class, $organizer);
        $client->loginUser($organizer);
        $client->request('GET', '/organizer');
        self::assertResponseIsSuccessful();

        $admin = $users->findByEmail('admin@skillspot.local');
        self::assertInstanceOf(User::class, $admin);
        $client->loginUser($admin);
        $client->request('GET', '/admin');
        self::assertResponseRedirects('/admin/organizer-applications');
    }
}
