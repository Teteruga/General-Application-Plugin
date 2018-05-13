<%
use Cake\Utility\Inflector;

$HtmlBootstrap = $this->loadHelper('GeneralApplicationUtilities.HtmlBootstrap');

$fields = collection($fields)
    ->filter(function($field) use ($schema) {
        return !in_array($schema->columnType($field), ['binary', 'text']);
    });

if (isset($modelObject) && $modelObject->behaviors()->has('Tree')) {
    $fields = $fields->reject(function ($field) {
        return $field === 'lft' || $field === 'rght';
    });
}

if (!empty($indexColumns)) {
    $fields = $fields->take($indexColumns);
}

foreach($fields as $field)
    $fields_array[$field] = ['scope' => 'col'];
    
$fields_array['action'] = ['scope' => 'col'];
%>

    echo $this->Html->tag('div', NULL, ['class' => '<%= $pluralVar %> <%=$translation['index']%> large-9 medium-8 columns content']);

        echo $this->HtmlBootstrap->fieldSet('<%= $pluralVar %> <%=$translation['index']%>');

            echo $this->HtmlBootstrap->startTable(json_decode('<%=json_encode($fields_array)%>', true), ['class' => 'table-striped', 'paginate' => 'true'], 'lg');

            foreach ($<%= $pluralVar %> as $<%= $singularVar %>){

                echo $this->Html->tableCells([
            <%        
                foreach ($fields as $field) {
                    $isKey = false;
                    
                    if (!empty($associations['BelongsTo'])) {
                        foreach ($associations['BelongsTo'] as $alias => $details) {
                            if ($field === $details['foreignKey']) {
                                $isKey = true;
            %>
                $<%= $singularVar %>->has('<%= $details['property'] %>') ? 
                    $this->Html->link($<%= $singularVar %>-><%= $details['property'] %>-><%= $details['displayField'] %>, ['controller' => '<%= $details['controller'] %>', 'action' => '<%=$translation['view']%>', $<%= $singularVar %>-><%= $details['property'] %>-><%= $details['primaryKey'][0] %>])
                    : '',                      
            <%
                                break;
                            }
                        }
                    }
                    if ($isKey !== true) {
                        if (!in_array($schema->columnType($field), ['integer', 'biginteger', 'decimal', 'float'])) {
            %>
                h($<%= $singularVar %>-><%= $field %>),
            <%
                        } else {
            %>
                $this->Number->format($<%= $singularVar %>-><%= $field %>),
            <%
                        }
                    }
                }
        
                $pk = '$' . $singularVar . '->' . $primaryKey[0];
            %>  
                $this->Html->link(__(ucfirst('<%=$translation['view']%>')), ['action' => '<%=$translation['view']%>', <%= $pk %>]) .'/'.$this->Html->link(__(ucfirst('<%=$translation['edit']%>')), ['action' => '<%=$translation['edit']%>', <%= $pk %>]).'/'.
                    $this->Form->postLink(__(ucfirst('<%=$translation['delet']%>')), ['action' => '<%=$translation['delet']%>', <%= $pk %>], ['confirm' => __('<%=$translation['deletMsg']%> # {0}?', <%= $pk %>)]),

                ]);
                                
            }

            echo $this->HtmlBootstrap->endTable();

            echo $this->HtmlBootstrap->paginatorLinks();

        echo $this->HtmlBootstrap->fieldSetEnd();

    echo $this->Html->tag('/div');


