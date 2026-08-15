<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Localization\LocaleResolver;
use App\Shared\Domain\Enum\SupportedLocale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    #[Route('/', name: 'locale_landing', methods: ['GET'], priority: 1000)]
    public function landing(Request $request, LocaleResolver $locales): RedirectResponse
    {
        $user = $this->getUser();
        $locale = $locales->resolve($request, $user instanceof User ? $user : null);

        return $this->redirectToRoute('home', ['_locale' => $locale->value]);
    }

    #[Route('/locale/{locale}', name: 'locale_switch', requirements: ['locale' => 'fr|en'], methods: ['POST'])]
    public function switch(
        string $locale,
        Request $request,
        UserRepositoryInterface $users,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('switch_locale', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $supportedLocale = SupportedLocale::from($locale);
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->changePreferredLocale($supportedLocale);
            $users->save($user);
        }

        $target = $request->request->getString('target');
        if (!preg_match('#^/(?:fr|en)(?:/|$)#', $target) || str_starts_with($target, '//')) {
            $target = $this->generateUrl('home', ['_locale' => $supportedLocale->value]);
        }

        $response = $this->redirect($target);
        $response->headers->setCookie(Cookie::create(
            'skillspot_locale',
            $supportedLocale->value,
            new \DateTimeImmutable('+1 year'),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));

        return $response;
    }

    #[Route(
        '/{path}',
        name: 'legacy_redirect',
        requirements: ['path' => '(?!api(?:/|$)|healthz$|fr(?:/|$)|en(?:/|$)|locale(?:/|$)|\\.well-known(?:/|$)|_(?:profiler|wdt|error)(?:/|$)).+'],
        methods: ['GET'],
        priority: -1000,
    )]
    public function legacy(string $path, Request $request): RedirectResponse
    {
        $target = match (true) {
            'dashboard' === $path => $this->generateUrl('attendee_dashboard', ['_locale' => 'fr']),
            'organizer/apply' === $path => $this->generateUrl('organizer_apply', ['_locale' => 'fr']),
            'organizer' === $path => $this->generateUrl('organizer_dashboard', ['_locale' => 'fr']),
            'organizer/workshops/new' === $path => $this->generateUrl('organizer_workshop_new', ['_locale' => 'fr']),
            1 === preg_match('#^organizer/workshops/(\d+)/edit$#', $path, $matches) => $this->generateUrl('organizer_workshop_edit', ['_locale' => 'fr', 'id' => $matches[1]]),
            1 === preg_match('#^organizer/workshops/(\d+)/sessions/new$#', $path, $matches) => $this->generateUrl('organizer_session_new', ['_locale' => 'fr', 'id' => $matches[1]]),
            1 === preg_match('#^organizer/sessions/(\d+)/participants$#', $path, $matches) => $this->generateUrl('organizer_session_participants', ['_locale' => 'fr', 'id' => $matches[1]]),
            'admin' === $path => $this->generateUrl('admin', ['_locale' => 'fr']),
            'admin/organizer-applications' === $path => $this->generateUrl('admin_organizer_applications', ['_locale' => 'fr']),
            default => '/fr/'.$path,
        };
        if ($request->getQueryString()) {
            $target .= '?'.$request->getQueryString();
        }

        return $this->redirect($target, RedirectResponse::HTTP_MOVED_PERMANENTLY);
    }
}
