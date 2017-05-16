<?php

class shopPricelistProductsCollection extends shopProductsCollection {

    public function categoryPrepare($id, $auto_title = false)
    {
        parent::categoryPrepare($id, $auto_title);
        $t = $this->addJoin('shop_product_skus');
        $this->fields[] = $t . '.sku';
        $this->fields[] = $t . '.name sku_name';
        $this->fields[] = $t . '.price';
        $this->fields[] = $t . '.count';
    }
    
    public function getProducts($fields = "*", $offset = 0, $limit = null, $escape = true) {
        $sql = $this->getSQL();

        $sql = "SELECT " . ($this->joins ? 'DISTINCT ' : '') . $this->getFields('id,name,currency,category_id') . " " . $sql;
        $sql .= $this->_getOrderBy();

        $data = $this->getModel()->query($sql)->fetchAll('id');
        if (!$data) {
            return array();
        }
        return $data;
    }

}