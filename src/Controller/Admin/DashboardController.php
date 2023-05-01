<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Categories;
use App\Entity\Contact;
use App\Entity\User;
use App\Entity\Ingredient;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\Recipe;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class DashboardController extends AbstractDashboardController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Site Recette- Administration')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::linkToCrud('Produit', 'fas fa-shopping-cart', Product::class);
        yield MenuItem::linkToCrud('Image de Produit', 'fas fa-image', ProductImage::class);
        yield MenuItem::linkToCrud('Catégorie', 'fas fa-list', Categorie::class);

    }
}