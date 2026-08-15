<?php

declare(strict_types=1);

namespace App\Shared\Application\Localization;

use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class LocalizedUrlGenerator
{
    public function __construct(
        private RequestStack $requests,
        private RouterInterface $router,
        private WorkshopRepositoryInterface $workshops,
    ) {
    }

    public function forCurrentRequest(
        SupportedLocale|string $locale,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $locale = $locale instanceof SupportedLocale ? $locale : SupportedLocale::fromString($locale);
        $request = $this->requests->getCurrentRequest();
        if (!$request) {
            return $this->router->generate('home', ['_locale' => $locale->value], $referenceType);
        }

        $route = $request->attributes->getString('_canonical_route') ?: $request->attributes->getString('_route');
        $route = preg_replace('/\.(?:fr|en)$/', '', $route) ?: $route;
        if ('' === $route || \in_array($route, ['locale_landing', 'legacy_redirect', 'locale_switch'], true)) {
            $route = 'home';
        }

        $parameters = $request->attributes->get('_route_params', []);
        $parameters = \is_array($parameters) ? $parameters : [];
        unset($parameters['_locale']);

        if ('workshop_show' === $route && isset($parameters['slug']) && \is_string($parameters['slug'])) {
            $workshop = $this->workshops->findPublishedByAnySlug($parameters['slug']);
            if ($workshop) {
                $parameters['slug'] = $workshop->translation($locale)->getSlug();
            }
        }

        $parameters = [...$request->query->all(), ...$parameters, '_locale' => $locale->value];

        try {
            return $this->router->generate($route, $parameters, $referenceType);
        } catch (\Throwable) {
            return $this->router->generate('home', ['_locale' => $locale->value], $referenceType);
        }
    }
}
