<?php

namespace App\Controller\Account;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


// #[Route('/compte', name: 'app_account')]
class AccountController extends AbstractController
{
    #[Route('/compte', name: 'app_account')]
    public function index(OrderRepository $repoOrder): Response
    {
        $orders = $repoOrder->findBy(['isPaid'=>true, 'user'=>$this->getUser()],
        ['id'=>'DESC']);

        return $this->render('pages/account/index.html.twig',[
            'orders'=> $orders,
        ] );
   
    }

    #[Route('/compte/{id}', name: 'app_account_show')]
    public function show(Order $order): Response
    {
        if(!$order || $order->getUser() !== $this->getUser()){
            return $this->redirectToRoute('home');
        }

        return $this->render('pages/account/detail_order.html.twig',[
            'order'=> $order,
        ] );
   
    }
}
