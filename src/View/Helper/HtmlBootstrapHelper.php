<?php

/* src/View/Helper/BootstrapHelper.php */
namespace GeneralApplicationUtilities\View\Helper;

use Cake\View\Helper;
use Cake\Core\Configure;


class HtmlBootstrapHelper extends Helper {

    public $helpers = ['Html', 'Form', 'Paginator', 'Flash'];
    
    /**
     * 
     * 
     */
    public function paginatorLinks() {

        $this->Paginator->options([
            'url' => array_merge($_GET, $this->request->params['pass'])
        ]);

        return $this->Html->nestedList([
                    $this->Paginator->prev('<< Anterior'),
                    $this->Paginator->numbers(),
                    $this->Paginator->next('>> Próximo')
                        ], ['class' => 'pagination']);
    }

    /**
     *
     *
     */
    public function dropdownCreationBootstrap4(&$itens, $menu = false, $submenu = false){

        (!$menu) ? $menu = $this->Html->tag('ul', null, ['class' => 'navbar-nav mr-auto']) : $menu = $this->Html->tag('ul', null, ['class' => 'dropdown-menu']);

        foreach ($itens as $idItem => $item) {
                
            if(is_array($item)){

                if(!$submenu){

                    $menu .= $this->Html->tag('li', null, ['class' => 'dropdown nav-item dropdown-item']);
                    $menu .= $this->Html->link($idItem, 
                                                '#', 
                                                ['class' => 'dropdown-toggle nav-link', 'data-toggle' => 'dropdown', 'role' => 'button', 'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'escape' => false]);
                    $menu .= $this->Html->tag('ul', null, ['class' => 'dropdown-menu multi-level', 'role' => 'menu', 'aria-labelledby' => 'dropdownMenu']);

                }
                else{

                    $menu .= $this->Html->tag('li', null, ['class' => 'dropdown-submenu nav-item dropdown-item']);

                    $menu .= $this->Html->link($idItem, 
                                            '#', 
                                            ['tabindex' => -1, 'class' => 'dropdown-toggle dropdown-link nav-link', 'data-toggle' => 'dropdown', 'role' => 'button', 'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'escape' => false]);

                    $menu .= $this->Html->tag('ul', null, ['class' => 'dropdown-menu multi-level nav-item', 'role' => 'menu', 'aria-labelledby' => 'dropdownMenu']);
                }

                foreach ($item as $idSubItem => $subItem){

                    if(is_array($subItem)){

                        $menu .= $this->Html->tag('li', NULL, ['class' => 'dropdown-submenu nav-item dropdown-item']);

                        $menu .= $this->Html->link($idSubItem, 
                                            '#', 
                                            ['tabindex' => -1, 'class' => 'dropdown-toggle dropdown-link nav-link', 'data-toggle' => 'dropdown', 'role' => 'button', 'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'escape' => false]);

                        $menu .= $this->dropdownCreationBootstrap4($subItem, true, true);

                        $menu .= $this->Html->tag('/li'); 
                    }
                    else
                        $menu .= $this->Html->tag('li', $subItem, ['class' => 'dropdown-link nav-link']); 
                }
                    
                $menu .= $this->Html->tag('/ul');
                $menu .= $this->Html->tag('/li');

            }else
                $menu .= $this->Html->tag('li', $item); 
        }

        $menu .= $this->Html->tag('/ul');
        return $menu;      
    }

    /**
     *
     *
     */
    public function dropdownCreationBootstrap3(&$itens, $menu = false, $submenu = false){

        (!$menu) ? $menu = $this->Html->tag('ul', null, ['class' => 'navbar-nav mr-auto']) : $menu = $this->Html->tag('ul', null, ['class' => 'dropdown-menu']);

        foreach ($itens as $idItem => $item) {
                
            if(is_array($item)){

                if(!$submenu){

                    $menu .= $this->Html->tag('li', null, ['class' => 'dropdown nav-item dropdown-item']);
                    $menu .= $this->Html->link($idItem . $this->Html->tag('span', '', ['class' => 'caret']), 
                                                '#', 
                                                ['class' => 'dropdown-toggle', 'data-toggle' => 'dropdown', 'role' => 'button', 'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'escape' => false]);
                    $menu .= $this->Html->tag('ul', null, ['class' => 'dropdown-menu multi-level', 'role' => 'menu', 'aria-labelledby' => 'dropdownMenu']);

                }
                else{

                    $menu .= $this->Html->tag('li', null, ['class' => 'dropdown-submenu nav-item dropdown-item']);

                    $menu .= $this->Html->link($idItem . $this->Html->tag('span', '', ['class' => 'caret caret-right']), 
                                            '#', 
                                            ['tabindex' => -1, 'class' => 'dropdown-toggle dropdown-link', 'data-toggle' => 'dropdown', 'role' => 'button', 'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'escape' => false]);

                    $menu .= $this->Html->tag('ul', null, ['class' => 'dropdown-menu multi-level nav-item', 'role' => 'menu', 'aria-labelledby' => 'dropdownMenu']);
                }

                foreach ($item as $idSubItem => $subItem){

                    if(is_array($subItem)){

                        $menu .= $this->Html->tag('li', NULL, ['class' => 'dropdown-submenu nav-item dropdown-item']);

                        $menu .= $this->Html->link($idSubItem . $this->Html->tag('span', '', ['class' => 'caret caret-right']), 
                                            '#', 
                                            ['tabindex' => -1, 'class' => 'dropdown-toggle dropdown-link', 'data-toggle' => 'dropdown', 'role' => 'button', 'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'escape' => false]);

                        $menu .= $this->dropdownCreationBootstrap3($subItem, true, true);

                        $menu .= $this->Html->tag('/li'); 
                    }
                    else
                        $menu .= $this->Html->tag('li', $subItem); 
                }
                    
                $menu .= $this->Html->tag('/ul');
                $menu .= $this->Html->tag('/li');

            }else
                $menu .= $this->Html->tag('li', $item); 
        }

        $menu .= $this->Html->tag('/ul');
        return $menu;      
    } 
	
    /**
     *
     *
     */
    public function progressBar($percentual, string $mensagem = null, $estilo = null, bool $striped = false, bool $active = false){
    
        $classes = 'progress-bar ';
        
        if($striped){ $classes .= 'progress-bar-striped '; }
        
        if($estilo){ $classes .= 'progress-bar-' . $estilo; }
        
        if($active && $percentual < 100){ $classes .= 'active'; }
        
        return $this->Html->div('progress', 
                $this->Html->div($classes, 
                  $percentual . '% ' . $mensagem,
                  ['role' => 'progressbar', 'aria-valuenow' => $percentual, 'aria-valuemin' => '0', 'aria-valuemax' => '100', 'style' => 'width: ' .$percentual. '%; min-width: 2em;'])
                );
    }//progressBar
   	
    /**
     *
     *
     */ 
    public function multipleProgressBar(array $percentual, array $mensagem = [], array $estilo, array $striped, array $active, bool $exibePercentual = true){
    
        $percentuais = [];
        
        foreach ($percentual as $key => $value) {
            
            $classes[$key] = 'progress-bar ';
        
            if($striped[$key]){ $classes[$key] .= 'progress-bar-striped '; }

            if($estilo[$key]){ $classes[$key] .= 'progress-bar-' . $estilo[$key]; }

            if($active[$key] && $percentual[$key] < 100){ $classes[$key] .= ' active'; }

            if( $percentual[$key] > 0 ){
                $percentuais[] =  $this->Html->div($classes[$key], 
                              ($exibePercentual) ? $percentual[$key] . '% ' . $mensagem[$key] : $mensagem[$key] ,
                              [//'role' => 'progressbar'
                                  //, 'aria-valuenow' => $percentual[$key]
                                  //, 'aria-valuemin' => '0'
                                  //, 'aria-valuemax' => '100'
                                  'style' => 'width: ' .$percentual[$key]. '%;']);
            }
            
        }
        
        return $this->Html->div('progress', $percentuais);
                            
        
    }//progressBar
    	
    /**
     *
     *
     */
    public function sucesso(bool $valor){
        if($valor){
            return $this->label('success', $this->glyphicon('ok') . ' SIM');
        }else{
            return $this->label('danger', $this->glyphicon('remove') . ' NÃO');
        }
    }//sucesso
    	
    /**
     *
     *
     */
    public function label(string $estilo, string $mensagem){
        return $this->Html->tag('span', $mensagem, ['class' => 'label label-' . $estilo]);
    }//label
    	
    /**
     *
     *
     */
    public function badge(string $mensagem){
        return $this->Html->tag('span', $mensagem, ['class' => 'badge']);
    }//label
    	
    /**
     *
     *
     */
    public function glyphicon(string $title, array $options = []){
        return $this->Html->tag('span', ' ', ['aria-hidden' => 'true', 'class' => 'glyphicon glyphicon-' . $title]);
    }//glyphicon
    	
    /**
     *
     *
     */
    public function collapseLink(string $idAnchor, string $texto, string $conteudo, bool $botao = false, bool $expandido = false){
        
        $opcoes['data-toggle'] = 'collapse';
        $opcoes['aria-expanded'] = $expandido;
        $opcoes['aria-controls'] = $idAnchor;
        $opcoes['escape'] = false;
        
        if($botao){
            $opcoes['class'] = 'btn btn-primary';
            $opcoes['role'] = 'button';
        }
        
        return $this->Html->link($texto, '#'.$idAnchor, $opcoes) . $this->Html->div('collapse', $this->Html->div('well', $conteudo), ['id' => $idAnchor]);
        
	}//collapseLink
		
    /**
     *
     *
     */
	public function startTable(Array $headers, Array $options, String $responsive = NULL){

    	switch ($responsive) {
    		case 'sm':
    		case 'small':
    			$div = $this->Html->tag('div', NULL, ['class' => 'table-responsive-sm table-wrapper']);
    			break;
     		case 'md':
    		case 'medium':
    			$div = $this->Html->tag('div', NULL, ['class' => 'table-responsive-md table-wrapper']);
    			break;
    		case 'lg':
    		case 'large':
    			$div = $this->Html->tag('div', NULL, ['class' => 'table-responsive-lg table-wrapper']);
    			break;
    		case 'xl':
    		case 'extra-large':
    			$div = $this->Html->tag('div', NULL, ['class' => 'table-responsive-xl table-wrapper']);
    			break;
    		case NULL:
    			$div = $this->Html->tag('div', NULL, ['class' => 'container table-wrapper']);  			    			   		
    		default:
    			$div = $this->Html->tag('div', NULL, ['class' => 'table-responsive table-wrapper']);
    			break;
    	}

    	( !isset($options['class']) ) ? $options['class'] = 'table' : $options['class'] = 'table '.$options['class'];

    	$table = $this->Html->tag('table', NULL, $options);

    	$tableHeader = $this->Html->tag('thead') . $this->Html->tag('tr');

    	foreach($headers as $header => $option){

    		if( isset($option['paginate'])){
    			$paginate = $option['paginate'];
    			unset($option['paginate']);
    		}
    		else
    			$paginate = false;

    		if($paginate && is_array($option))
    			$tableHeader .= $this->Html->tag('th', $this->Paginator->sort($header), $option);
    		else if($paginate)
    			$tableHeader .= $this->Html->tag('th', $this->Paginator->sort($header));
    		else if(is_array($option))
    			$tableHeader .= $this->Html->tag('th', $header, $option);
    		else
    			$tableHeader .= $this->Html->tag('th', $header);

    		$tableHeader .= $this->Html->tag('/th');
    	}

    	$tableHeader .= $this->Html->tag('/tr') . $this->Html->tag('/thead');

    	return $div.$table.$tableHeader;
    }

    /**
     * 
     * 
     */
    public function checkBoxInput($name, Array $options, Bool $inline = false, Array $options_forAll = NULL, $type = 'checkbox'){

        echo $this->Html->tag('div', NULL, ['class' => 'form-checks-container']);

        if(isset($options[0]) || isset($options[1]))
            $options = array_flip($options);

        ($inline) ? $append_div_class = ' form-check-inline' : $append_div_class = '';

        ( !isset($options_forAll['div_options']) ) ?  $div_options = NULL :  $div_options = $options_forAll['div_options'];

        ( !isset($options_forAll['input_options']) ) ?  $input_options = NULL :  $input_options = $options_forAll['input_options'];

        ( !isset($options_forAll['label_options']) ) ?  $label_options = NULL :  $label_options = $options_forAll['label_options'];

        foreach($options as $key => $option){

            ( !isset($option['div_options']) && !isset($options_forAll['div_options']) ) ?  $div_options = ['class' => 'form-check'.$append_div_class] :  $div_options = $option['div_options'];

            ( !isset($option['input_options']) && !isset($options_forAll['input_options']) ) ?  $input_options = ['class' => 'form-check-input', 'type' => $type, 'name' => $name, 'id' => $name.'-'.$key, 'value' => $key] :  $input_options = $option['input_options'];

            ( !isset($option['label_options']) && !isset($options_forAll['label_options']) ) ?  $label_options = ['class' => 'form-check-label', 'for' => $name.'-'.$key] :  $label_options = $option['label_options'];

            ( !isset($input_options['name']) ) ? $input_options['name'] = $name : NULL;

            echo $this->Html->tag('div', NULL, $div_options);
  
                echo $this->Html->tag('input', NULL, $input_options);
                echo $this->Html->tag('label', $key, $label_options);

            echo $this->Html->tag('/div');

        }

        echo $this->Html->tag('/div');

    }

    /**
     * 
     * 
     */
    public function radioInput($name, Array $options, Bool $inline = false, Array $options_forAll = NULL){
        $this->checkBoxInput($name, $options, $inline, $options_forAll, 'radio');
    }

    /**
     *
     *
     */
    public function endTable(){

    	return $this->Html->tag('/table').$this->Html->tag('/div');
	}

    /**
     *
     *
     */
	public function fieldSet(string $legend, Array $field_set_options = [], Array $legend_options = []) {

        ( isset($field_set_options['class']) ) ?  $field_set_options['class'] .= ' form-group' : $field_set_options['class'] = 'form-group';

        return $this->Html->tag('fieldset', NULL, $field_set_options) . ' ' . $this->Html->tag('legend', $legend, $legend_options);
    }

	/**
     *
     *
     */
    public function fieldSetEnd() {
        return $this->Html->tag('/fieldset');
    }

}

?>