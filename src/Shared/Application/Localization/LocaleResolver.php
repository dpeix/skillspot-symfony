<?php

declare(strict_types=1);

namespace App\Shared\Application\Localization;

use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Enum\SupportedLocale;
use Symfony\Component\HttpFoundation\Request;

final readonly class LocaleResolver
{
    public function resolve(Request $request, ?User $user = null): SupportedLocale
    {
        $routeLocale = $request->attributes->getString('_locale');
        if (null !== SupportedLocale::tryFrom($routeLocale)) {
            return SupportedLocale::from($routeLocale);
        }

        if ($user) {
            return $user->getPreferredLocale();
        }

        $cookieLocale = $request->cookies->getString('skillspot_locale');
        if (null !== SupportedLocale::tryFrom($cookieLocale)) {
            return SupportedLocale::from($cookieLocale);
        }

        $preferred = $request->getPreferredLanguage(array_map(
            static fn (SupportedLocale $locale): string => $locale->value,
            SupportedLocale::cases(),
        ));

        return SupportedLocale::fromString($preferred ?? SupportedLocale::default()->value);
    }
}
