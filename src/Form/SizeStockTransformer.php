<?php

namespace App\Form\DataTransformer;

use App\Entity\SizeStock;
use Symfony\Component\Form\DataTransformerInterface;

class SizeStockTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        return $value instanceof SizeStock ? $value->getSize() : '';
    }

    public function reverseTransform($value)
    {
        $sizeStock = new SizeStock();
        $sizeStock->setSize($value);
        return $sizeStock;
    }
}
