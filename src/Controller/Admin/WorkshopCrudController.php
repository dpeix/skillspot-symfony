<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Workshop\Domain\Entity\Workshop;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Workshop> */
final class WorkshopCrudController extends AbstractCrudController
{
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
        yield TextField::new('title', 'Titre');
        yield TextField::new('owner.displayName', 'Organisateur');
        yield TextField::new('category')->formatValue(static fn (mixed $value, Workshop $workshop): string => $workshop->getCategory()->label());
        yield TextField::new('level', 'Niveau')->formatValue(static fn (mixed $value, Workshop $workshop): string => $workshop->getLevel()->label());
        yield TextField::new('status', 'Statut')->formatValue(static fn (mixed $value, Workshop $workshop): string => $workshop->getStatus()->value);
    }
}
