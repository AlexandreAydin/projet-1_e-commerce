<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Repository\OrderDetailsRepository;
use App\Repository\OrderRepository;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvoiceController extends AbstractController
{
    #[Route('factures/pdf/{order}/{orderDetails}', name: 'app_order_pdf')]
    public function generatePdfOrder(OrderDetails $orderDetails = null, Order $order = null, PdfService $pdf)
    {
        $currentUser = $this->getUser();
    
        if (!$order 
            || ($order->getUser() !== $currentUser 
                && !$this->isGranted('ROLE_ADMIN'))) {
            return $this->redirectToRoute('app_home');
        }
    
        $response = $this->render('pages/invoice/pdf/index.html.twig', [
            'orderDetails' => $orderDetails,
            'order' => $order,
            'show_header' => false,
        ]);
        
        $htmlContent = $response->getContent();
        $pdf->showPdfFile($htmlContent);
    
        exit;
    }
    
    
}
