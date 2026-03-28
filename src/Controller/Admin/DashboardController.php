<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use App\Entity\BrandModel;
use App\Entity\Carrier;
use App\Entity\Cart;
use App\Entity\Categorie;
use App\Entity\Contact;
use App\Entity\Coupon;
use App\Entity\GoogleOAuthSetting;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\PaymentMethod;
use App\Entity\Product;
use App\Entity\ProductBrand;
use App\Entity\ProductImage;
use App\Entity\RewiewsProduct;
use App\Entity\SubCategorie;
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
        yield MenuItem::linkToRoute('🏠 Aller sur le site', '', 'app_home');

        yield MenuItem::subMenu('🛍️ Catalogue', '')->setSubItems([
            MenuItem::linkToCrud('Produits', 'fas fa-box-open', Product::class),
            MenuItem::linkToCrud('Images produit', 'fas fa-images', ProductImage::class),
            MenuItem::linkToCrud('Catégories', 'fas fa-layer-group', Categorie::class),
            MenuItem::linkToCrud('Sous-catégories', 'fas fa-sitemap', SubCategorie::class),
            MenuItem::linkToCrud('Marques', 'fas fa-copyright', ProductBrand::class),
            MenuItem::linkToCrud('Modèles de marque', 'fas fa-tags', BrandModel::class),
        ]);

        yield MenuItem::subMenu('💰 Ventes & Promotions', '')->setSubItems([
            MenuItem::linkToCrud('Commandes & Factures', 'fas fa-file-invoice-dollar', Order::class),
            MenuItem::linkToCrud('Détails commandes', 'fas fa-list-alt', OrderDetails::class),
            MenuItem::linkToCrud('Paniers', 'fas fa-shopping-cart', Cart::class),
            MenuItem::linkToCrud('Coupons', 'fas fa-percent', Coupon::class),
            MenuItem::linkToCrud('Livraison', 'fas fa-truck', Carrier::class),
        ]);

        yield MenuItem::subMenu('👥 Utilisateurs', '')->setSubItems([
            MenuItem::linkToCrud('Utilisateurs', 'fas fa-user-circle', User::class),
            MenuItem::linkToCrud('Adresses', 'fas fa-map-marker-alt', Address::class),
            MenuItem::linkToCrud('Messages & Contact', 'fas fa-envelope-open-text', Contact::class),
            MenuItem::linkToCrud('Commentaires', 'fas fa-comments', RewiewsProduct::class),
        ]);

        yield MenuItem::subMenu('⚙️ Paramètres du site', '')->setSubItems([
            MenuItem::linkToCrud('Moyens de paiement', 'fas fa-credit-card', PaymentMethod::class),
            MenuItem::linkToCrud('Connexion Google', 'fab fa-google', GoogleOAuthSetting::class),
        ]);

        yield MenuItem::linkToUrl('📊 Analytics', '', '/admin/stats');
    }
}