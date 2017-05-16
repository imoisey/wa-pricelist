<?php

$path = realpath(dirname(__FILE__) . '/../../vendor/PHPExcel');
require_once $path . '/PHPExcel.php';
require_once $path .'/PHPExcel/IOFactory.php';

class shopPricelistPluginBackendDownloadController extends waController {

    private $fill = array(
        'type'       => PHPExcel_Style_Fill::FILL_SOLID,
        'rotation'   => 0,
        'color'   => array(
            'rgb' => '000000'
        )
    );

    private $font = array(
        'size'  => 15,
        'bold'  => false,
        'color' => array(
		    'rgb' => 'FFFFFF'
	    )
    );

    private $alignment = array(
        'horizontal' 	=> PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical'   	=> PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'rotation'   	=> 0,
        'wrap'       	=> true,
        'shrinkToFit'	=> false,
        'indent'	=> 5
    );

    private $out_stock_font = array(
        'color' => array(
            'rgb' => 'A0A0A0',
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
        // Получаем массив категорий из настроек
        $tree_cats = $this->createTree($cats_array);

        // Загружаем шаблон для прайса
        $template_xlsx = wa()->getConfig()->getPluginPath('pricelist').'/lib/config/data/'.$pricelist['storefront'].'.xlsx';
        $pExcel = PHPExcel_IOFactory::createReader('Excel2007');
        $pExcel = $pExcel->load($template_xlsx);
        $pExcel->setActiveSheetIndex(0);
        $aSheets = $pExcel->getActiveSheet();
        // Начинаем заполнять данные в файл
        $cell_id = 3;
        foreach($tree_cats as $category) {
            // Пропускаем, если в категории нет товара
            if(count($category['items']) == 0 || 
            ($category['count_stock'] == 0 && $pricelist['stock'] == 1) )
                continue;
            // Стилизуем категорию
            $aSheets->mergeCells("A{$cell_id}:G{$cell_id}");
            $aSheets->getStyle("A{$cell_id}")->getFill()->applyFromArray($this->fill);
            $aSheets->getStyle("A{$cell_id}")->getFont()->applyFromArray($this->font);
            $aSheets->getStyle("A{$cell_id}")->getAlignment()->applyFromArray($this->alignment);
            $aSheets->setCellValue("A{$cell_id}", $category['name']);
            $cell_id++;
            // Выводим товары
            foreach($category['items'] as $product_id => $product) {
                /**
                 * A - фотография
                 * B - название
                 * С - серия
                 * D - оптовая цена
                 * E - розничная цена
                 * F - ссылка на карточку товара
                 * G - остаток
                 */

                // Стилизуем ячейки
                $aSheets->getStyle("A{$cell_id}")->getAlignment()->applyFromArray($this->alignment);
                $aSheets->getStyle("B{$cell_id}")->getAlignment()->applyFromArray($this->alignment);
                $aSheets->getStyle("B{$cell_id}")->getFont()->setSize(15);
                $aSheets->getStyle("B{$cell_id}")->getFont()->setBold(true);
                $aSheets->getStyle("C{$cell_id}")->getAlignment()->applyFromArray($this->alignment);
                $aSheets->getStyle("D{$cell_id}")->getAlignment()->applyFromArray($this->alignment);
                $aSheets->getStyle("E{$cell_id}")->getAlignment()->applyFromArray($this->alignment);
                $aSheets->getStyle("F{$cell_id}")->getAlignment()->applyFromArray($this->alignment);
                $aSheets->getStyle("G{$cell_id}")->getAlignment()->applyFromArray($this->alignment);

                 // Вычисляем остаток, чтобы подсветить не в наличии
                $count = $product['count'] > 0 ? $product['count'] : 0;
                $aSheets->setCellValue("G{$cell_id}", $count);
                if($count == 0 && $pricelist['stock'] == 0) {
                    $aSheets->getStyle("A{$cell_id}")->getFont()->applyFromArray($this->out_stock_font);
                    $aSheets->getStyle("B{$cell_id}")->getFont()->applyFromArray($this->out_stock_font);
                    $aSheets->getStyle("C{$cell_id}")->getFont()->applyFromArray($this->out_stock_font);
                    $aSheets->getStyle("D{$cell_id}")->getFont()->applyFromArray($this->out_stock_font);
                    $aSheets->getStyle("E{$cell_id}")->getFont()->applyFromArray($this->out_stock_font);
                    $aSheets->getStyle("F{$cell_id}")->getFont()->applyFromArray($this->out_stock_font);
                    $aSheets->getStyle("G{$cell_id}")->getFont()->applyFromArray($this->out_stock_font);
                }
                elseif($count == 0 && $pricelist['stock'] == 1)
                    continue;

                // Вставляем картинку товара
                $aSheets->getRowDimension($cell_id)->setRowHeight(96);
                $image_path = shopImage::getThumbsPath($this->getImage($product['image_id']), '96x96');
                if(file_exists($image_path)) {
                    $image = new PHPExcel_Worksheet_Drawing();
                    $image->setPath($image_path);
                    $image->setCoordinates("A{$cell_id}");
                    $image->setOffsetX(35);
                    $image->setOffsetY(20);
                    $image->setWorksheet($aSheets);
                }

                // Название товара
                $aSheets->setCellValue("B{$cell_id}", $product['name']);
                // Серия
                $aSheets->setCellValue("C{$cell_id}", $category['name']);
                // Оптовая цена
                $discount = ($pricelist['discount'] / 100) * $product['price'];
                $aSheets->setCellValue("D{$cell_id}", $product['price'] - $discount);
                // Розничная цена
                $aSheets->setCellValue("E{$cell_id}", $product['price']);

                $cell_id++;
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($pExcel, 'Excel2007');
        $file = "file.xlsx";
        $objWriter->save(wa()->getConfig()->getPluginPath('pricelist').'/lib/config/data/'.$file);

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
        } else if ($sort == 'count') {
            $sql_sort = '`s`.`count`';
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
                WHERE p.status = 1 AND p.category_id = i:category_id
                ORDER BY ' . $sql_sort . ' ' . $sql_sort_type . ', s.sort ASC';

        $category_model = new shopCategoryModel();
        $products = $category_model->query($sql, array('category_id' => $category_id))->fetchAll($key);

        return $products;
    }


    /**
     * Возвращает данные картинки продукта
     *
     * @param int $image_id
     * @return void
     */
    public function getImage($image_id) {
        if(!is_numeric($image_id))
            return false;
        $image_model = new shopProductImagesModel();
        return $image_model->getById($image_id);
    }

    /**
     * Возвращает url на карточку товара
     *
     * @param array $product
     * @return void
     */
    public function getProductUrl($product) {
        
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
            $products = $this->getProducts($category['id'], 'count', 'DESC');
            // Считаем количество товаров в наличии
            $count_stock = 0;
            foreach($products as $i => $product) {
                if($product['count'] > 0)
                    $count_stock++;
            }
            $categories[] = array(
                'name' => $category['name'],
                'count_stock' => $count_stock,
                'items' => $products,
            );
        }

        return $categories;
    }


}