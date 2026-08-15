<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig;

use App\Shared\Application\Localization\LocalizedUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LocaleExtension extends AbstractExtension
{
    public function __construct(private readonly LocalizedUrlGenerator $urls)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('localized_url', $this->localizedUrl(...)),
            new TwigFunction('localized_absolute_url', $this->localizedAbsoluteUrl(...)),
        ];
    }

    public function localizedUrl(string $locale): string
    {
        return $this->urls->forCurrentRequest($locale);
    }

    public function localizedAbsoluteUrl(string $locale): string
    {
        return $this->urls->forCurrentRequest($locale, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
