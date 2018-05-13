<?php

    try{
        Cake\Core\Configure::load('GeneralApplicationUtilities_config');
        $logo = Cake\Core\Configure::read('logo');
        $logo_inline_style = Cake\Core\Configure::read('logo_inline_style');
    }
    catch(\Exception $e){

        Cake\Core\Configure::load('GeneralApplicationUtilities.plugin_config');
        $logo = Cake\Core\Configure::read('logo_default');

    }

    if( ($app_name = Cake\Core\Configure::read('app_name') ) == NULL){

        Cake\Core\Configure::load('app');
        $app = Cake\Core\Configure::read('App');
    }

    if(!isset($logo_inline_style))
        $logo_inline_style = ['width: 50px; height: 50px;'];

    echo $this->Html->tag('header');

        echo $this->Html->tag('nav', NULL, ['class' => 'navbar navbar-expand-md fixed-top navbar-dark bg-dark']);

            echo $this->Html->tag('div', NULL, ['class' => 'navbar-brand', 'id' => 'titleAreaBox']);

                echo $this->Html->image($logo, ['class' => 'img-fluid logo', 'alt' => 'logo', 'style' => $logo_inline_style ]);

                if( isset($app['appname']) )
                    echo $this->Html->tag('h1', $app['appname']);
                else if($app_name)
                    echo $this->Html->tag('h1', $app_name);

            echo $this->Html->tag('/div');

            echo $this->Html->tag('button', NULL, ['class' => 'navbar-toggler collapsed', 'type' => 'button',  'data-toggle' => 'collapse', 
                                        'data-target' => '#navbarCollapse', 'aria-expanded' => 'false', 'aria-controls' => 'navbarCollapse', 'aria-label' => "Toggle navigation" ]);

                echo $this->Html->tag('span', NULL, ['class' => 'navbar-toggler-icon']);

            echo $this->Html->tag('/button');  

            echo $this->Html->tag('div', NULL, ['class' => 'navbar-collapse collapse', 'id' => 'navbarCollapse', 'style' => '']);   
                
                echo $this->Menu->showMenu();

            echo $this->Html->tag('/div'); ## div#navbarCollapse ##

        echo $this->Html->tag('/nav'); ## nav#custom-bootstrap-menu ##

    echo $this->Html->tag('/header');

?>