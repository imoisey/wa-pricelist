<?php

$path = realpath(dirname(__FILE__) . '/../../vendor/PHPExcel');
require_once $path . '/PHPExcel.php';
require_once $path .'/PHPExcel/IOFactory.php';

class shopPricelistPluginBackendDownloadController extends waController {

    public $fill = array(
        'type'       => PHPExcel_Style_Fill::FILL_SOLID,
        'rotation'   => 0,
        'color'   => array(
            'rgb' => '000000'
        )
    );

    public $font = array(
        'size'  => 16,
        'color' => array(
		    'rgb' => 'FFFFFF'
	    )
    );

    public function execute() {
        $template_id = waRequest::get('template_id', false);
        if(!$template_id)
            throw new waException('Не передан ID шаблона', 500);
        
        $pricelist_model = new shopPricelistPluginModel();
        $pricelist = $pricelist_model->getById($template_id);

        if(!is_array($pricelist))
            throw new waException('Не удалось загрузить прайс-лист. Неверный ID', 500);
        $cats_array = json_decode($pricelist['categories']);

        // Шаблон для прайса
        $template_xlsx = $this->configPath($pricelist['storefront'].'.xlsx');
        $catTree = $this->createTree($cats_array);

        /**
         * Начинаем загрузку шаблона
         */
        $template_xlsx = wa()->getConfig()->getPluginPath('pricelist').'/lib/config/data/'.$pricelist['storefront'].'.xlsx';
        $pExcel = PHPExcel_IOFactory::createReader('Excel2007');
        $pExcel = $pExcel->load($template_xlsx);
        $pExcel->setActiveSheetIndex(0);
        $aSheets = $pExcel->getActiveSheet();

        // Начинаем загружать данные
        $cell_id = 3;
        foreach($catTree as $category) {
            if(!count($category['items']))
                continue;
            $category_name = $category['name'];
            $aSheets->mergeCells("A{$cell_id}:G{$cell_id}");
            $aSheets->setCellValue('A'.$cell_id, $category_name);
            $aSheets->getStyle('A'.$cell_id)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $aSheets->getStyle('A'.$cell_id)->getFill()->applyFromArray($this->fill);
            $aSheets->getStyle('A'.$cell_id)->getFont()->applyFromArray($this->font);
            foreach($category['items'] as $item) {
                // Загружаем фотографию
                $imagePath = shopImage::getPath($item['image_id']);
                wa_dump($imagePath);

                $aSheets->setCellValue('B'.$cell_id, $item['name']);
                $cell_id++;
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($pExcel, 'Excel2007');
        $file = "file.xlsx";
        $objWriter->save(wa()->getConfig()->getPluginPath('pricelist').'/lib/config/data/'.$file);

        /*
        $template_xlsx = wa()->getConfig()->getPluginPath('pricelist').'/lib/config/data/'.$pricelist['storefront'].'.xlsx';
        $path = realpath(dirname(__FILE__) . '/../../vendor/PHPExcel');
        require_once $path . '/PHPExcel.php';
        require_once $path .'/PHPExcel/IOFactory.php';
        $pExcel = PHPExcel_IOFactory::createReader('Excel2007');
        $pExcel = $pExcel->load($template_xlsx);
        $pExcel->setActiveSheetIndex(0);
        $aSheets = $pExcel->getActiveSheet();

        $cell_id = 3;
        foreach($cats_array as $category_id) {
            $category = $this->getProducts($category_id);
            foreach($category as $product) {
                $aSheets->setCellValue('B'.$cell_id, $product['name']);
                $cell_id++;
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($pExcel, 'Excel2007');
        $file = "file.xlsx";
        $objWriter->save(wa()->getConfig()->getPluginPath('pricelist').'/lib/config/data/'.$file);
        */
    }


    private function getProducts($category_id, $sort = false, $sort_type = false, $key = 'sku_id') {
        if (!$category_id) {
            return array();
        }

        // сортировка
        $sql_sort_type = ($sort_type ? 'DESC' : 'ASC');
        if ($sort == 'name') {
            $sql_sort = '`p`.`name`';
        } else if ($sort == 'artic') {
            $sql_sort = '`s`.`sku`';
        } else if ($sort == 'price') {
            $sql_sort = '`s`.`price`';
        } else {
            $sql_sort = '`cp`.`sort`';
        }

        $fieldsSql = '';
        if (!empty($this->pricesFields)) {
            $fieldsSql .= ',' . implode(',', array_keys($this->pricesFields));
        }

        $sql = 'SELECT p.id, s.id AS sku_id, s.sku, p.sku_id as product_sku_id, p.name, p.url, s.name as sku_name, s.compare_price, s.purchase_price, s.price, p.currency, s.count, p.category_id, cp.category_id add_category_id, p.summary, p.image_id
                ' . $fieldsSql . '
                FROM shop_product p
                JOIN shop_product_skus s ON s.product_id = p.id
                JOIN shop_category_products cp ON p.id = cp.product_id
                WHERE p.status = 1 AND cp.category_id IN (i:category_id)
                ORDER BY ' . $sql_sort . ' ' . $sql_sort_type . ', s.sort ASC';

        $category_model = new shopCategoryModel();
        $products = $category_model->query($sql, array('category_id' => $category_id))->fetchAll($key);

        return $products;
    }


    /**
     * Возвращает массив 
     *
     * @return void
     */
    private function createTree($cats_array) {
        if(!is_array($cats_array))
            throw new waException('Категории не в массиве', 500);
        $categories = array();
        $cat_model = new shopCategoryModel();
        foreach($cats_array as $category) {
            $category = $cat_model->getById($category);
            $products = $this->getProducts($category);
            $categories[] = array(
                'name' => $category['name'],
                'items' => $products,
            );
        }

        return $categories;
    }


}