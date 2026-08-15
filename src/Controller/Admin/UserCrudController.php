<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Identity\Domain\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<User> */
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('admin.user.singular')->setEntityLabelInPlural('admin.user.plural');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('email', 'admin.user.email'),
            TextField::new('firstName', 'admin.user.first_name'),
            TextField::new('lastName', 'admin.user.last_name'),
            TextField::new('preferredLocale.value', 'admin.user.locale'),
            BooleanField::new('verified', 'admin.user.verified'),
            ArrayField::new('roles', 'admin.user.roles'),
        ];
    }
}
