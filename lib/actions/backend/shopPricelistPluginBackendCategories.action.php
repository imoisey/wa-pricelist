<?php

class shopPricelistPluginBackendCategoriesAction extends waViewAction {

    public function execute() {
        $template_id = waRequest::get('template_id', false);
        if(!$template_id)
            throw new waException('Неверный ID шаблона', 500);
        $pricelist_model = new shopPricelistPluginModel();
        // Получаем данные по категориям
        $template_data = $pricelist_model->getById($template_id);
        if(count($template_data) == 0)
            throw new waException('ID шаблона не найден в БД', 500);

        if(empty($template_data['categories']))
            throw new waException('Категории не могут быть пустыми', 500);

        $categories = json_decode($template_data['categories']);
        $categories = array_combine(array_values($categories),array_values($categories));

        // Получаем все категории
        $shopCategoryModel = new shopCategoryModel();
        $categories_full = $shopCategoryModel->getFullTree();

        $this->view->assign('template_id', $template_id);
        $this->view->assign('categories', $categories);
        $this->view->assign('categories_full', $categories_full);
    }

}