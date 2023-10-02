<?php 

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf; // Correct namespace for Dompdf class
use Dompdf\Options; // Correct namespace for Options class
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

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
        $bodyContent = $this->extractBodyContent($html);
        
        $this->domPdf->loadHtml($bodyContent);
        $this->domPdf->render();
        $this->domPdf->stream("details.pdf", [
            "Attachment" => false,
        ]);
    }

    public function generateBinaryPDF($html)
    {
        $bodyContent = $this->extractBodyContent($html);

        $this->domPdf->loadHtml($bodyContent);
        $this->domPdf->render();
        return $this->domPdf->output();
    }

    private function extractBodyContent($html)
    {
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $html, $matches);
        return $matches[1] ?? '';
    }


    
}
