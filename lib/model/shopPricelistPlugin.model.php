<?php

class shopPricelistPluginModel extends waModel {
    protected $table = 'shop_pricelist_templates';
    
    // Получаем категории по ID шаблона
    public function getCategoriesById($template_id, $fields = 'id, depth, name, parent_id') {
        $template_data = $this->getById($template_id);
        // Запись не найдена
        if(!is_array($template_data) or count($template_data) == 0)
            return false;
        $categories = json_decode($template_data['categories']);
        $fields = $this->escape($fields);
        $sql = "SELECT $fields FROM shop_category WHERE id IN(i:id)";
        $categories = $this->query($sql, array('id' => $categories))->fetchAll();

        return $categories;
    }
}