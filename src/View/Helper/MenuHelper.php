<?php

/* src/View/Helper/BootstrapHelper.php */
namespace GeneralApplicationUtilities\View\Helper;

use Cake\View\Helper;
use Cake\Controller\Controller;
use Cake\View\View;
use Cake\Database\Schema\Collection;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;


class MenuHelper extends Helper {

    public $helpers = ['Html', 'Paginator', 'Flash', 'GeneralApplicationUtilities.Util', 'GeneralApplicationUtilities.HtmlBootstrap'];
    private $thresholdBelongsTo;
    private $thresholdNeighbors;
    private $menu_create;
    private $config_file = 'menu_config';
    private $enToLanguageTranslation;
    private $bake_language;

    public function __construct(View $View, array $config = []){

        parent::__construct($View, $config);

        try{
            Configure::load('GeneralApplicationUtilities_config', 'default');
            $this->bake_language = Configure::read('bake_language');
        }
        catch(\Exception $e){
            Configure::load('GeneralApplicationUtilities.plugin_config', 'default');
            $this->bake_language = 'en';
        }

        $this->thresholdBelongsTo = Configure::read('thresholdBelongsTo');
        $this->thresholdNeighbors = Configure::read('thresholdNeighbors');
        $this->menu_create = Configure::read('menuCreate');

        try{
            Configure::load('GeneralApplicationUtilities.plugin_config', 'default');

            $this->enToLanguageTranslation = Configure::read('enToLanguageTranslation.'.$this->bake_language);
        }
        catch(\Exception $e){
            $this->enToLanguageTranslation = ['index' => 'index', 'view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delet' => 'delet', 'deletMsg' => 'Are you sure you want to delete', 'list_by_association_ajax' => 'list_by_association_ajax'];
        }

    }

    /**
     * 
     * 
     */
    public function showMenu(){

        $this->checkMenuExist();

        try {
            Configure::load($this->config_file, 'default');

            $itens = Configure::read('menu');

            if(is_array($itens) ){

                array_walk_recursive($itens, array($this, 'makeLink'));
                
                $menu = $this->HtmlBootstrap->dropdownCreationBootstrap4($itens);

                return $menu;
            }    

        } catch (\Exception $e) {
            echo 'Exceção capturada: ',  $e->getMessage(), "\n";
        }

    }

    /**
     *  Check if the menu exist. If not create the menu file.
     * 
     */
    private function checkMenuExist(){

        try{
            Configure::load($this->config_file, 'default');
        }
        catch(\Exception $e){

            switch ($this->menu_create) {
                case 'Belongsto':
                case 'BelongsTo':
                case 'belongsto':
                    $menu = $this->createMenuArrayByBelongsTo();
                    break;
                case 'Neighbors':
                case 'neighbors':
                    $menu = $this->createMenuArrayByNeighbors();
                    break;
                default:
                    $menu = $this->createMenuArrayByNeighbors();
                    break;
            }

            Configure::write('menu', $menu);
            Configure::dump($this->config_file, 'default', ['menu']);
        }
    }

    /**
     *
     *
     */
    private function createMenuArrayByBelongsTo(){

        $db = ConnectionManager::get('default');

        $collection = $db->schemaCollection();

        $tables_names = $collection->listTables();

        $belognsTo = [];
        $lookup_array = [];

        try{

            foreach($tables_names as $table_name){

                $table_name_humanize = str_replace('_', ' ', $table_name);

                $table_name_humanize = ucwords($table_name_humanize);

                $controller_name =  str_replace(' ', '', $table_name_humanize);

                $tabela = TableRegistry::get($table_name);
                $associacoes = $tabela->associations()->getIterator();

                $belognsTo_cont = 0;
                $belognsTo_table = [];
                $belongsTo_lookup_array = [];

                foreach($associacoes as $associacao){

                    if($associacao->type() == 'manyToOne'){
                                
                        $association_name = $associacao->name();

                        $name_humanize = $this->Util->humanizeOnUppercase($association_name);

                        $this->createMenuLink($belognsTo_table, $name_humanize, $association_name);

                        $belongsTo_lookup_array[$association_name] = 'true';

                        $belognsTo_cont++;
                    }

                }

                if($belognsTo_cont >= $this->thresholdBelongsTo){

                    $this->createMenuLink($menu_multidimensional, $table_name_humanize, $controller_name, $belognsTo_table);

                    $lookup_array = array_merge($lookup_array, $belongsTo_lookup_array);
                    $lookup_array[$controller_name] = true;
                }

            }

            $this->createMenuDefault($menu_bidimencional, $tables_names, $lookup_array);

            $menu = array_merge($menu_bidimencional, $menu_multidimensional);

        }
        catch(\Exception $e){
            $this->createMenuDefault($menu, $tables_names);
        }

        ksort($menu);

        return $menu;
    }

