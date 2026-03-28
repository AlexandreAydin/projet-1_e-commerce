<?php
// src/Controller/ProductController.php

namespace App\Controller;

use App\Entity\ProductImage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * @Route("/create-product", name="create_product", methods={"GET", "POST"})
     */
    public function createProduct(Request $request): Response
    {
        // Create a new instance of ProductImage
        $productImage = new ProductImage();

        $uploadedFile = $request->files->get('imageFile');
        $productImage->setImageFile($uploadedFile);

        // Access the imageFile property using the getImageFile() method
        $imageFile = $productImage->getImageFile();


        // Render a view, or return a response
        return $this->render('product/create.html.twig', [
            'image_file' => $imageFile,
        ]);
    }

}

