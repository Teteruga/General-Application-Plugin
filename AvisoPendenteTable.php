<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

/**
 * AvisoPendente Model
 *
 * @property \App\Model\Table\PessoaTable|\Cake\ORM\Association\BelongsTo $Pessoa
 * @property \App\Model\Table\ProcessoJudicialTable|\Cake\ORM\Association\BelongsTo $ProcessoJudicial
 * @property \App\Model\Table\TipoComunicacaoTable|\Cake\ORM\Association\BelongsTo $TipoComunicacao
 * @property \App\Model\Table\ComunicacaoTable|\Cake\ORM\Association\HasMany $Comunicacao
 *
 * @method \App\Model\Entity\AvisoPendente get($primaryKey, $options = [])
 * @method \App\Model\Entity\AvisoPendente newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\AvisoPendente[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AvisoPendente|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AvisoPendente patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AvisoPendente[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\AvisoPendente findOrCreate($search, callable $callback = null, $options = [])
 */
class AvisoPendenteTable extends Table
{

    private $filters = [
        'default' => [
            'is_null' => [],
            'is_not_null' => [],
            '%like%'=> [],
            '%like' => [],
            'like%' => [],
            'higher' => [],
            'higher_equal' => [],
            'lower' => [],
            'lower_equal' => [],
            'unset' => [ 'page', 'sort', 'direction', 'sem_numero_pa', 'manter_dia'],
        ],
        'index' => [
            'is_null' => [],
            'is_not_null' => [],
            '%like%'=> [ 'ProcessoJudicial' => ['numero'] ],
            '%like' => [],
            'like%' => [],
            'higher' => [],
            'higher_equal' => [],
            'lower' => [],
            'lower_equal' => [],
            'unset' => [ 'page', 'sort', 'direction', 'sem_numero_pa', 'manter_dia'],
        ],
    ];

    private $sqlStatements = [
        'is_null' => " VAL IS NULL",
        'is_not_null' => " VAL IS NOT NULL",
        '%like%' => " LIKE || %VAL% ",
        '%like' => " LIKE || %VAL",
        'like%' => " LIKE || VAL%",
        'higher' => " > || VAL",
        'higher_equal' => " >= || VAL",
        'lower' => " < || VAL",
        'lower_equal' => " <= || VAL",
        'equal' => " = || VAL",
        'not_equal' => " != || VAL",
    ];

