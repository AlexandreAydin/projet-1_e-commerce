<?php 
namespace App\Services;

use App\Repository\PaymentMethodRepository;
use Symfony\Component\HttpFoundation\RequestStack;


class StripeService {



    private $requestStack;
    private $paymentMethodRepository;

    public function __construct(RequestStack $requestStack, PaymentMethodRepository $paymentMethodRepository)
    {
        $this->requestStack = $requestStack;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    private function getSession() 
    {
        return $this->requestStack->getSession();
    }

    public function getPublicKey(){
        $config = $this->paymentMethodRepository->findOneByName("Stripe");

        if($_ENV['APP_ENV'] === 'dev'){
            //development
            return $config->getTestPublicApiKey();
        }else{
            //production
            return $config->getProdPublicApiKey();
        }
    }

    public function getPrivateKey(){
        $config = $this->paymentMethodRepository->findOneByName("Stripe");

        if($_ENV['APP_ENV'] === 'dev'){
            //development
            return $config->getTestPrivateApiKey();
        }else{
            //production
            return $config->getProdPrivateApiKey();
        }
    }


}