<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_map extends WPCOM_Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'pos' => array(
                    'name' => '位置',
                    'desc' => '<a href="http://api.map.baidu.com/lbsapi/getpoint/index.html" target="_blank">点击拾取坐标信息</a>',
                    'value'  => '116.403963,39.915119'
                ),
                'title' => array(
                    'name' => '标题',
                    'desc' => '例如公司名称'
                ),
                'address' => array(
                    'name' => '地址',
                    'desc' => '可以是公司地址，也可以是一段介绍文字'
                ),
                'scrollWheelZoom' => array(
                    'name' => '滚轮缩放',
                    'type' => 't',
                    'desc' => '是否允许鼠标滚轮缩放，开启将可以使用鼠标滚轮放大缩小地图',
                    'value'  => '0'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'height' => array(
                    'name' => '高度',
                    'type' => 'length',
                    'mobile' => 1,
                    'value'  => '400px'
                ),
                'margin' => array(
                    'name' => '外边距',
                    'type' => 'trbl',
                    'use' => 'tb',
                    'mobile' => 1,
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => '20px'
                )
            )
        );
        parent::__construct( 'map', '百度地图', $options, 'map' );
    }

    function classes( $atts, $depth ){
        $classes = $depth==0?' container':'';
        return $classes;
    }

    function style($atts){
        return array(
            'height' => array(
                '' => 'height: {{value}};'
            )
        );
    }

    function template($atts, $depth){
        $content = '';
        $content .= isset($atts['title'])&&$atts['title'] ? '<h3 class="map-title">'.$atts['title'].'</h3>':'';
        $content .= isset($atts['address'])&&$atts['address'] ? '<p class="map-address">'.$atts['address'].'</p>':'';
        echo wpcom_map($content, isset($atts['pos'])?$atts['pos']:'', isset($atts['scrollWheelZoom'])?$atts['scrollWheelZoom']:0, 0, 0);
    }
}

register_module( 'WPCOM_Module_map' );