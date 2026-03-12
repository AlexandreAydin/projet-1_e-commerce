<?php

namespace App\Controller\Admin;

use App\Entity\GoogleOAuthSetting;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;

class GoogleOAuthSettingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GoogleOAuthSetting::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Configuration Google OAuth')
            ->setEntityLabelInPlural('Configuration Google OAuth')
            ->setPageTitle('index', 'Connexion Google — Configuration')
            ->setPageTitle('edit', 'Modifier la configuration Google')
            ->setPageTitle('new', 'Ajouter une configuration Google')
            ->setHelp('edit', 'Les clés OAuth Google se trouvent sur console.cloud.google.com → Identifiants.')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            TextField::new('name', 'Nom')
                ->setHelp('Ex: Google OAuth'),

            TextareaField::new('description', 'Description')
                ->hideOnIndex()
                ->setRequired(false),

            ImageField::new('imageUrl', 'Logo Google')
                ->setBasePath('assets/images/oauth_logos')
                ->setUploadDir('/public/assets/images/oauth_logos')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),

            TextField::new('clientId', 'Client ID (Google)')
                ->setHelp('Trouvé sur console.cloud.google.com → Identifiants OAuth')
                ->hideOnIndex(),

            TextField::new('clientSecret', 'Client Secret')
                ->setHelp('Secret OAuth Google — ne pas partager')
                ->hideOnIndex(),

            TextField::new('redirectUri', 'URI de redirection')
                ->setHelp('Ex: https://votre-site.com/connect/google/check')
                ->hideOnIndex(),

            BooleanField::new('isEnabled', 'Activer la connexion Google')
                ->renderAsSwitch(true),

            DateTimeField::new('updatedAt', 'Dernière mise à jour')
                ->hideOnForm()
                ->setRequired(false),
        ];
    }
}