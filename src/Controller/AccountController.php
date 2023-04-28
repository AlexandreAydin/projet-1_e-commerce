<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/compte', name: 'app_account')]
class AccountController extends AbstractController
{
    #[Route('', name: 'app_account')]
    public function index(): Response
    {
        return $this->render('pages/account/index.html.twig', [
            'controller_name' => 'AccountController',
        ]);
    }
}
