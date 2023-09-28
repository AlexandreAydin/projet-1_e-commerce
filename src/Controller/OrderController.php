<?php
// src/Controller/OrderController.php

namespace App\Controller;

use App\Entity\Order;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @Route("/order/{id}", name="order_show")
     * @ParamConverter("order", class="App\Entity\Order")
     */
    public function show(Order $order): Response
    {
        // Faites quelque chose avec $order, par exemple afficher ses détails

        return $this->render('order/show.html.twig', ['order' => $order]);
    }
}