    private $filter = [];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) {
        parent::initialize($config);

        $this->setTable('aviso_pendente');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Pessoa', [
            'foreignKey' => 'destinatario_id'
        ]);
        $this->belongsTo('ProcessoJudicial', [
            'foreignKey' => 'processo_id'
        ]);
        $this->belongsTo('TipoComunicacao', [
            'foreignKey' => 'tipo_comunicacao_id'
        ]);
        $this->hasMany('Comunicacao', [
            'foreignKey' => 'aviso_pendente_id'
        ])->setProperty('comunicacoes');

        $this->hasOne('InstantaneoAvisoPendente', [
            'foreignKey' => 'aviso_pendente_id'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->allowEmpty('id_aviso');

        $validator
            ->allowEmpty('fonte');

        $validator
            ->dateTime('data_disponibilizacao')
            ->allowEmpty('data_disponibilizacao');

        $validator
            ->boolean('sucesso')
            ->allowEmpty('sucesso');

        $validator
            ->allowEmpty('mensagem');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->existsIn(['destinatario_id'], 'Pessoa'));
        $rules->add($rules->existsIn(['processo_id'], 'ProcessoJudicial'));
        $rules->add($rules->existsIn(['tipo_comunicacao_id'], 'TipoComunicacao'));

        return $rules;
    }

    /**
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    public static function defaultConnectionName() {
        return 'mni';
    }

    /**
     *  Monta a query padrão que é usada para o cruzamento de dados com a tabela de Processo do sicaj
     *
     *  @return Query
     */
    private function queryPadraoAvisos_Sicaj(Query $query){

        $query
            ->where(['AvisoPendente.sucesso' => true])
                ->leftJoin(['ProcessoJudicial' => 'processo_judicial'], [
                    'AvisoPendente.processo_id = ProcessoJudicial.id'
                ])
                ->leftJoin(['Processo' => 'sicaj.dbo.Processo'], [
                    'OR' => [
                        ['ProcessoJudicial.numero = Processo.Num_Proc_Jud_Novo'],
                        ['ProcessoJudicial.numero = Processo.NUM_PROC_JUD'],
                        ['ProcessoJudicial.numero = Processo.NO_ORIGINAL'],
                        ['ProcessoJudicial.numero = Processo.NUM_PROCESSO_2']
                    ],
                ]);

        return $query;
    }

    /**
     *
     */
    private function queryPadraoData(Query $query, $dia, $mes, $ano){
        $query
            ->where(['YEAR(data_disponibilizacao)' => $ano])
            ->where(['MONTH(data_disponibilizacao)' => $mes])
            ->where(['DAY(data_disponibilizacao)' => $dia]);

        return $query;
    }

    /**
     *  Monta a query padrão que é usada para o cruzamento de dados com a tabela Movimento do sicaj
     *
     *  @return Query
     */
    private function queryPadraoMovimento(Query $query){

        $query                    
            ->leftJoin(['Movimentacao' => 'sicaj.dbo.Movimentacao'], [
                'Processo.num_processo = Movimentacao.num_processo',
                'Movimentacao.ultima_mov' => 1
            ]);

        $query = $this->queryPadraoUnidade($query);

        return $query;
    }

    /*
     *
     */
    private function queryPadraoRecurso(Query $query){
        $query
            ->leftJoin(['Recurso' => 'sicaj.dbo.recurso' ],
                ['ProcessoJudicial.numero = Recurso.cnj'
            ]);

        return $query;
    }

    /**
     *
     */
    private function queryPadraoUnidade(Query $query){
        $query
            ->leftJoin(['Unidade' => 'sicaj.dbo.Unidade'], [
                'Movimentacao.COD_UNIDADE = Unidade.COD_UNIDADE'
            ]);

        return $query;
    }

    /**
     *
     */
    private function containPadraoVisualizar(Query $query){
        $query
            ->contain(['Pessoa', 'InstantaneoAvisoPendente', 'TipoComunicacao', 'ProcessoJudicial' => ['AssuntoProcessoJudicial' => ['AssuntoLocal']]]);

        return $query;
    }

    /**
     *
     */
    private function containPadraoDownload(Query $query){
        $query
            ->contain(['InstantaneoAvisoPendente', 'Comunicacao' => ['DocumentoComunicacao'] ]);

        return $query;
    }

    /**
     *
     */
    private function selectPadraoVisualizar(Query $query){
        $query
            ->select(['AvisoPendente.id', 'AvisoPendente.id_aviso', 'AvisoPendente.fonte', 'AvisoPendente.sucesso',
                        'AvisoPendente.data_disponibilizacao', 'AvisoPendente.data_consultado', 'AvisoPendente.mensagem'])
            ->select(TableRegistry::get('ProcessoJudicial'))
            ->select('Pessoa.nome')
            ->select('TipoComunicacao.nome')
            ->select('InstantaneoAvisoPendente.numero_processo');

        return $query;
    }

    /**
     *
     *
     */
    private function selectPadraoAjaxVisualizar(Query $query){

        $query
            ->select(['AvisoPendente.id', 'AvisoPendente.id_aviso', 'AvisoPendente.fonte', 'AvisoPendente.sucesso', 'AvisoPendente.mensagem'])
            ->select(['numero_processo_judicial' => 'ProcessoJudicial.numero'])
            ->select(TableRegistry::get('ProcessoJudicial'))
            ->select(['destinatario' => 'Pessoa.nome'])
            ->select(['tipo_comunicacao' => 'TipoComunicacao.nome'])
            ->select(['numero_pa' => 'InstantaneoAvisoPendente.numero_processo']);

        return $query;
    }

    /**
     *
     */
    private function wherePadraoVisualizar(Query $query, $flag, $por, $valor = NULL){

        if($por == 'unidade'){
            $valor = $flag;
            $nome = 'codigo_unidade';
        }
        else
            $nome = 'nome_servidor';

        if($flag)
            $query
                ->where(['InstantaneoAvisoPendente.'.$nome => $valor ]);
        else
            $query
                ->where(['InstantaneoAvisoPendente.'.$nome.' IS NULL']);

        return $query;
    }

    /**
     *
     */
    private function queryPadraoAcervo(Query $query){
        $query
            ->leftJoin(['Acervo' => 'sicaj.dbo.distrib_vincjud' ],[
                'AND' => [
                    ['Processo.NUM_PROCESSO = Acervo.num_processo'],
                    'OR' => [
                        ['Acervo.ativo = 1'],
                        ['Acervo.COD_SERVIDOR IS NULL'],
                    ]
                ],
            ]);

        $query = $this->queryPadraoServidor($query, 'Acervo');

        return $query;
    }

    /**
     *
     */
    private function queryPadraoServidor(Query $query, $tabela){
        $query
            ->leftJoin(['Servidor' => 'sicaj.dbo.Servidor'],
                [$tabela.'.COD_SERVIDOR = Servidor.COD_SERVIDOR'
            ]);

        return $query;
    }

    /**
     *
     */
    private function queryPadraoDistribuicao(Query $query){

        $query
            ->leftJoin(['Distribuicao' => 'sicaj.dbo.Distribuicao'],[
                'OR' => [
                    ['Processo.NUM_PROCESSO = Distribuicao.NUM_PROCESSO'],
                    ['Recurso.num_processo = Distribuicao.NUM_PROCESSO']
                ]
            ])
            ->where(['Distribuicao.ULTIMA_DIST' => 1]);

        return $query;
    }

    /**
     *
     *   
     */
    private function instantaneoProcesso(Query $query){
        $query        
        ->select(['aviso_pendente_id' => 'AvisoPendente.id'])
        ->select(['numero_processo_judicial' => 'ProcessoJudicial.numero'])
        ->select(['numero_processo_recurso' => 'Recurso.num_processo'])
        ->select(['numero_processo_processo' => 'Processo.NUM_PROCESSO'])
        ->select(['coluna_Num_Proc_Jud_Novo' => 'Processo.Num_Proc_Jud_Novo'])
        ->select(['coluna_NUM_PROC_JUD' => 'Processo.NUM_PROC_JUD'])
        ->select(['coluna_NO_ORIGINAL' => 'Processo.NO_ORIGINAL'])
        ->select(['coluna_NUM_PROCESSO_2' => 'Processo.NUM_PROCESSO_2'])
        ->select(['codigo_unidade' => 'Unidade.COD_UNIDADE'])
        ->select(['nome_unidade' => 'Unidade.UNIDADE'])
        ->select(['codigo_servidor' => 'Servidor.COD_SERVIDOR'])
        ->select(['nome_servidor' => 'Servidor.servidor'])
                ->hydrate(false);

        return $query;
    }

    /**
     *   Encontra os avisos e documentosComunicacao de acordo com a opção 'por'
     *       
     * @param $options array de opções
     *
     *  $options['por'] => individual
     *          Encontra todos os avisos de acordo com a data passada
     *
     *  $options['por'] => downloadDoDia
     *          Encontra todos os avisos do dia passado para fazer o download dos documentos
     *
     *  $options['por'] => unidadeContar
     *           Encontra os avisos e sua quantidade de acordo com a data escolhida agrupando por unidade
     *     
     *  $options['por'] => unidadeVisualizar
     *           Encontra os avisos devem ser enviados para serem visualizados de acordo com o unidade escolhido e data
     *
     *  $options['por'] => unidadeDownload
     *           Encontra os documentosComunicacao devem ser enviados para serem baixados de acordo com a unidade escolhido e data
     *
     *  $options['por'] => acervoContar
     *            Encontra os avisos e sua quantidade de acordo com a data escolhida agrupando por acervo
     *
     *  $options['por'] => acervoVisualizar
     *           Encontra os avisos devem ser enviados para serem visualizados de acordo com o acervo escolhido e data
     *
     *  $options['por'] => acervoDownload
     *           Encontra os documentosComunicacao devem ser enviados para serem baixados de acordo com o acervo escolhido e data
     *
     *  $options['por'] => DocumentoSicaj
     *           Encontra os documentosComunicacao que devem ser incluidos na base de dados do sicaj de acordo com a data escolhida
     * 
     *  @options['por'] => preencherInstantaneo
     *          Encontra todos os avisos, os numeros dos pa's, os nomes dos servidores, os codigo dos servidores, as unidades, os 
     *      codigos das unidades para salvar na tabela InstantaneoAvisoPendente (tabela espelho para aumentar o desempenho das query)
     *  
     *
     * @return Query
     */
    public function findAvisosPendentesPor(Query $query, array $options){

        $find = TableRegistry::get('InstantaneoAvisoPendente')->find();

        switch ($options['por']) {
            case 'individual':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->selectPadraoVisualizar($query);
                $query = $this->containPadraoVisualizar($query);
                $query
                    ->order(['InstantaneoAvisoPendente.numero_processo' => 'DESC']);

                break;
            case 'individualId':

                $query = $this->selectPadraoAjaxVisualizar($query);
                $query = $this->containPadraoVisualizar($query);
                $query->where(['AvisoPendente.id' => $options['id'] ]);

                break;
            case 'downloadDoDia':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->containPadraoDownload($query);
                $query
                    ->order(['InstantaneoAvisoPendente.numero_processo' => 'DESC']);

                break;
            case 'downloadDoDiaEtiqueta':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->containPadraoDownload($query);
                $query
                    ->where(['InstantaneoAvisoPendente.numero_processo IS NOT NULL'])
                    ->order(['AvisoPendente.id']);

            break;
            case 'unidadeContar':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query
                    ->select('InstantaneoAvisoPendente.nome_unidade')
                    ->select('InstantaneoAvisoPendente.codigo_unidade')
                    ->select(['total' => $find->func()->count('*')])
                        ->group(['InstantaneoAvisoPendente.nome_unidade', 'InstantaneoAvisoPendente.codigo_unidade'])
                        ->contain(['InstantaneoAvisoPendente']);

                break;
            case 'unidadeVisualizar':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->selectPadraoVisualizar($query);
                $query = $this->wherePadraoVisualizar($query, $options['valor'], 'unidade');
                $query = $this->containPadraoVisualizar($query);

                break;
            case 'unidadeDownload':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->wherePadraoVisualizar($query, $options['valor'], 'unidade');
                $query = $this->containPadraoDownload($query);
                $query
                    ->order(['InstantaneoAvisoPendente.numero_processo' => 'DESC']);;

                break;
            case 'acervoContar':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query
                    ->select(['servidor' => 'InstantaneoAvisoPendente.nome_servidor'])
                    ->select(['total' => $find->func()->count('*')])
                        ->group(['InstantaneoAvisoPendente.nome_servidor'])
                        ->contain(['InstantaneoAvisoPendente']);

                break;
            case 'acervoVisualizar':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->selectPadraoVisualizar($query);
                $query = $this->wherePadraoVisualizar($query, $options['valor'], 'acervo', $options['nome']);
                $query = $this->containPadraoVisualizar($query);
                $query
                    ->select(['servidor' => 'InstantaneoAvisoPendente.nome_servidor']);

                break;
            case 'acervoDownload':

                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->wherePadraoVisualizar($query, $options['valor'], 'acervo', $options['nome']);
                $query = $this->containPadraoDownload($query);
                $query 
                    ->order(['InstantaneoAvisoPendente.numero_processo' => 'DESC']);

                break;
            case 'DocumentoSicaj':

                $dateTime = date_create($options['ano'].$options['mes'].$options['dia']);
                $query = $this->queryPadraoAvisos_Sicaj($query);
                $query
                    ->select(['aviso_id' => 'AvisoPendente.id'])
                    ->select('documento_comunicacao.binario')
                    ->select('documento_comunicacao.mimetype')
                    ->select(['descricao' => 'documento_comunicacao.descricao'])
                    ->select(['tipoDocumento_id' => 'documento_comunicacao.tipo_documento_id'])
                    ->select(['num_processo' => 'Processo.num_processo'])
                        ->where(['AvisoPendente.id IN' => $options['id'] ])
                        ->where(['Processo.num_processo IS NOT NULL'])
                            ->leftJoin(['comunicacao' => 'Comunicacao'], [
                                'AvisoPendente.id = Comunicacao.aviso_pendente_id'
                            ])
                            ->leftJoin(['documento_comunicacao'], [
                                'Comunicacao.id = documento_comunicacao.comunicacao_id'
                            ])
                    ->hydrate(false);
                break;
            //PROBLEMA COM ACERVOS ARQUIVADOS?
            case 'preencherInstantaneoProcesso':

                $query = $this->queryPadraoAvisos_Sicaj($query);
                $query = $this->queryPadraoMovimento($query);
                $query = $this->queryPadraoRecurso($query);
                $query = $this->queryPadraoAcervo($query);
                $query = $this->instantaneoProcesso($query);

                break;
            //Possivelmente jogar na table do processo?
            case 'preencherInstantaneoDespacho':

                $processoTable = TableRegistry::get('Processo');

                $query = $processoTable->find();
                $query
                    ->leftJoin(['Distribuicao' => 'sicaj.dbo.Distribuicao'],
                        ['Distribuicao.NUM_PROCESSO = Processo.NUM_PROCESSO',
                        'Distribuicao.ULTIMA_DIST' => 1
                    ])               
                    ->leftJoin(['Movimentacao' => 'sicaj.dbo.Movimentacao'], [
                        'Processo.num_processo = Movimentacao.num_processo',
                        'Movimentacao.ultima_mov' => 1
                    ])
                    ->select(['codigo_autor' => 'Autor.COD_INTERESSADO'])
                    ->select(['nome_autor' => 'Autor.INTERESSADO'])
                    ->select(['distribuicao_data' => 'Distribuicao.DATA_DISTRIBUICAO'])
                    ->select(['distribuicao_hora' => 'Distribuicao.HORA_DISTRIBUICAO'])
                    ->select(['despacho_distribuicao' => 'Distribuicao.DESPACHO'])
                    ->select(['ultima_distribuicao' => 'Servidor.servidor'])
                    ->select(['movimentacao_data' => 'Movimentacao.DATA_MOVIMENTACAO'])
                    ->select(['movimentacao_hora' => 'Movimentacao.HORA_MOVIMENTACAO'])
                    ->select(['despacho_movimentacao' => 'Movimentacao.DESPACHO'])
                        ->where(['Processo.NUM_PROCESSO' => $options['numero_processo']])
                            ->contain(['Autor']);


                    $query = $this->queryPadraoServidor($query, 'Distribuicao');
                    $query->hydrate(false);

                break;
            case 'atualizarInstantaneo':

                $query = $this->queryPadraoAvisos_Sicaj($query);
                $query = $this->queryPadraoMovimento($query);
                $query = $this->queryPadraoRecurso($query);
                $query = $this->queryPadraoAcervo($query);
                $query = $this->instantaneoProcesso($query);
                $query->where(['AvisoPendente.id' => $options['id'] ])->first();

            default:
                $query = [];
                break;
        }

        if( isset($options['busca']) ){

            if( isset($options['filtro']) ){
                $this->filter = $this->filters[ $options['filtro'] ];

                if( isset($options['filtroBusca']) )
                    $this->createFilter($options['filtroBusca']);

            }
            else
                $this->filter = $this->filters['default'];

            $this->queryBusca($query, $options['busca']);
        }

        $this->logQuery($query, $options);

        return $query;
    }

    /**
     * 
     * 
     */
    public function createFilter($options){

        foreach($this->filter as $key => $filter){

            if( isset($options[$key]) ){

                if( is_array($options[$key]) && empty($this->filter[$key]) )
                    $this->filter[$key] = array_merge($this->filter[$key], $options[$key]);
                else if(is_array($options[$key]) && !empty($this->filter[$key])){

                    foreach($filter as $subkey => $subfilter){

                        if(isset($options[$key][$subkey]))
                            $this->filter[$key][$subkey][] = $options[$key][$subkey];
                    }
                }
                else{
                    $this->filter[$key][] = $options[$key];

                }
            }
        }
    }


    /**
     * 
     * 
     */
    // public function queryBusca(Query $query, Array &$busca){

    //     if( isset($busca['page']) )
    //         unset($busca['page']);

    //     foreach($busca as $key => $option){

    //         if($key == 'sort')
    //             break;

    //         if(is_array($option) && !empty($option)){
    //             foreach($option as $subkey => $suboption){
    //                 if($suboption === 'IS NOT NULL' || $suboption === 'IS NULL')
    //                     $query->where([$key.'.'.$subkey.' '.$suboption]);                    
    //                 else if($suboption != '')
    //                     $query->where([$key.'.'.$subkey => $suboption]);
                    
    //             }
    //         }
    //         else if( !is_array($option) && $option != '')
    //             $query->where([$key => $option]);
    //     }        

    //     return $query;
    // }

    public function queryBusca(Query $query, Array &$busca){

        $this->cleanFilterBusca($busca);

        foreach($busca as $key => $option){

            if(is_array($option) && !empty($option)){

                foreach($option as $subkey => $suboption){
                    $statement = $this->getWhereStatement($key, $option, $subkey, $suboption);

                    if($statement !== NULL)
                        $statements[] = $statement; 
                } 
            }
            else if( !is_array($option) && $option != ''){
                $statement = $this->getWhereStatement($key, $option);
            
                if($statement !== NULL)
                    $statements[] = $statement; 
            }
        }

        if(!empty($statements))
            $query = $this->buildQueryBusca($query, $statements);

        return $query;
    }

    /**
     * 
     * 
     */
    public function buildQueryBusca(Query $query, Array $statement){

        foreach($statement as $where)
            $query->where($where);

        return $query;
    }

    /**
     * 
     * 
     */
    public function cleanFilterBusca(Array &$busca){

        foreach($this->filter['unset'] as $unset){

            if( is_array($unset)){

                foreach($unset as $subunset)
                    unset($busca[$unset][$subunset]);
            }
            else
                unset($busca[$unset]);
        }

    }

    /**
     * 
     * 
     */
    public function getWhereStatement($key, $option, $subkey = NULL, $suboption = NULL){

        $no_option = false;

        if($subkey != NULL && $suboption != NULL){
            $val = $suboption;
            $sql_key = $key.'.'.$subkey;
        }
        else if($subkey == NULL || $suboption == NULL){
            $val = $option;
            $sql_key = $key;
        }
        else
            $val = NULL;

        if( ( !isset($this->filter['is_null'][$key]) && !isset($this->filter['is_not_null'][$key]) ) && 
                                    ($subkey != NULL && $suboption == NULL) || ($key != NULL && $option == NULL)  )
            $no_option = true;


        if(!$no_option){
            debug($key);
            debug($subkey);exit();
            if( isset($subkey, $this->filter['is_null'][$key]) ){
                if(in_array($subkey, $this->filter['is_null'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'is_null');
            }
            else if( isset($subkey, $this->filter['is_not_null'][$key]) ){
                if(in_array($subkey, $this->filter['is_not_null'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'is_not_null');
            }
            else if( isset($subkey, $this->filter['like%'][$key]) ){   
                if(in_array($subkey, $this->filter['like%'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'like%');
            }
            else if( isset($subkey, $this->filter['%like'][$key]) ){
                if(in_array($subkey, $this->filter['%like'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, '%like'); 
            }
            else if( isset($subkey, $this->filter['%like%'][$key]) ){
                if(in_array($subkey, $this->filter['%like%'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, '%like%');
            }
            else if( isset($subkey, $this->filter['higher'][$key]) ){
                if(in_array($subkey, $this->filter['higher'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'higher');
            }
            else if( isset($subkey, $this->filter['higher_equal'][$key]) ){
                if(in_array($subkey, $this->filter['higher_equal'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'higher_equal');
            }
            else if( isset($subkey, $this->filter['lower'][$key]) ){
                if(in_array($subkey, $this->filter['lower'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'lower');
            }
            else if( isset($subkey, $this->filter['lower_equal'][$key]) ){
                if(in_array($subkey, $this->filter['lower_equal'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'lower_equal');
            }
            else if( isset($subkey, $this->filter['not_equal'][$key]) ){
                if(in_array($subkey, $this->filter['not_equal'][$key]) )
                    $statement = $this->createStatement($sql_key, $val, 'not_equal');
            }
            else if($val != NULL)
                $statement = $this->createStatement($sql_key, $val, 'equal');
        }

        if(!isset($statement))
            $statement = NULL;

        return $statement;
    }

    /**
     * 
     * 
     */
    public function createStatement($sql_key, $val, $sql_statement){

        $where_statement = $sql_key.str_replace('VAL', $val, $this->sqlStatements[$sql_statement]);

        $where_statement = explode("||", $where_statement, 2);

        $arr_left = trim($where_statement[0]);

        if( isset($where_statement[1]) ){
            $arr_right = trim($where_statement[1]);
            $where_statement = [$arr_left => $arr_right];
        }
        else
            $where_statement = $arr_left;

        return $where_statement;
    }

    /**
     * 
     * 
     */
    public function logQuery(Query &$query, Array &$options){

        $query_name = $options['por'];
        unset($options['por']);

        if( isset($options['filtroBusca']) )
            unset($options['filtroBusca']);

        if( isset($options['busca']) ){

            $busca = '';

            foreach($options['busca'] as $key => $option){

                if(is_array($option) && !empty($option)){
                    foreach($option as $subkey => $suboption)
                        if($suboption != '')
                            $busca .= $key.'.'.$subkey.' : '. $suboption .', ';
                }
                else if( !is_array($option) && $option != '')
                    $busca .= $key.' : '.$option;
            }

            if($busca == '')
                $busca = 'NULL';

            unset($options['busca']);
        }
        else
            $busca = NULL;

        if( isset($options['valor']) ){
            $valor = $options['valor'];
            unset($options['valor']);
        }
        else
            $valor = 'NULL';

        if( isset($options['nome']) ){
            $nome = $options['nome'];
            unset($options['nome']);
        }
        else
            $nome = 'NULL';

        if( isset($options['id']) ){
            $id = $options['id'];
            unset($options['id']);
        }
        else
            $id = 'NULL';

        $data = implode('/', $options);

        ($data == '') ? $data = 'NULL' : NULL;  

        try{

            (!is_array($query)) ? $query->all() : NULL;
            
            Log::info("Query ".$query_name." realizada com sucesso.
                Dados: data: ".$data.",  valor: ".$valor.",  nome: ".$nome.",  id: ".$id."
                Busca: ".$busca
                , ['scope' => 'avisosPendentes_query']);
        }
        catch(Exception $e){
            Log::info("Ocorreu algum problema com a Query ".$query_name."
            Dados: data: ".$data.",  valor: ".$valor.",  nome: ".$nome.",  id: ".$id."
            Busca: ".$busca."
            Erro: ".$e->getMessage(), ['scope' => 'avisosPendentes_query']);
        }

    }

    ## OLD STUFF ##

    /**
     *  Monta a query padrão que é usada para o cruzamento de dados com a tabela Movimento do sicaj quando se deseja baixar os binarios
     *
     *  @param $Valor Codigo da unidade
     *
     *  @return Query
     */
    private function queryPadraoMovimentoDownload_OLD(Query $query, $valor) {

        $query = $this->queryPadraoMovimento($query);

        if($valor)
            $query
            ->select(['Processo.num_processo', 'Unidade.UNIDADE'])
            ->select($this)
                ->where(['Unidade.COD_UNIDADE' => $valor ]);
        else
            $query
                ->select(['Processo.num_processo', 'Unidade.UNIDADE'])
                ->select($this)
                    ->where(['Unidade.COD_UNIDADE IS NULL']);


        $query->contain(['Comunicacao' => 'DocumentoComunicacao']);

        return $query;
    }

    /**
     *
     *  Monta a query padrão que é usada para o cruzamento de dados com a tabela Acervo do sicaj
     *
     *  @return Query
     */
    private function queryPadraoAcervo_OLD(Query $query){

        $query             
            ->leftJoin(['Acervo' => 'sicaj.dbo.distrib_vincjud' ],
                ['Processo.NUM_PROCESSO = Acervo.num_processo'
            ])
            ->leftJoin(['Servidor' => 'sicaj.dbo.Servidor'],
                ['Acervo.cod_servidor = Servidor.COD_SERVIDOR'
            ]);

        //debug($query->toArray());exit();

        return $query;
    }

    /**
     *
     *  Monta a query padrão que é usada para o cruzamento de dados com a tabela Acervo do sicaj quando se deseja baixar os binarios
     *
     * @param $valor se o Acervo esta ativo ou inativo
     * 
     * @param $nome nome do Servidor responsavel pelo acervo
     *
     * @return Query
     */
    private function queryPadraoAcervoDownload_OLD(Query $query, $valor, $nome){

        $query = $this->queryPadraoAcervo($query);
        debug($valor);exit();
        if($valor)
            $query
            ->select(['servidor' => 'Servidor.servidor'])
            ->select($this)
                ->where(['Acervo.ativo' => 1])
                ->where(['Servidor.servidor' => $nome ]);
        else if($valor == -1)
            $query 
            ->select(['servidor' => 'Servidor.servidor'])
            ->select($this)
                ->where(['Acervo.ativo' => 1])
                ->where(function ($exp) {
                    return $exp->or_([
                        'Acervo.ativo' => 0,
                        'Servidor IS NULL'
                    ]);
                });
                // ->where(['Servidor.servidor IN' => $nome ]);            
        else
            $query
            ->select(['servidor' => 'Servidor.servidor'])
            ->select($this)
                // ->where(['Servidor IS NULL'])
                ->andWhere(function ($exp) {
                    return $exp->or_([
                        'Acervo.ativo' => 0,
                        'Servidor IS NULL'
                    ]);
                });

        $query->contain(['Comunicacao' => 'DocumentoComunicacao']);

        return $query;
    }

    /**
     *   Encontra os avisos e documentosComunicacao de acordo com a opção 'por'
     *       
     * @param $options array de opções
     *
     *  $options['por'] => acervoDownload
     *           Encontra os documentosComunicacao devem ser enviados para serem baixados de acordo com o acervo escolhido e data
     *
     *  $options['por'] => acervoVisualizar
     *           Encontra os avisos devem ser enviados para serem visualizados de acordo com o acervo escolhido e data
     *
     *  $options['por'] => acervoContar
     *            Encontra os avisos e sua quantidade de acordo com a data escolhida agrupando por acervo
     *
     *  $options['por'] => unidadeDownload
     *           Encontra os documentosComunicacao devem ser enviados para serem baixados de acordo com a unidade escolhido e data
     *
     *  $options['por'] => unidadeVisualizar
     *           Encontra os avisos devem ser enviados para serem visualizados de acordo com o unidade escolhido e data
     *
     *  $options['por'] => unidadeContar
     *           Encontra os avisos e sua quantidade de acordo com a data escolhida agrupando por unidade
     *
     *  $options['por'] => DocumentoSicaj
     *           Encontra os documentosComunicacao que devem ser incluidos na base de dados do sicaj de acordo com a data escolhida (e id)
     *
     * @return Query
     */
    public function findAvisosPendentesPor_OLD(Query $query, array $options){

        $query = $this->queryPadraoAvisos_Sicaj($query);

        $find = $this->find();

        switch ($options['por']) {
            case 'acervoDownload':
                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->queryPadraoAcervoDownload($query, $options['valor'], $options['nome']);
                break;

            case 'acervoVisualizar':
                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->queryPadraoAcervoDownload($query, $options['valor'], $options['nome']);
                $query
                ->select('ProcessoJudicial.numero')
                ->select('Pessoa.nome')
                ->select('TipoComunicacao.nome')
                    ->contain(['Pessoa', 'ProcessoJudicial' => ['AssuntoProcessoJudicial' => ['AssuntoLocal']], 'TipoComunicacao']);
                break;

            case 'acervoContar':
                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->queryPadraoAcervo($query);
                $query
                ->select(['servidor' => 'Servidor.servidor'])
                ->select(['total' => $find->func()->count('*')])
                    ->andWhere(function ($exp) {
                        return $exp->or_([
                            'Acervo.ativo' => 1,
                            'Servidor IS NULL'
                        ]);
                    })
                    ->group(['Servidor.SERVIDOR']);

                break;

            case 'unidadeDownload':
                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->queryPadraoMovimentoDownload($query, $options['valor']);
                break;

            case 'unidadeVisualizar':
                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->queryPadraoMovimentoDownload($query, $options['valor']);
                $query
                ->select('ProcessoJudicial.numero')
                ->select('Pessoa.nome')
                ->select('TipoComunicacao.nome')
                    ->contain(['Pessoa', 'ProcessoJudicial' => ['AssuntoProcessoJudicial' => ['AssuntoLocal']], 'TipoComunicacao']);
                break;

            case 'unidadeContar':
                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query = $this->queryPadraoMovimento($query);
                $query         
                    ->select('Unidade.UNIDADE')
                    ->select('Unidade.COD_UNIDADE')
                    ->select(['total' => $find->func()->count('*')])
                        ->group(['Unidade.unidade', 'Unidade.COD_UNIDADE']);
                break;

            case 'DocumentoSicaj':
                $dateTime = date_create($options['ano'].$options['mes'].$options['dia']);

                $query
                    ->select(['aviso_id' => 'AvisoPendente.id'])
                    ->select('documento_comunicacao.binario')
                    ->select('documento_comunicacao.mimetype')
                    ->select(['descricao' => 'documento_comunicacao.descricao'])
                    ->select(['tipoDocumento_id' => 'documento_comunicacao.tipo_documento_id'])
                    ->select(['num_processo' => 'Processo.num_processo'])
                        //Caso salve o documento no sicaj logo apos salvar o documento do aviso
                        //Não é necessario caso seja melhor esperar buscar todos os avisos e depois salvar no sicaj todos de uma só vez
                        // ->where(['documento_comunicacao.id' => $options['id'] ])
                        ->where(['AvisoPendente.id IN' => $options['id'] ])
                        // ->where(function ($exp) {
                        //     return $exp->or_([
                        //         ['AvisoPendente.id IN' => $options['id'] ],
                        //         ['AvisoPendente.data_disponibilizacao >' => $dateTime]
                        //     ]);
                        // })
                        ->where(['Processo.num_processo IS NOT NULL'])
                            ->leftJoin(['comunicacao' => 'Comunicacao'], [
                                'AvisoPendente.id = Comunicacao.aviso_pendente_id'
                            ])
                            ->leftJoin(['documento_comunicacao'], [
                                'Comunicacao.id = documento_comunicacao.comunicacao_id'
                            ])
                    ->hydrate(false);

                break;
            case 'individual':
                $query = $this->queryPadraoData($query, $options['dia'], $options['mes'], $options['ano']);
                $query
                    ->select(['AvisoPendente.id', 'AvisoPendente.id_aviso', 'AvisoPendente.fonte', 'AvisoPendente.sucesso',
                        'AvisoPendente.data_disponibilizacao', 'AvisoPendente.mensagem'])
                    ->select(TableRegistry::get('ProcessoJudicial'))
                    ->select('Pessoa.nome')
                    ->select('TipoComunicacao.nome')
                    ->select('Processo.NUM_PROCESSO')
                        ->contain(['Pessoa', 'ProcessoJudicial' => ['AssuntoProcessoJudicial' => ['AssuntoLocal']],'TipoComunicacao']);

                break;
            case 'preencherInstantaneo':
                $query = $this->queryPadraoMovimento($query);
                $query = $this->queryPadraoRecurso($query);
                $query        
                    ->leftJoin(['Acervo' => 'sicaj.dbo.distrib_vincjud' ],[
                        'AND' => [
                            ['Processo.NUM_PROCESSO = Acervo.num_processo'],
                            ['Acervo.ativo = 1']
                        ],
                    ])
                    ->leftJoin(['Servidor' => 'sicaj.dbo.Servidor'],
                        ['Acervo.cod_servidor = Servidor.COD_SERVIDOR'
                    ])

                    ->select(['aviso_pendente_id' => 'AvisoPendente.id'])
                    ->select(['numero_processo_judicial' => 'ProcessoJudicial.numero'])
                    ->select(['numero_processo_recurso' => 'Recurso.num_processo'])
                    ->select(['numero_processo_processo' => 'Processo.NUM_PROCESSO'])
                    ->select(['coluna_Num_Proc_Jud_Novo' => 'Processo.Num_Proc_Jud_Novo'])
                    ->select(['coluna_NUM_PROC_JUD' => 'Processo.NUM_PROC_JUD'])
                    ->select(['coluna_NO_ORIGINAL' => 'Processo.NO_ORIGINAL'])
                    ->select(['coluna_NUM_PROCESSO_2' => 'Processo.NUM_PROCESSO_2'])
                    ->select(['codigo_unidade' => 'Unidade.COD_UNIDADE'])
                    ->select(['nome_unidade' => 'Unidade.UNIDADE'])
                    ->select(['codigo_servidor' => 'Servidor.COD_SERVIDOR'])
                    ->select(['nome_servidor' => 'Servidor.servidor'])
                    ->where(['AvisoPendente.sucesso' => true])
                        ->hydrate(false);
                    
                break;
            default:
                $query = [];
                break;
        }
        // debug($query->toArray());exit();

        return $query;
    }

}
