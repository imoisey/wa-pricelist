<?php

class shopPricelistPluginBackendAction extends waViewAction {

    public function execute() {
        $this->setLayout(new shopPricelistPluginBackendLayout());

        $shopCategoryModel = new shopCategoryModel();
        $categories = $shopCategoryModel->getFullTree();

        // Получаем список шаблонов
        $pricelist_model = new shopPricelistPluginModel();
        $templates = $pricelist_model->getAll();

        // Сообщения об ошибках
        if( $errors = $this->getStorage()->read('errors') ) {
            $this->view->assign('error_message', $errors);
        }

        $this->view->assign('templates', $templates);
        $this->view->assign('storefronts', wa()->getRouting()->getByApp('shop'));
        $this->view->assign('categories', $categories);
    }
}