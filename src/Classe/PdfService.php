<?php 

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService 
{
    private $domPdf;

    public function __construct()
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $pdfOptions->setIsHtml5ParserEnabled(true);

        $this->domPdf = new Dompdf($pdfOptions);
    }
    
    public function showPdfFile($html) 
    {
        $html = $this->prepareHtml($html);
        $this->domPdf->loadHtml($html, 'UTF-8');
        $this->domPdf->setPaper('A4', 'portrait');
        $this->domPdf->render();
        $this->domPdf->stream('facture.pdf', ['Attachment' => false]);
        exit;
    }

    public function generateBinaryPDF($html)
    {
        $html = $this->prepareHtml($html);
        $this->domPdf->loadHtml($html, 'UTF-8');
        $this->domPdf->setPaper('A4', 'portrait');
        $this->domPdf->render();
        return $this->domPdf->output();
    }

    private function prepareHtml($html): string
    {
        // Supprimer tout ce qui est avant <html ou <!DOCTYPE
        if (preg_match('/(<html[\s\S]*<\/html>)/i', $html, $matches)) {
            $clean = $matches[1];
        } elseif (preg_match('/(<!DOCTYPE[\s\S]*<\/html>)/i', $html, $matches)) {
            $clean = $matches[1];
        } else {
            $clean = $html;
        }

        // Supprimer les scripts JS
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $clean);
        // Supprimer les <link> CSS externes (dompdf ne les charge pas)
        $clean = preg_replace('/<link[^>]+(?:stylesheet|icon)[^>]*>/i', '', $clean);
        // Supprimer la navbar et le header/footer Symfony
        $clean = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is', '', $clean);
        $clean = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $clean);
        $clean = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $clean);

        return $clean;
    }
}