    /**
     *
     *
     */
    private function createMenuArrayByNeighbors(){

        $db = ConnectionManager::get('default');

        $collection = $db->schemaCollection();

        $tables_names = $collection->listTables();

        try{

            foreach($tables_names as $table_name){

                $table_name_humanize = str_replace('_', ' ', $table_name);

                $table_name_humanize = ucwords($table_name_humanize);

                $controller_name =  str_replace(' ', '', $table_name_humanize);

                $tabela = TableRegistry::get($table_name);
                $associations = $tabela->associations()->getIterator();
                
                $associations_cont[$table_name_humanize] = count($associations);
            
            }

            asort($associations_cont);

            end($associations_cont);

            for($i = 0;$i < $this->thresholdNeighbors; $i++){

                $value = current($associations_cont);
                $key = key($associations_cont);
                prev($associations_cont);

                $top_associations[$key] = $value;
            }

            $menu = $this->selectAssociations($top_associations, $associations_cont);
    
        }
        catch(\Exception $e){
            $this->createMenuDefault($menu, $tables_names);
        }

        ksort($menu);

        return $menu;
    }

    /**
     *
     *
     */
    private function selectAssociations($chosen_associations, Array &$associations_cont, &$menu = NULL, Bool $first = true){

        foreach($chosen_associations as $table_name => $quant){

            $table = TableRegistry::get($table_name);

            if($menu === NULL || $first){

                $table_name_humanize = str_replace('_', ' ', $table_name);

                $table_name_humanize = ucwords($table_name_humanize);
            
                $this->createMenuLink($menu, $table_name_humanize, $table_name);
            }

            $associations_cont[$table_name] = true;

            $associations = $table->associations()->getIterator();

            foreach($associations as $association){

                $association_name_humanize = $this->Util->humanizeOnUppercase($association->name());

                if( $associations_cont[$association_name_humanize] !== true && !isset($chosen_associations[$association_name_humanize]) ){

                    $this->createMenuLink($menu, $association_name_humanize, $association->name(), $null, $table_name);
 
                    $associations_cont[$association_name_humanize] = true;

                    $new_chosen_associations[$association_name_humanize] = 'true';
                }
            }

        }

        if(isset($new_chosen_associations))
            $this->selectAssociations($new_chosen_associations, $associations_cont, $menu, false);

        return $menu;
    }

    /**
     *
     *
     */
    private function createMenuDefault( &$menu, Array &$itens, $lookup = NULL){

        foreach($itens as $table_name){

            $table_name_humanize = str_replace('_', ' ', $table_name);

            $table_name_humanize = ucwords($table_name_humanize);

            $controller_name =  str_replace(' ', '', $table_name_humanize);

            if(is_array($lookup)){
                if(!array_key_exists($controller_name, $lookup))
                    $this->createMenuLink($menu, $table_name_humanize, $controller_name);
            }
            else
               $this->createMenuLink($menu, $table_name_humanize, $controller_name); 
        }
    }

    /**
     *
     *
     */
    private function createMenuLink(&$menu, String $name_humanize, String $name, Array &$association = NULL, $father = NULL){

        $tmp_array = [$name_humanize.' '.ucfirst($this->enToLanguageTranslation['index']) => str_replace(' ', '', $name).'.'.$this->enToLanguageTranslation['index'],
        $name_humanize.' '.ucfirst($this->enToLanguageTranslation['add']) => str_replace(' ', '', $name).'.'.$this->enToLanguageTranslation['add']];

        if(is_array($association))
            $tmp_array[$name_humanize.'_associations'] = $association;

        if( !isset($father) )
            $menu[$name_humanize] = $tmp_array;
        else{

            if(isset($menu[$father]) )
                $menu[$father][$name_humanize] = $tmp_array;
            else{

                $menu_father = $this->Util->findArrayPathToKey($father, $menu, $null, true);

                $tmp = &$menu;

                foreach($menu_father as $path)
                    $tmp = &$tmp[$path];
                
                $tmp[$name_humanize] = $tmp_array;
            }

        }  

        unset($tmp_array);
    }

    /**
     *
     *
     */
    private function makeLink(&$item1, $key){
        
        $ctrlLink = explode(".", $item1);
        $ctrl = $ctrlLink[0];
        $href = $ctrlLink[1];

        $item1 = $this->Html->link($key, ['controller' => $ctrl, 'action' => $href], ['class' => 'dropdown-link nav-link']);
        
	}

}