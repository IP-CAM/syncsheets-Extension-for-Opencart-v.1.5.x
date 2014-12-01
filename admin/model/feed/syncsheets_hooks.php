<?php
/* Syncsheet Field Hooks 
 * type: normal/regex
 * match: valid regex expression
 * */

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'store',
    'get'   =>  function($product,$field){
        return $product->getProductStore($product->id);
    },
    'add'   => function($key,$value,$product){
         $product->product['product_store'][0] = 0;
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'product_url',
    'get'   => function($product,$field){
        return $product->url->link('product/product','&product_id=' . $product->id);
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'store_id',
    'get'   =>  function($product,$field){
        return $product->getProductStore($product->id,true);
    },
    'add'   => function($key,$value,$product){
         $product->product['product_store'][0] = 0;
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'weight_class_unit',
    'get'   =>  function($product,$field){
        return $product->getProductWeightName($product->product['weight_class_id'],'unit');
    },
    'add'   => function($key,$value,$product){
         $product->product['product']['weight_class_id'] = $product->getProductWeightId($value,'unit');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'weight_class',
    'get'   =>  function($product,$field){
        return $product->getProductWeightName($product->product['weight_class_id'],'title');
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['weight_class_id'] = $product->getProductWeightId($value,'title');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'length_class_unit',
    'get'   =>  function($product,$field){
        return $product->getProductLengthName($product->product['length_class_id'],'unit');
    },
    'add'   => function($key,$value,$product){
         $product->product['product']['length_class_id'] = $product->getProductLengthId($value,'unit');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'length_class',
    'get'   =>  function($product,$field){
        return $product->getProductLengthName($product->product['length_class_id'],'title');
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['length_class_id'] = $product->getProductLengthId($value,'title');
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'tax_class',
    'get'   =>  function($product,$field){
        return $product->getProductTaxName($product->product['tax_class_id']);
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['tax_class_id'] = $product->getProductTaxId($value);
    }
);

$hooks[] = array(
    'type'  =>  'normal',
    'match' =>  'stock_status',
    'get'   =>  function($product,$field){
        return $product->getProductStock($product->product['stock_status_id']);
    },
    'add'   => function($key,$value,$product){
        $product->product['product']['stock_status_id'] = $product->getProductStockId($value);
    }
);

$hooks[] = array(
    'type' =>  'regex',
    'match'=>  '/^category.*/',
    'get'   =>  function($product,$field){
        if(array_key_exists($field, $product->productMap) && !empty($product->productMap[$field])){
            return false;
        }
        
        $catIndex = filter_var($field, FILTER_SANITIZE_NUMBER_INT);
        $cat = explode($catIndex, $field);
        $language_code = end($cat);
        $language_id = isset($product->lg[$language_code]['language_id']) ? $product->lg[$language_code]['language_id'] : '0';
        $category = array(); $return=array();
        $cats = $product->getProductCategories($product->id);
        if (!empty($cats)){
            foreach ($cats as $category_id){
                $path = $product->getCategoryPath($category_id,$language_id);
                if (!empty($path)) {
                    $category[] = $path;
                }
            }
        }
        $languageCode = substr($field,  strlen($field)-2,  strlen($field));
        foreach ($category as $catekey => $catitem) {
            $n = $catekey + 1;
            $return['category' . $n.$languageCode] = html_entity_decode($catitem);
        }
        return $return;
    },
    'addold'   =>  function($key,$value,$product){
        $category_id = 0;
        $catIndex = filter_var($key, FILTER_SANITIZE_NUMBER_INT);
        if($catIndex==1)
            $product->product['product_category']=array();
        
        $cat = explode($catIndex, $key);
        $languageCode = end($cat);
       
            if (isset($product->categories[$languageCode][html_entity_decode($value)])) {
                $category_id = $product->categories[$languageCode][html_entity_decode($value)];
                if ($category_id)
                    $product->product['product_category'][] = $category_id;
            }else {
                if (!empty($value))
                    $category_id = $product->saveCategory($value,$product->languages);
                if ($category_id)
                    $product->product['product_category'][] = $category_id;
            }
    },
    'add'   =>  function($key,$value,$product){
        $category_id = 0;
        $product->product['product_category'] = array();
        $catIndex = filter_var($key, FILTER_SANITIZE_NUMBER_INT);
        $cat = explode($catIndex, $key);
        $languageCode = end($cat);
        if($value){
            $product->productMap['product_category'][$languageCode] = html_entity_decode($value);
        }
    },
    '_bfs' => function($product){   
        if(isset($product->productMap['product_category'])){
            $category_id = false;
            
            foreach($product->productMap['product_category'] as $languageCode=>$value){
                if (isset($product->categories[$languageCode][$value]) && $product->categories[$languageCode][$value]){
                    $product->product['product_category'][] = $product->categories[$languageCode][$value];
                    $category_id = true;
                }
            }
            
            if(!$category_id && $product->productMap['product_category']){
                $category_id = $product->saveMulCategory($product->productMap['product_category'],$product->languages);
                foreach($product->productMap['product_category'] as $code=>$cats){
                    $product->categories[$code][$cats] = $category_id;
                    $product->product['product_category'][] = $category_id;
                }
            }else{

            }
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^name.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){
        list($name, $language) = explode('_', $key);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['name'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^tag.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){ 
        list($name, $language) = explode('_', $key);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['tag'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^description.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? html_entity_decode($product->product[$field]) : '';
    },
    'add'  =>  function($key,$value,$product){
        list($name, $language) = explode('_', $key);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['description'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^meta_key.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){
        $meta_text = explode('_', $key);
        $language = end($meta_text);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['meta_keyword'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^meta_desc.*/',
    'get'   =>  function($product,$field){ 
        return (isset($product->product[$field]) && !empty($product->product[$field])) ? $product->product[$field] : '';
    },
    'add'  =>  function($key,$value,$product){
        $meta_text = explode('_', $key);
        $language = end($meta_text);
        $language_id = isset($product->languages[$language]['language_id']) ? $product->languages[$language]['language_id'] : '0';
        $product->product['product_description'][$language_id]['meta_description'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^manufacturer.*/',
    'get'   =>  function($product,$field){
        return (isset($product->product['manufacturer']) && !empty($product->product['manufacturer'])) ? $product->product['manufacturer'] : '';
    },
    'add'  => function($key,$value,$product){
        $product->product['product']['manufacturer_id'] = $product->saveManufacurer($value);
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^additional.*/',
    'get'   =>  function($product,$field){
        $imgs = array();
        $images = $product->model_catalog_product->getProductImages($product->id);
        if($images){
            $product->product['product_image'] = array();
        }
        foreach ($images as $image) {
            $imgs[] = $image['image'];
        }
        return implode('|', $imgs);
    },
    'add'  => function($key,$value,$product){
        $images = explode('|', $value);
        foreach ($images as $key => $image) {
            $product->product['product_image'][$key]['image'] = $image;
            $product->product['product_image'][$key]['sort_order'] = $key;
        }
    }
    
);   

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^discount.*/',
    'beforetFilter'=> function($header,$product){
        $regex = "/\{(.*?)\}/";
        preg_match($regex, $header, $match);
        parse_str($match[1],$parsed_header);
        if(isset($parsed_header['group'])){
            $group = $product->getCustomerGroupByName($parsed_header['group']);
            $parsed_header['customer_group_id'] = $group['customer_group_id'];
            $product->default_discount = $parsed_header;
        }
    },
    'get'=>function($product,$field){
    if(isset($product->product['discount']))      
        return $product->product['discount'];
    },
    'add' => function($key,$value,$product){
        $value = str_replace("'","\"", $value);
        $dicounts = json_decode($value);
        if(is_array($dicounts)){
            $product->product['product_discount'] = array();
            foreach($dicounts as $item){
                $product->product['product_discount'][] = (array)$item;
            }
        }else{
            $product->product['product_discount'] = array();
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^special.*/',
    'beforetFilter'=> function($header,$product){
        $regex = "/\{(.*?)\}/";
        preg_match($regex, $header, $match);
        parse_str($match[1],$parsed_header);
        if(isset($parsed_header['group'])){
            $group = $product->getCustomerGroupByName($parsed_header['group']);
            $parsed_header['customer_group_id'] = $group['customer_group_id'];
            $product->default_special = $parsed_header;
        }
    },
    'get'=>function($product,$field){
    if(isset($product->product['special']))      
        return $product->product['special'];
    },
    'add' => function($key,$value,$product){
        $value = str_replace("'","\"", $value);
        $special = json_decode($value);
        if(is_array($special)){
            $product->product['product_special'] = array();
            foreach($special as $item){
                $product->product['product_special'][] = (array)$item;
            }
        }else{
            $product->product['product_special'] = array();
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^option.*/',
    'get'   => function($product,$field){
        return $product->product['options'];
    },
    'add' => function($key,$value,$product){ 
//        $value = str_replace("'","\"", $value);
//        print_r(json_decode(stripslashes($value))); exit;
        
        $options = json_decode($value);
        if($options){
            $product->product['product_option']=array();
            foreach($options as $item){
                $product->product['product_option'][] = (array)$item;
            }
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^at(\d+).*/',
    'get'   =>  function($product,$field){ 
        if (isset($product->attributes[$field])) { 
            return $product->attributes[$field];
        } else {
            return '';
        }
    },
    'add' => function($key,$value,$product){
        preg_match('/at(\d+)/', $key, $matches);
        if (isset($matches[1])) {          
            $attribute_id = $matches[1];
            $language_code = substr($key, strlen($key) - 2, strlen($key));
            $language_id = isset($product->languages[$language_code]['language_id']) ? $product->languages[$language_code]['language_id'] : '0';
            $product->product['product_attribute'][$attribute_id]['name'] = '';
            $product->product['product_attribute'][$attribute_id]['attribute_id'] = $attribute_id;
            $product->product['product_attribute'][$attribute_id]['product_attribute_description'][$language_id]['text'] = $value;
        }
    }
);

$hooks[] = array(
    'type'  => 'regex',
    'match' => '/^seo.*/',
    'get'   =>  function($product,$field){
        return (isset($product->product['seo_keyword']) && !empty($product->product['seo_keyword'])) ? $product->product['seo_keyword'] : '';
    },
    'add'  => function($key,$value,$product){
        $product->product['product']['keyword'] = $value;
    }
);

$hooks[] = array(
    'type'  => 'normal',
    'match' =>  'related',
    'get'   =>  function($product,$field){
        $related = '';
        $sql = "select p.product_id, p.model from ".DB_PREFIX."product p left join ".DB_PREFIX."product_related rp on (p.product_id=rp.related_id) where rp.product_id='{$product->id}'";
        $query = $product->db->query($sql);
                    foreach($query->rows as $row){
                        $related .=$row['model'].'|';
                    }
         return trim($related,'|');
    },
    'add' => function($key,$value,$product){
        if($value){
            $related_models = explode('|', $value);
            foreach($related_models as $model){
                if($product_id = $product->getProductByModel($model)){
                    $product->product['product_related'][] = $product_id;
                }
            }
        }
    }
);



$hooks[] = array(
        'type' => 'normal',
        'match' => 'related_group',
        'get' => function($product, $field) {

    },
    'add' => function($key, $value, $product) {
        if($value){
            $rkey = trim(md5($value));
            $product->product['product_related_group'][$rkey][]=$product->id;
        }
    },
    'cb'  => function($product){
        if(isset($product->product['product_related_group'])){
            $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "product_related2");
            foreach($product->products['product_related_group'] as $groups){
                if(count($groups)>1){
                    for($rp=0;$rp<count($groups);$rp++){
                        $product->setRelatedProducts2($groups[$rp],$groups);
                    }
                }
            }
        }
    }
);
?>
