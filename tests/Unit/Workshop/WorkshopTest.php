<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workshop;

use App\Shared\Domain\Enum\SupportedLocale;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Tests\Support\DomainFactory;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use PHPUnit\Framework\TestCase;

final class WorkshopTest extends TestCase
{
    use DomainFactory;

    public function testPublicationRequiresAFutureBookableSession(): void
    {
        $workshop = $this->workshop();
        self::assertFalse($workshop->hasFutureSession(new \DateTimeImmutable()));

        $this->session($workshop);
        self::assertTrue($workshop->hasFutureSession(new \DateTimeImmutable()));
    }

    public function testBothTranslationsAreRequired(): void
    {
        $workshop = new Workshop($this->user(), WorkshopCategory::Development, WorkshopLevel::Beginner);
        $workshop->addTranslation(
            SupportedLocale::French,
            'Atelier en français',
            'atelier-en-francais',
            'Une description française suffisamment détaillée pour exposer clairement tous les objectifs pratiques de cet atelier.',
        );

        self::assertFalse($workshop->hasAllTranslations());
        $this->expectException(BusinessRuleViolation::class);
        $workshop->translation(SupportedLocale::English);
    }

    public function testUpdatingContentKeepsLocalizedSlugsStable(): void
    {
        $workshop = $this->workshop();
        $frenchSlug = $workshop->translation(SupportedLocale::French)->getSlug();
        $englishSlug = $workshop->translation(SupportedLocale::English)->getSlug();

        $workshop->updateTranslation(SupportedLocale::French, 'Un titre entièrement modifié', 'Une description française entièrement revue mais toujours suffisamment longue pour respecter toutes les règles métier de contenu.');
        $workshop->updateTranslation(SupportedLocale::English, 'A completely changed title', 'A completely rewritten English description that remains sufficiently long to satisfy every workshop content business rule.');

        self::assertSame($frenchSlug, $workshop->translation(SupportedLocale::French)->getSlug());
        self::assertSame($englishSlug, $workshop->translation(SupportedLocale::English)->getSlug());
    }
}
