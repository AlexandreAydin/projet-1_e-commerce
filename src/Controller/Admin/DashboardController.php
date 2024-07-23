<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use App\Entity\Carrier;
use App\Entity\Cart;
use App\Entity\Categorie;
use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\PaymentMethod;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\RewiewsProduct;
use App\Entity\User;
use App\Entity\Wishlist;
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
        yield MenuItem::linkToRoute('Aller sur le site', 'fa fa-home', 'app_home');
        yield MenuItem::subMenu('Produit', 'fas fa-shopping-cart')->setSubItems([
            MenuItem::linkToCrud('Produit', 'fas fa-shopping-cart', Product::class),
            MenuItem::linkToCrud('Image de Produit', 'fas fa-image', ProductImage::class),
            MenuItem::linkToCrud('Commentaire', 'fas fa-user', RewiewsProduct::class),
            MenuItem::linkToCrud('Catégorie', 'fas fa-list', Categorie::class),
            MenuItem::linkToCrud('Livraison', 'fas fa-truck', Carrier::class),
            
        ]);
        yield MenuItem::subMenu('Utilisateur', 'fas fa-user')->setSubItems([
            MenuItem::linkToCrud('Utilisateur', 'fas fa-user', User::class),
            MenuItem::linkToCrud('Addresse de l\'utilisateur', 'fas fa-map', Address::class),
            MenuItem::linkToCrud('Contact', 'fas fa-user', Contact::class),
        ]);
        yield MenuItem::subMenu('Commande, Factures et panier', 'fas fa-shopping-bag')->setSubItems([
            MenuItem::linkToCrud('Payment methods', 'fas fa-landmark', PaymentMethod::class),
            MenuItem::linkToCrud('Commandes et Factures', 'fas fa-shopping-bag', Order::class),
            MenuItem::linkToCrud('Commande Détaillé', 'fas fa-shopping-bag', OrderDetails::class),
            MenuItem::linkToCrud('Panier', 'fas fa-boxes', Cart::class),
        ]);
    }
}