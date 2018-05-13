<%
use Cake\Utility\Inflector;

$associations += ['BelongsTo' => [], 'HasOne' => [], 'HasMany' => [], 'BelongsToMany' => []];
$immediateAssociations = $associations['BelongsTo'];
$associationFields = collection($fields)
    ->map(function($field) use ($immediateAssociations) {
        foreach ($immediateAssociations as $alias => $details) {
            if ($field === $details['foreignKey']) {
                return [$field => $details];
            }
        }
    })
    ->filter()
    ->reduce(function($fields, $value) {
        return $fields + $value;
    }, []);

$groupedFields = collection($fields)
    ->filter(function($field) use ($schema) {
        return $schema->columnType($field) !== 'binary';
    })
    ->groupBy(function($field) use ($schema, $associationFields) {
        $type = $schema->columnType($field);
        if (isset($associationFields[$field])) {
            return 'string';
        }
        if (in_array($type, ['integer', 'float', 'decimal', 'biginteger'])) {
            return 'number';
        }
        if (in_array($type, ['date', 'time', 'datetime', 'timestamp'])) {
            return 'date';
        }
        return in_array($type, ['text', 'boolean']) ? $type : 'string';
    })
    ->toArray();

$groupedFields += ['number' => [], 'string' => [], 'boolean' => [], 'date' => [], 'text' => []];
$pk = "\$$singularVar->{$primaryKey[0]}";
%>


echo $this->Html->tag('div', NULL, ['class' => '<%= $pluralVar %> <%=$translation['index']%> large-9 medium-8 columns content']);

    echo $this->HtmlBootstrap->fieldSet('<%= $pluralVar %> '.ucfirst('<%=$translation['view']%>'));

        echo $this->Html->tag('h3', h($<%= $singularVar %>-><%= $displayField %>));

        #table inicio
        echo $this->Html->tag('table', NULL, ['class' => 'vertical-table']);

<%          if ($groupedFields['string']){
                foreach ($groupedFields['string'] as $field){
                    if (isset($associationFields[$field])){
                        $details = $associationFields[$field];
%>

                        echo $this->Html->tag('tr');
                            echo $this->Html->tag('th', __('<%= Inflector::humanize($details['property']) %>'), ['scope' => 'row']);
                            echo $this->Html->tag('/th');
                            echo $this->Html->tag('td', $<%= $singularVar %>->has('<%= $details['property'] %>') ? $this->Html->link($<%= $singularVar %>-><%= $details['property'] %>-><%= $details['displayField'] %>, ['controller' => '<%= $details['controller'] %>', 'action' => '<%=$translation['view']%>', $<%= $singularVar %>-><%= $details['property'] %>-><%= $details['primaryKey'][0] %>]) : '');
                            echo $this->Html->tag('/td');
                        echo $this->Html->tag('/tr');

<%                  }else{ %>

                        echo $this->Html->tag('tr');
                            echo $this->Html->tag('th', __('<%= Inflector::humanize($field) %>'), ['scope' => 'row']);
                            echo $this->Html->tag('/th');
                            echo $this->Html->tag('td', h($<%= $singularVar %>-><%= $field %>));
                            echo $this->Html->tag('/td');
                        echo $this->Html->tag('/tr');

<%                  }
                }
            } 
            if ($associations['HasOne']){
                foreach ($associations['HasOne'] as $alias => $details){
%>

                    echo $this->Html->tag('tr');
                        echo $this->Html->tag('th', __('<%= Inflector::humanize(Inflector::singularize(Inflector::underscore($alias))) %>'), ['scope' => 'row']);
                        echo $this->Html->tag('/th');
                        echo $this->Html->tag('td', $<%= $singularVar %>->has('<%= $details['property'] %>') ? $this->Html->link($<%= $singularVar %>-><%= $details['property'] %>-><%= $details['displayField'] %>, ['controller' => '<%= $details['controller'] %>', 'action' => '<%=$translation['view']%>', $<%= $singularVar %>-><%= $details['property'] %>-><%= $details['primaryKey'][0] %>]) : '');
                        echo $this->Html->tag('/td');
                    echo $this->Html->tag('/tr');

<%              }
            }
            if ($groupedFields['number']){
                foreach ($groupedFields['number'] as $field){ 
%>

                        echo $this->Html->tag('tr');
                            echo $this->Html->tag('th', __('<%= Inflector::humanize($field) %>'), ['scope' => 'row']);
                            echo $this->Html->tag('/th');
                            echo $this->Html->tag('td', $this->Number->format($<%= $singularVar %>-><%= $field %>));
                            echo $this->Html->tag('/td');
                        echo $this->Html->tag('/tr');

<%              }
            }
            if ($groupedFields['date']){
                foreach ($groupedFields['date'] as $field){
%>

                        echo $this->Html->tag('tr');
                            echo $this->Html->tag('th', <%= "<%= __('" . Inflector::humanize($field) . "') %>" %>, ['scope' => 'row']);
                            echo $this->Html->tag('/th');
                            echo $this->Html->tag('td', h($<%= $singularVar %>-><%= $field %>));
                            echo $this->Html->tag('/td');
                        echo $this->Html->tag('/tr');
                        
<%              } 
            }
            if ($groupedFields['boolean']){
                foreach ($groupedFields['boolean'] as $field){
%>

                        echo $this->Html->tag('tr');
                            echo $this->Html->tag('th', __('<%= Inflector::humanize($field) %>'), ['scope' => 'row']);
                            echo $this->Html->tag('/th');
                            echo $this->Html->tag('td', $<%= $singularVar %>-><%= $field %> ? __('Sim') : __('Não'); );
                            echo $this->Html->tag('/td');
                        echo $this->Html->tag('/tr');

<%              }
            } 
