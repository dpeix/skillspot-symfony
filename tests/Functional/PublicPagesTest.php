<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicPagesTest extends WebTestCase
{
    public function testPublicPagesAndApiAreAvailable(): void
    {
        $client = static::createClient();
        foreach (['/', '/ateliers', '/ateliers/symfony-sans-magie-noire', '/healthz', '/api/workshops'] as $path) {
            $client->request('GET', $path, server: ['HTTP_ACCEPT' => str_starts_with($path, '/api') ? 'application/json' : 'text/html']);
            self::assertResponseIsSuccessful($path);
        }
    }

    public function testWorkshopApiIsReadOnly(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/workshops', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');
        self::assertResponseStatusCodeSame(405);
    }

    public function testApiFiltersAreAvailableAndPrivateMeetingLinksStayHidden(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/workshops?mode=online&itemsPerPage=2', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        self::assertStringNotContainsString('meet.example.com', (string) $client->getResponse()->getContent());
    }
}
