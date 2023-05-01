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

        // Set the imageFile property, e.g. via a file upload or other means
        // You would typically process the uploaded file from the request here
        // For demonstration purposes, let's assume you have the uploaded file in a variable called $uploadedFile
        $uploadedFile = $request->files->get('imageFile');
        $productImage->setImageFile($uploadedFile);

        // Access the imageFile property using the getImageFile() method
        $imageFile = $productImage->getImageFile();

        // Do something with the imageFile, e.g., save it to the database, store it on the server, etc.

        // Render a view, or return a response
        return $this->render('product/create.html.twig', [
            'image_file' => $imageFile,
        ]);
    }
}

