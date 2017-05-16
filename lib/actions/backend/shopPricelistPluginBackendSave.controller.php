<?php

class shopPricelistPluginBackendSaveController extends waController {

    public function execute() {
        // Получаем данные
        $template = array();
        $template['name']       = waRequest::post('name', 'Без названия');
        $template['storefront'] = waRequest::post('storefront');
        $template['discount']   = waRequest::post('discount');
        $template['stock']      = waRequest::post('stock') == 'on' ? 1 : 0;
        $template['categories'] = waRequest::post('categories') ? json_encode(waRequest::post('categories')) : null;

        if(!$template['discount']) {
            $this->getStorage()->write('errors', array(
                'header' => _wp('Ошибка заполнения данных'),
                'content' => _wp('Не указано поле "Скидка"'),
            ));
            $this->redirect('?plugin=pricelist');
        }

        // Добавляем данные в БД
        $pricelist_model = new shopPricelistPluginModel();
        $lastInsertId = $pricelist_model->insert($template, 1);

        // Если запись добавлена
        if(!$lastInsertId) {
            $this->getStorage()->write('errors', array(
                'header' => _wp('Ошибка добавления шаблона'),
                'content' => _wp('Шаблон не был добавлен'),
            ));
        }

        $this->redirect('?plugin=pricelist');
    }

}