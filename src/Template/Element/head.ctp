<?php

    echo $this->Html->tag('head');

        echo $this->Html->charset();

        echo $this->Html->meta('X-UA-Compatible', 'IE=edge');
        echo $this->Html->meta('viewport', 'width=device-width, initial-scale=1');
        echo $this->Html->meta('favicon.ico', '/favicon.ico', ['type' => 'icon']); 
        echo $this->Html->meta('description', 'Applicação de manutemção preventiva');
        echo $this->Html->meta('author', 'Digital Labs');

        echo $this->Html->css('https://fonts.googleapis.com/css?family=Ubuntu');

        echo $this->Html->tag('title',$this->fetch('title'));

        ## Bootstrap core CSS Bootstrap theme ##
        echo $this->Html->css('GeneralApplicationUtilities.bootstrap.min'); 
        ## IE10 viewport hack for Surface/desktop Windows 8 bug CSS ##
        echo $this->Html->css('GeneralApplicationUtilities.ie10-viewport-bug-workaround');
        ## GeneralApplicationUtilities theme CSS ##
        echo $this->Html->css('GeneralApplicationUtilities.theme');
        ## Jquery UI CSS ##
        echo $this->Html->css('GeneralApplicationUtilities.jquery-ui/jquery-ui');
        ## Lightbox modal CSS ##
        echo $this->Html->css('GeneralApplicationUtilities.lightbox/lightbox');
        ## Bootstrap Date Picker plugin CSS ##        
        echo $this->Html->css('GeneralApplicationUtilities.datepicker/bootstrap-datepicker3.min');


        ## Jquery core JS ##
        echo $this->Html->script('GeneralApplicationUtilities.jquery-3.3.1.min');
        ## Bootstrap core JS ##
        echo $this->Html->script('GeneralApplicationUtilities.bootstrap.bundle.min');
        ## IE10 viewport hack for Surface/desktop Windows 8 bug JS ##
        echo $this->Html->script('GeneralApplicationUtilities.ie10-viewport-bug-workaround');
        ## JS Libary with utility functions ##
        echo $this->Html->script('GeneralApplicationUtilities.util');
        ## Jquery UI JS ##
        echo $this->Html->script('GeneralApplicationUtilities.jquery-ui/jquery-ui');
        ## Lightnox modal JS ##
        echo $this->Html->script('GeneralApplicationUtilities.lightbox/lightbox');
        ## Jquery mask plugin JS ##
        echo $this->Html->script('GeneralApplicationUtilities.jquery-mask/jquery.mask');
        ## Bootstrap Date Picker plugin JS ##
        echo $this->Html->script('GeneralApplicationUtilities.datepicker/bootstrap-datepicker.min');
        ## CPF AND CNPJ VALIDATOR ##
        echo $this->Html->script('GeneralApplicationUtilities.jquery-cpf-cnpj-validator/jquery.cpfcnpj.min');
        
        echo $this->fetch('meta');
        echo $this->fetch('css');
        echo $this->fetch('script');

        echo ($this->elementExists('scripts')) ? $this->element('scripts', [], ['plugin' => false]) : '';
        echo ($this->elementExists('css')) ? $this->element('css', [], ['plugin' => false]) : '';

    echo $this->Html->tag('/head');




?>