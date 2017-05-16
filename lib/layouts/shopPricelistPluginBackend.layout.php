<?php

class shopPricelistPluginBackendLayout extends shopBackendLayout {

    public function execute() {
        parent::execute();

        wa()->getResponse()->addCss('css/semantic.min.css', 'shop/plugins/pricelist');
        wa()->getResponse()->addCss('css/pricelist.css', 'shop/plugins/pricelist');
        wa()->getResponse()->addJs('js/semantic.min.js', 'shop/plugins/pricelist');
        wa()->getResponse()->addJs('js/pricelist.js', 'shop/plugins/pricelist');
    }
}