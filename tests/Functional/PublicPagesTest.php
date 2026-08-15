<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicPagesTest extends WebTestCase
{
    public function testPublicPagesAndApiAreAvailable(): void
    {
        $client = static::createClient();
        foreach (['/fr', '/en', '/fr/ateliers', '/en/workshops', '/fr/ateliers/symfony-sans-magie-noire', '/en/workshops/symfony-without-black-magic', '/healthz', '/api/workshops'] as $path) {
            $client->request('GET', $path, server: ['HTTP_ACCEPT' => str_starts_with($path, '/api') ? 'application/json' : 'text/html']);
            self::assertResponseIsSuccessful($path);
        }
    }

    public function testLocalizedPagesExposeLanguageAndSeoAlternates(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fr');
        self::assertSelectorCount(4, '.workshop-card');

        $client->request('GET', '/en/workshops/symfony-without-black-magic');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorTextContains('h1', 'Symfony without black magic');
        self::assertSelectorExists('[data-availability-available-label-value="seats available"]');
        self::assertSelectorExists('link[rel="canonical"][href$="/en/workshops/symfony-without-black-magic"]');
        self::assertSelectorExists('link[hreflang="fr"][href$="/fr/ateliers/symfony-sans-magie-noire"]');
        self::assertSelectorExists('link[hreflang="x-default"]');
    }

    public function testLegacyAndWrongLocaleSlugsRedirectPermanently(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ateliers');
        self::assertResponseRedirects('/fr/ateliers', 301);

        $client->request('GET', '/en/workshops/symfony-sans-magie-noire');
        self::assertResponseRedirects('/en/workshops/symfony-without-black-magic', 301);
    }

    public function testWorkshopApiIsReadOnly(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/workshops', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');
        self::assertResponseStatusCodeSame(405);
    }

    public function testApiDetailKeepsFrenchIdentifierAndIncludesSessionTranslations(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/workshops/symfony-without-black-magic', server: ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/workshops/symfony-sans-magie-noire', server: ['HTTP_ACCEPT' => 'application/json']);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        $sessionIri = $payload['sessions'][0] ?? '';
        self::assertIsString($sessionIri);
        $client->request('GET', $sessionIri, server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('"workshopTranslations"', (string) $client->getResponse()->getContent());
    }

    public function testApiFiltersAreAvailableAndPrivateMeetingLinksStayHidden(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/workshops?mode=online&itemsPerPage=2', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $content = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('meet.example.com', $content);
        self::assertStringContainsString('"translations"', $content);
        self::assertStringContainsString('"fr"', $content);
        self::assertStringContainsString('"en"', $content);
    }
}
