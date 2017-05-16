<?php

class shopPricelistPluginBackendUpdateController extends waJsonController {
    
    public function execute() {
        $template_id    = waRequest::post('template_id', false);
        $categories     = waRequest::post('categories', false);
        if(!$template_id)
            throw new waException('ID шаблона не получен', 500);
        if(!$categories)
            throw new waException('Категории не могут быть пустыми', 500);

        $data = array();
        $data['categories'] = json_encode($categories);
        $pricelist_model = new shopPricelistPluginModel();
        $update = $pricelist_model->updateById($template_id, $data);

        if($update == true)
            $this->response = 'OK';
        else
            $this->response = 'ERROR';

        return $this->response;
    }
}