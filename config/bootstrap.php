<?php

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Core\Configure;

EventManager::instance()->on('Bake.beforeRender', function (Event $event) {
    $view = $event->getSubject();

    try{
        Configure::load('GeneralApplicationUtilities_config', 'default');

        $bake_language = Configure::read('bake_language');

        if($bake_language == NULL)
            $bake_language = 'en';

    }
    catch(\Exception $e){
        $bake_language = 'en';
    }

    try{
        Configure::load('GeneralApplicationUtilities.plugin_config', 'default');

        $view->viewVars['bake_language'] = $bake_language;

        $view->viewVars['translation'] = Configure::read('enToLanguageTranslation.'.$bake_language);

        $view->viewVars['listAjax'] = Configure::read('listByVarAjax.'.$bake_language);

        $view->viewVars['emptySelectValue'] = Configure::read('emptySelectValue.'.$bake_language);
    }
    catch(\Exception $e){

        $view->viewVars['actions'] = ['index', 'view', 'add', 'edit', 'delete', 'listar_by_association_ajax'];

        $view->viewVars['bake_language'] = 'en';

        $view->viewVars['listAjax'] = 'listBy_VARASSOC_Ajax';

        $view->viewVars['emptySelectValue'] = 'Select...';
    }

    

});

?>