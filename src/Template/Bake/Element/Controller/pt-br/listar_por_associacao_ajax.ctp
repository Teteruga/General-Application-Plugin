<%
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Utility\Inflector;

$allAssociations = array_merge(
    $this->Bake->aliasExtractor($modelObj, 'BelongsTo'),
    $this->Bake->aliasExtractor($modelObj, 'BelongsToMany'),
    $this->Bake->aliasExtractor($modelObj, 'HasOne'),
    $this->Bake->aliasExtractor($modelObj, 'HasMany')
);

$belongsTo = $this->Bake->aliasExtractor($modelObj, 'BelongsTo');

foreach($belongsTo as $association){

    %>
        /**
        * ListarPor<%= $association %>Ajax method
        *
        * @param string|null $id <%= $association %> id.
        * @return \Cake\Network\Response|null
        * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
        */
        public function listarPor<%= $association %>Ajax($id = null)
        {
            $json = $this-><%= $currentModelName %>->find('list')
                ->where(["<%= $association %>.id" => $id])
                ->contain([<%= $this->Bake->stringifyList($allAssociations, ['indent' => false]) %>]);

            $this->viewBuilder()->className('Json');
            
            $json = $json->toArray();
            $json[0] = '<%= $emptySelectValue %>';

            $this->set(compact('json')); 
            $this->set('_serialize', ['json']);
        }
    <%
}

%>