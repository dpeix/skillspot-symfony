<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Workshop\Domain\Entity\Workshop;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @extends AbstractCrudController<Workshop> */
final class WorkshopCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RequestStack $requests,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Workshop::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Action::INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('title', 'workshop.form.title')->formatValue(fn (mixed $value, Workshop $workshop): string => $workshop->translation($this->requests->getCurrentRequest()?->getLocale() ?? 'fr')->getTitle());
        yield TextField::new('owner.displayName', 'admin.workshop.organizer');
        yield TextField::new('category', 'workshop.form.category')->formatValue(fn (mixed $value, Workshop $workshop): string => $this->translator->trans($workshop->getCategory()->labelKey()));
        yield TextField::new('level', 'workshop.form.level')->formatValue(fn (mixed $value, Workshop $workshop): string => $this->translator->trans($workshop->getLevel()->labelKey()));
        yield TextField::new('status', 'admin.workshop.status')->formatValue(fn (mixed $value, Workshop $workshop): string => $this->translator->trans($workshop->getStatus()->labelKey()));
    }
}
