<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/{_locale}/admin', routeName: 'admin', routeOptions: ['requirements' => ['_locale' => 'fr|en']])]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_organizer_applications');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SkillSpot')
            ->setTranslationDomain('messages');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('admin.menu.dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('admin.menu.applications', 'fa fa-user-check', 'admin_organizer_applications');
        yield MenuItem::linkTo(UserCrudController::class, 'admin.menu.users', 'fa fa-users');
        yield MenuItem::linkTo(WorkshopCrudController::class, 'admin.menu.workshops', 'fa fa-graduation-cap');
        yield MenuItem::linkToRoute('admin.menu.website', 'fa fa-arrow-left', 'home');
    }
}
