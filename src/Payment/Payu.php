<?php

namespace Vfixtechnology\Payu\Payment;

use Webkul\Payment\Payment\Payment;
use Illuminate\Support\Facades\Storage;

class Payu extends Payment
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'payu';

    public function getRedirectUrl()
    {
        return route('payu.redirect');

    }

    public function isAvailable()
    {
        if (!$this->cart) {
            $this->setCart();
        }

        return $this->getConfigData('active') && $this->cart?->haveStockableItems();
    }

    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : '';

    }
    
}