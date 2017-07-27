<?php

namespace Panuci\Correios\Model\Carrier\Source;

use Magento\Framework\Option\ArrayInterface;

class Codigos implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $options = [
            [
                'label' => __('PAC - Sem contrato (04510)'),
                'value' => '04510',
            ],
            [
                'label' => __('Sedex - Sem contrato (04014)'),
                'value' => '04014',
            ],
            [
                'label' => __('Sedex 10 (40215)'),
                'value' => '40215',
            ],
            [
                'label' => __('Sedex HOJE (40290)'),
                'value' => '40290',
            ],
            [
                'label' => __('Sedex a Cobrar (40045)'),
                'value' => '40045',
            ],            
            [
                'label' => __('PAC - Com contrato (41068)'),
                'value' => '41068',
            ],
            [
                'label' => __('PAC - Com contrato (04669)'),
                'value' => '04669',
            ],
            [
                'label' => __('Sedex - Com contrato (04162)'),
                'value' => '04162',
            ],
            [
                'label' => __('Sedex - Com contrato (40096)'),
                'value' => '40096',
            ],
            [
                'label' => __('E-Sedex - Com contrato (81019)'),
                'value' => '81019',
            ]
        ];
        return $options;
    }
}