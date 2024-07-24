<?php 
namespace App\Services;

use App\Repository\PaymentMethodRepository;
use Symfony\Component\HttpFoundation\RequestStack;


class PaypalService {


    private $requestStack;
    private $paymentMethodRepository;

    public function __construct(RequestStack $requestStack, PaymentMethodRepository $paymentMethodRepository)
    {
        $this->requestStack = $requestStack;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function getPublicKey(){
        $config = $this->paymentMethodRepository->findOneByName("Paypal");

        if($_ENV['APP_ENV'] === 'dev'){
            //development
            return $config->getTestPublicApiKey();
        }else{
            //production
            return $config->getProdPublicApiKey();
        }
    }

    public function getPrivateKey(){
        $config = $this->paymentMethodRepository->findOneByName("Paypal");

        if($_ENV['APP_ENV'] === 'dev'){
            //development
            return $config->getTestPrivateApiKey();
        }else{
            //production
            return $config->getProdPrivateApiKey();
        }
    }
    public function getBaseUrl(){
        $config = $this->paymentMethodRepository->findOneByName("Paypal");

        if($_ENV['APP_ENV'] === 'dev'){
            //development
            return $config->getTestBaseUrl();
        }else{
            //production
            return $config->getProdBaseUrl();
        }
    }


}