<?php 

namespace App\Service;

use Dompdf\Dompdf; // Correct namespace for Dompdf class
use Dompdf\Options; // Correct namespace for Options class

class PdfService 
{
    private $domPdf;

    public function __construct()
    {
        $this->domPdf = new Dompdf();

        $pdfOptions = new Options();
        $pdfOptions->set("defaultFont", "Garamond");
        
        $this->domPdf->setOptions($pdfOptions);
    }
    
    public function showPdfFile($html) 
    {
        $this->domPdf->loadHtml($html);
        $this->domPdf->render();
        $this->domPdf->stream("details.pdf", [
            "Attachment" => false,
        ]);
    }

    public function generateBinaryPDF($html)
    {
        $this->domPdf->loadHtml($html);
        $this->domPdf->render();
        return $this->domPdf->output();
    }   
}
