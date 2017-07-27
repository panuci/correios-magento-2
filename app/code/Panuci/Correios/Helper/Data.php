<?php

namespace Panuci\Correios\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getBoxes($request)
    {
        $_boxes = array();
        foreach ($request->getAllItems() as $_product) {
            for($x = 0; $x < $_product->getQty(); $x++){
                $itemAlt = $itemLar = $itemCom = 0;
                if($_product->getProduct()->getData('altura') != '' && $_product->getProduct()->getData('altura') > 0){
                    $itemAlt = $_product->getProduct()->getData('altura');
                }
                if($_product->getProduct()->getData('largura') != '' && $_product->getProduct()->getData('largura') > 0){
                    $itemLar = $_product->getProduct()->getData('largura');
                }
                if($_product->getProduct()->getData('comprimento') != '' && $_product->getProduct()->getData('comprimento') > 0){
                    $itemCom = $_product->getProduct()->getData('comprimento');
                }

                $_boxes[] = array(
                    'length'    => $itemCom,
                    'width'     => $itemLar,
                    'height'    => $itemAlt
                );
            }            
        }

    	return $_boxes;
    }

    public function priceFormat($stringPrice){
        $stringPrice = str_replace(',', '.', $stringPrice);
        $stringPrice = preg_replace("/[^0-9\.]/", "", $stringPrice);
        $stringPrice = str_replace('.', '',substr($stringPrice, 0, -3)) . substr($stringPrice, -3);

        $valorAdcional = $this->getConfig('carriers/correios/valoradcional');
        if(is_numeric($valorAdcional)){
            $stringPrice = $stringPrice + str_replace(',','.', $valorAdcional);
        }

        return (float) $stringPrice;
    }

    public function prazoFormat($stringPrazo){
        $prazoAdcional = $this->getConfig('carriers/correios/prazoadcional');
        if(is_numeric($prazoAdcional)){
            $stringPrazo = $stringPrazo + $prazoAdcional;
        }

        return $stringPrazo;
    }

    public function getMethodTitle($codigo, $prazo){
        $codes = array(
            '04510' => 'PAC',
            '04014' => 'Sedex',
            '40215' => 'Sedex',
            '40290' => 'Sedex',
            '40045' => 'Sedex',
            '41068' => 'PAC',
            '04669' => 'PAC',
            '04162' => 'Sedex',
            '40096' => 'Sedex',
            '81019' => 'E-Sedex',
        );

        if($msg = $codes[$codigo]){
            if($prazo > 1){
                $msg .= ' - Em média ' . $prazo . ' dias úteis.';
            }else{
                $msg .= ' - Em média ' . $prazo . ' dia útil.';
            }
            return $msg;
        }

        return 'Correios - Para utilizar este método entre em contato conosco.';
    }

    public function xmlarray($source,$arr=null){
        $xml = is_object($source) ? $source : simplexml_load_string($source);
        $iter = 0;
        foreach($xml->children() as $b){
            $a = $b->getName();
            if(!$b->children()){
                    $arr[$a] = trim($b[0]);
            }else{
                    $arr[$a][$iter] = array();
                    $arr[$a][$iter] = $this->xmlarray($b,$arr[$a][$iter]);
            }
            $iter++;
        }
        return $arr;
    }
}