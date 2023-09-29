<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use App\Entity\Carrier;
use App\Entity\Cart;
use App\Entity\Categorie;
use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\RewiewsProduct;
use App\Entity\User;
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
        yield MenuItem::linkToCrud('Utilisateur', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Addresse', 'fas fa-user', Address::class);
        yield MenuItem::linkToCrud('Commande', 'fas fa-shopping-bag', Order::class);
        yield MenuItem::linkToCrud('Commande Détaillé', 'fas fa-shopping-bag', OrderDetails::class);
        yield MenuItem::linkToCrud('Panier', 'fas fa-boxes', Cart::class);
        yield MenuItem::linkToCrud('Image de Produit', 'fas fa-image', ProductImage::class);
        yield MenuItem::linkToCrud('Catégorie', 'fas fa-list', Categorie::class);
        yield MenuItem::linkToCrud('Livraison', 'fas fa-truck', Carrier::class);
        yield MenuItem::linkToCrud('Contact', 'fas fa-user', Contact::class);
        yield MenuItem::linkToCrud('Commentaire', 'fas fa-user', RewiewsProduct::class);
    }
}