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
use Cake\ORM\TableRegistry;

$fields = collection($fields)
    ->filter(function($field) use ($schema) {
        return $schema->columnType($field) !== 'binary';
    });

if (isset($modelObject) && $modelObject->hasBehavior('Tree')) {
    $fields = $fields->reject(function ($field) {
        return $field === 'lft' || $field === 'rght';
    });
}

$urlAssociation = [];
$fieldsAssociations = [];
$targetAssociation = [];
$date = [];

foreach($fields as $field){

    if( isset($keyFields[$field]) ){

        $tabela = TableRegistry::get($keyFields[$field]);
        $assoc = $tabela->associations()->getIterator();
        
        foreach($assoc as $association){

            if( ($association->type() === 'manyToOne') && (strtolower($association->name()) !== $keyFields[$field]) && (in_array( strtolower($association->name()), $keyFields)) ){

                $fieldsAssociations[strtolower($association->name())] = $keyFields[$field];

                if( !in_array('url'.$association->name(), $urlAssociation) ){
%>
    $url<%=$association->name()%> = $this->Url->build([
        "controller" => '<%= $keyFields[$field] %>',
        "action" => '<%= str_replace('_VARASSOC_', $association->name(), $listAjax) %>',
        ], ['fullBase' => true]);
<%       
                }

                $urlAssociation[strtolower($association->name())] = 'url'.$association->name();
                $targetAssociation[strtolower($association->name())] = str_replace('_', '-', $field);
            }
        }
    }
}

%>

    echo $this->Html->tag('div', NULL, ['class' => 'form content']);

        echo $this->Form->create($<%= $singularVar %>);

            echo $this->HtmlBootstrap->fieldSet('<%= Inflector::humanize($action) %> <%= $singularHumanName %>');

            <%

                foreach($fields as $field){

                    if(in_array($field, $primaryKey))
                        continue;
                    
                    if( isset($keyFields[$field]) ){
            
                        $fieldData = $schema->column($field);

                        if( !empty($fieldData['null']) ){
                        
                            if( isset( $targetAssociation[$keyFields[$field]] ) ){

                                if( in_array($keyFields[$field], $fieldsAssociations) ){
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => [], 'empty' => true, 'onchange' => "ajaxCombo(this, '$<%= $urlAssociation[$keyFields[$field]] %>', '<%= $targetAssociation[$keyFields[$field]] %>' )"]);
            <%
                                }else{
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => $<%= $keyFields[$field] %>, 'empty' => true, 'onchange' => "ajaxCombo(this, '$<%= $urlAssociation[$keyFields[$field]] %>', '<%= $targetAssociation[$keyFields[$field]] %>' )"]);    
            <%
                                }
                            }else{

                                if( in_array($keyFields[$field], $fieldsAssociations) ){
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => [], 'empty' => true]);
            <%
                                }else{
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => $<%= $keyFields[$field] %>, 'empty' => '<%= $emptySelectValue %>']);
            <%
                                }
                            }
                        }else{
                            if( isset( $targetAssociation[$keyFields[$field]] ) ){

                                if( in_array($keyFields[$field], $fieldsAssociations) ){
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => [], 'onchange' => "ajaxCombo(this, '$<%= $urlAssociation[$keyFields[$field]] %>', '<%= $targetAssociation[$keyFields[$field]] %>' )"]);
            <%
                                }else{
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => $<%= $keyFields[$field] %>, 'empty' => '<%= $emptySelectValue %>' ,'onchange' => "ajaxCombo(this, '$<%= $urlAssociation[$keyFields[$field]] %>', '<%= $targetAssociation[$keyFields[$field]] %>' )"]);    
            <%
                                }
                            }else{

                                if( in_array($keyFields[$field], $fieldsAssociations) ){
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => []]);
            <%
                                }else{
            %>
                                    echo $this->Form->control('<%= $field %>', ['options' => $<%= $keyFields[$field] %>, 'empty' => '<%= $emptySelectValue %>' ]);
            <%
                                }
                            }
                        }continue;
                    }

                    if( !in_array($field, ['created', 'modified', 'updated']) ){

                        $fieldData = $schema->column($field);

                        if( in_array($fieldData['type'], ['datetime', 'time']) && ( !empty($fieldData['null']) ) ){
            %>        
                            echo $this->Form->control('<%= $field %>', ['empty' => true]);
            <%
                        }else if( $fieldData['type'] === 'date'){
                            $date[] = $field;
            %>
                            echo $this->Html->div('input-daterange',
							                $this->Form->input('<%= $field %>', 
                                                ['onkeydown' => "return false;", 'label' => false, 'placeholder' => '<%= $field %>', 'class' => 'date-picker', 'type' => 'text', 'templates' => 
                                                ['input' => '<input class="input-sm form-control datapicker" type="{{type}}" name="{{name}}"{{attrs}}/>', 'inputContainer' => '{{content}}'],
                                            ])
                                        );
            <%
                        }else{
            %>
                            echo $this->Form->control('<%= $field %>');
            <%
                        }
                    }

                }
                if( !empty($associations['BelongsToMany']) ){
                    foreach ($associations['BelongsToMany'] as $assocName => $assocData){
            %>            
                        echo $this->Form->control('<%= $assocData['property'] %>._ids', ['options' => $<%= $assocData['variable'] %>]);
            <%
                    }
                }

            %>
            
            echo $this->HtmlBootstrap->fieldSetEnd();

            echo $this->Form->button('submit');

        echo $this->Form->end();

    echo $this->Html->tag('/div');

<%
    foreach($date as $dateField){
%>

    echo $this->Html->scriptBlock('     
        $("#<%=str_replace('_', '-', $dateField)%>").datepicker({
        format: "dd/mm/yyyy",
        weekStart: 0,
        clearBtn: true,
        todayBtn: "linked",
        language: "<%=$bake_language%>",
        daysOfWeekHighlighted: "0,6",
        autoclose: true,
        todayHighlight: true
    }); ', ['defer' => true]);
<%
    }
%>



