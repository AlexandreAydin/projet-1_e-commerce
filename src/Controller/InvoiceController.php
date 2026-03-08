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

        try {
            $response = $this->render('pages/invoice/pdf/index.html.twig', [
                'orderDetails' => $orderDetails,
                'order'        => $order,
                'show_header'  => false,
            ]);

            $htmlContent = $response->getContent();

            if (empty(trim($htmlContent))) {
                throw new \Exception('Le HTML rendu est vide — vérifiez le template Twig.');
            }

            $pdf->showPdfFile($htmlContent);
            exit;

        } catch (\Exception $e) {
            // Affiche l'erreur clairement au lieu d'une page blanche
            return new Response(
                '<h2 style="color:red;font-family:monospace;padding:20px;">Erreur PDF</h2>'
                . '<pre style="padding:20px;background:#fff3f3;border:1px solid red;">'
                . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString())
                . '</pre>',
                500
            );
        }
    }
}