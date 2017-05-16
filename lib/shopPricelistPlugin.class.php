<?php

class shopPricelistPlugin extends shopPlugin
{
    public function backendMenu() {
        $html = '';

        if($this->getSettings('status') == 'on') {
            $html .= '<li ' . (waRequest::get('plugin') == $this->id ? 'class="selected"' : 'class="no-tab"') . '>
                        <a href="?plugin=pricelist">' . _wp('Прайсы') . '</a>
                    </li>';
        }
        
        return array('core_li' => $html);
    }
}