%>

        echo $this->Html->tag('/table');

<%      if ($groupedFields['text']){
            foreach ($groupedFields['text'] as $field){
%>

                echo $this->Html->tag('div', NULL, ['class' => 'row']);
                    echo $this->Html->tag('h4', __('<%= Inflector::humanize($field) %>'));
                    echo $this->Html->tag('/h4');
                    echo $this->Text->autoParagraph(h($<%= $singularVar %>-><%= $field %>));
                echo $this->Html->tag('/div');

<%          }
        }

        $relations = $associations['HasMany'] + $associations['BelongsToMany'];
        foreach ($relations as $alias => $details){
            $otherSingularVar = Inflector::variable($alias);
            $otherPluralHumanName = Inflector::humanize(Inflector::underscore($details['controller']));
%>

            echo $this->Html->tag('div', NULL, ['class' => 'related']);
                echo $this->Html->tag('h4', __('Related <%= $otherPluralHumanName %>'));
                echo $this->Html->tag('/h4');

                if(!empty($<%= $singularVar %>-><%= $details['property'] %>)){

<%                  foreach ($details['fields'] as $field)
                        $fields_array[$field] = ['scope' => 'col'];
                    $fields_array['actions'] = ['scope' => 'col'];
%>

                    echo $this->HtmlBootstrap->startTable(json_decode('<%=json_encode($fields_array)%>', true), ['class' => 'table-striped', 'cellpadding' => 0, 'cellspacing' => 0], 'lg');

                    foreach ($<%= $singularVar %>-><%= $details['property'] %> as $<%= $otherSingularVar %>){

                        echo $this->Html->tableCells([
<%                             
                            foreach ($details['fields'] as $field){ %>
                                h($<%= $otherSingularVar %>-><%= $field %>),
<%                          }
                            $otherPk = "\${$otherSingularVar}->{$details['primaryKey'][0]}";
%>                            
                            $this->Html->link(__(ucfirst('<%=$translation['view']%>')), ['controller' => '<%= $details['controller'] %>', 'action' => '<%=$translation['view']%>', <%= $otherPk %>])
                            .'/'.$this->Html->link(__(ucfirst('<%=$translation['edit']%>')), ['controller' => '<%= $details['controller'] %>', 'action' => '<%=$translation['edit']%>', <%= $otherPk %>])
                            .'/'.$this->Form->postLink(__(ucfirst('<%=$translation['delet']%>')), ['controller' => '<%= $details['controller'] %>', 'action' => '<%=$translation['delet']%>', <%= $otherPk %>], ['confirm' => __('<%=$translation['deletMsg']%> # {0}?', <%= $otherPk %>)]),
                        ]);
                    }
                    echo $this->HtmlBootstrap->endTable();
                }
            echo $this->Html->tag('/div');
<%      } %>

    echo $this->HtmlBootstrap->fieldSetEnd();

echo $this->Html->tag('/div');


