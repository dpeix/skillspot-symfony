<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\Entity\User;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use App\Shared\Domain\Enum\SupportedLocale;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

final class LocalizationTest extends WebTestCase
{
    public function testLandingUsesTheBrowserLanguageAndFrenchFallback(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'en-GB,en;q=0.9']);
        self::assertResponseRedirects('/en');

        $client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9']);
        self::assertResponseRedirects('/fr');
    }

    public function testAccountPreferenceWinsOverCookie(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findByEmail('participant@skillspot.local');
        self::assertInstanceOf(User::class, $user);
        $client->getCookieJar()->set(new Cookie('skillspot_locale', 'en'));
        $client->loginUser($user);

        $client->request('GET', '/');

        self::assertResponseRedirects('/fr');
    }

    public function testAnonymousLanguageSwitchPersistsCookieAndFilters(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/ateliers?category=development&mode=online');
        $form = $crawler->selectButton('EN')->form();
        self::assertSame('/en/workshops?category=development&mode=online', $form->get('target')->getValue());

        $client->submit($form);

        self::assertResponseRedirects('/en/workshops?category=development&mode=online');
        self::assertSame('en', $client->getCookieJar()->get('skillspot_locale')?->getValue());
    }

    public function testLanguageSwitchConvertsWorkshopSlug(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/ateliers/symfony-sans-magie-noire');

        self::assertSame(
            '/en/workshops/symfony-without-black-magic',
            $crawler->selectButton('EN')->form()->get('target')->getValue(),
        );
    }

    public function testAuthenticatedLanguageSwitchUpdatesPreference(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findByEmail('participant@skillspot.local');
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/fr');

        $client->submit($crawler->selectButton('EN')->form());

        self::assertResponseRedirects('/en');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $updated = self::getContainer()->get(UserRepository::class)->findByEmail('participant@skillspot.local');
        self::assertInstanceOf(User::class, $updated);
        self::assertSame(SupportedLocale::English, $updated->getPreferredLocale());
        $updated->changePreferredLocale(SupportedLocale::French);
        self::getContainer()->get(UserRepository::class)->save($updated);
    }

    public function testEnglishFormValidationIsTranslated(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/register');
        $client->submit($crawler->selectButton('Create my account')->form([
            'registration_form[firstName]' => 'Nina',
            'registration_form[lastName]' => 'Tester',
            'registration_form[email]' => 'validation@example.test',
            'registration_form[plainPassword][first]' => 'SecureTest2026!',
            'registration_form[plainPassword][second]' => 'SecureTest2026!',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-row ul', 'You must accept the terms of use.');
    }
}
