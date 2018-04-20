<?php

namespace App\Controller;

use App\Controller\AppController;
use SoapClient;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use App\Controller\MNIController;
use \DateTime;
use Cake\Log\Log;

/**
 * AvisoPendente Controller
 *
 * @property \App\Model\Table\AvisoPendenteTable $AvisoPendente
 *
 * @method \App\Model\Entity\AvisoPendente[] paginate($object = null, array $settings = [])
 */

//RENOMEAR METODOS!
//ALTERAR DATA PADRÃO(SALVA NA PASTA DIGITAL E BUSCA DOS AVISOS) PARA APARTIR DE E NÂO A DATA FIXA!!
//REFATORAR CODIGO(MODULARIZAR)!!
//CONTINUAR TESTE COM A BASE DE DADOS!!
class AvisoPendenteController extends AppController {

    const OPTIONS_SOAP = array(
        'trace' => 1,
        'exceptions' => true,
        'encoding' => 'UTF-8',
    );

    const TIPODOCUMENTO_ID = array('INT' => 56, 'CIT' => 210);
    //MUDAR DATA POSTERIORMENTE E MOVER PARA UM ARQUIVO DE CONFIGURAÇÂO
    const DATAINICIO = 20170101000000;

    public $DATAFIM;
  	public $consultaPadrao;
    public $Soap;
    public $MNI;

    public $paginate = [
        'sortWhitelist' => [
            'AvisoPendente.id',
            'AvisoPendente.id_aviso',
            'AvisoPendente.fonte',
            'AvisoPendente.data_disponibilizacao',
            'AvisoPendente.data_consultado',
            'AvisoPendente.tipo_comunicacao_id',
            'InstantaneoAvisoPendente.numero_processo',
            'InstantaneoAvisoPendente.numero_processo_judicial',
        ]
    ];


    /*
     *
     */
    public function initialize() {

        parent::initialize();

        $this->MNI = new MNIController();

        $this->loadComponent('Pge.Util');
        $this->loadComponent('Pge.Ldap');

        $this->DATAFIM = date('Y').date('m').date('d').'000000';

        Configure::load('_ws_config_desenvolvimento');

        $wsdl = Configure::read('wsdl');
        $this->Soap = new \SoapClient($wsdl, self::OPTIONS_SOAP);

        $this->consultaPadrao['idConsultante'] = Configure::read('idManifestante');
        $this->consultaPadrao['senhaConsultante'] = Configure::read('senhaManifestante');
    }

    ## View ##

        //REFATORAÇÂO NECESSARIA!!!!!!!!1!
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index() {

		set_time_limit(300);
        $filtro_busca = [];

        $tabela_assunto_local = TableRegistry::get('AssuntoLocal');
        $tabela_instantaneo_aviso = TableRegistry::get('InstantaneoAvisoPendente');


        if( !empty($this->request->getQueryParams()) ){

            $dados_busca = $this->request->getQueryParams();

            unset($dados_busca['manter_dia']);

            if( isset($dados_busca['sem_numero_pa']) ){
                if($dados_busca['sem_numero_pa'] === '1')
                    $dados_busca['InstantaneoAvisoPendente']['numero_processo'] = '';
                    $filtro_busca = ['is_null' => ['InstantaneoAvisoPendente' => ['numero_processo']] ];
            }
            
            unset($dados_busca['sem_numero_pa']);

            if( !empty($dados_busca['dados']['por']) ){

                $por = $dados_busca['dados']['por'];
        
                $dia = $dados_busca['dados']['dia'];
                $mes = $dados_busca['dados']['mes'];
                $ano = $dados_busca['dados']['ano'];
        
                $nome = $dados_busca['dados']['nome'];
                $valor = $dados_busca['dados']['valor'];

                unset($dados_busca['dados']);

            }
            else if( !empty( $dados_busca['data_envio'] ) ){

                $data_envio = explode('/', $dados_busca['data_envio']);

                $dia =  $data_envio[0];
                $mes =  $data_envio[1];
                $ano =  $data_envio[2];

                unset($dados_busca['data_envio']);

            }
            else{
                $dia = date('d');
                $mes = date('m');
                $ano = date('Y'); 
            }

            $avisos_pendentes = $this->AvisoPendente->find('avisosPendentesPor',[
                                        'por' => (isset($por)) ? $por : 'individual',
                                        'dia' => $dia, 'mes' => $mes, 'ano' => $ano,
                                        'nome' => (isset($nome)) ? $nome : NULL,
                                        'valor' => (isset($valor)) ? $valor : NULL,
                                        'busca' => $dados_busca,
                                        'filtro' => 'index',
                                        'filtroBusca' => $filtro_busca,
                                        ]);       
        }
        else{
            $dia = date('d');
            $mes = date('m');
            $ano = date('Y');
            
            $avisos_pendentes = $this->AvisoPendente->find('avisosPendentesPor',
                                        ['por' => 'individual', 'dia' => $dia, 'mes' => $mes, 'ano' => $ano]);
        }

        foreach($avisos_pendentes as $aviso){

            if( isset($aviso->processo_judicial->assunto_processo_judicial) ){
                foreach($aviso->processo_judicial->assunto_processo_judicial as $assunto_processo_judicial ){

                    if( isset($assunto_processo_judicial->assunto_local) )
                        $assunto = $assunto_processo_judicial->assunto_local;
                    else
                        $assunto = NULL;

                    $pais = $this->encontrarPaisAssunto($assunto->paiLocal);

                    if( isset($caminho[$aviso->id]) )
                        $caminho[$aviso->id] .= ','.$pais;

                    else
                        $caminho[$aviso->id] = $assunto->descricao.$pais;

                }
            }
        }

        $avisoPendente = $this->paginate($avisos_pendentes);

        $data = $dia.'/'.$mes.'/'.$ano;

        if( !isset($nome))
            $nome = 'Avisos Pendentes';
        else{
            ($por == 'acervoVisualizar') ? $por_name = 'Por Acervo' : $por_name = 'Por Unidade';
            $nome = $por_name .': '.$nome.' '.$data;
        }


        if( !isset($valor))
        	$valor = NULL;


        $tipos_comunicacao = TableRegistry::get('TipoComunicacao')->find('list');

        $this->set(compact('avisoPendente', 'caminho', 'nome', 'data', 'valor', 'tipos_comunicacao'));
        $this->set('_serialize', ['avisoPendente']);
    }

    /**
     * 
     */
    public function porUnidade() {

		set_time_limit(300);

        if( isset($this->request->getQueryParams()['data_envio'])){
            $data = $this->request->getQueryParams()['data_envio'];

            $data = explode("/", $data);

            $dia = date($data[0]);
            $mes = date($data[1]);
            $ano = date($data[2]);
        }
        else{
            $dia = date('d');
            $mes = date('m');
            $ano = date ('Y');
        }
        
        $query = $this->AvisoPendente->find('avisosPendentesPor', ['por' => 'unidadeContar', 'dia' => $dia, 'mes' => $mes, 'ano' => $ano]);

        $unidades = $this->paginate($query);
        $this->set(compact('unidades', 'dia', 'mes', 'ano'));
    }

    /**
     * 
     */
    public function porAcervo(){
		set_time_limit(300);

        if( isset($this->request->getQueryParams()['data_envio'])){
            $data = $this->request->getQueryParams()['data_envio'];

            $data = explode("/", $data);

            $dia = date($data[0]);
            $mes = date($data[1]);
            $ano = date($data[2]);
        }
        else{
            $dia = date('d');
            $mes = date('m');
            $ano = date ('Y');
        }

        $query = $this->AvisoPendente->find('avisosPendentesPor', ['por' => 'acervoContar', 'dia' => $dia, 'mes' => $mes, 'ano' => $ano]);

        $acervos = $this->paginate($query);

        foreach($acervos as $acervo)
            $servidores[] = $acervo->servidor;

        $this->set(compact('acervos', 'servidores', 'dia', 'mes', 'ano'));
    }

    /**
     * 
     */
    public function baixarComunicacao($aviso_id) {

        $comunicacaoTable = TableRegistry::get('Comunicacao');

        $comunicacao = $comunicacaoTable->find()->where(['aviso_pendente_id' => $aviso_id])
                        ->contain(['DocumentoComunicacao' => ['OutroParametroDocumentoComunicacao']])->first();

        $this->set(compact('comunicacao'));
    }

    /**
     * 
     */
    public function baixarComunicacoes(){

        set_time_limit(-1);

        $dados_post = $this->request->data();

        $por = $dados_post['por'];
        
        $dia = $dados_post['dia'];
        $mes = $dados_post['mes'];
        $ano = $dados_post['ano'];

        isset($dados_post['nome']) ? $nome = $dados_post['nome'] : $nome = NULL;
        isset($dados_post['valor']) ? $valor = $dados_post['valor'] : $valor = -1;

        $query = $this->AvisoPendente->find('avisosPendentesPor',
                ['por' => $por, 'nome' => $nome, 'valor' => $valor, 'dia' => $dia, 'mes' => $mes, 'ano' => $ano ]);

        $avisos = $query;
        $this->set(compact('avisos', 'nome', 'dia', 'mes', 'ano'));
        $this->set('_serialize', ['avisos']);
    }

    /**
     * 
     */
    public function comunicacoesPorAcervo($valido, $nome, $dia, $mes, $ano){
    
        set_time_limit(300);
        $find = $this->AvisoPendente->find();

        $query = $this->AvisoPendente->find('avisosPendentesPor',
                                ['por' => 'acervoDownload', 'nome' => $nome, 'valor' => $valido, 'dia' => $dia, 'mes' => $mes, 'ano' => $ano]);

        return $this->paginate($query);
    }

    /**
     * 
     */
    public function comunicacoesPorUnidade($codigo_unidade, $nome, $dia, $mes, $ano){

        set_time_limit(300);

        $query = $this->AvisoPendente->find('avisosPendentesPor', 
                                        ['por' => 'unidadeDownload', 'valor' => $codigo_unidade, 'dia' => $dia, 'mes' => $mes, 'ano' => $ano]);
           
        return $this->paginate($query);
    }

    ## End View ##

    /**
     *  Metodo que encontra os assuntos pai de um assunto de um processo
     *
     *  @param $id_pai id do pai do assunto
     *
     *  @param $pais string dos pais já encontrados
     *
     *  @return string
     */
    public function encontrarPaisAssunto($id_pai, $pais = NULL){

        $tabela_assunto_local = TableRegistry::get('AssuntoLocal');

        $assunto_pai = $tabela_assunto_local->find()->where(['id' => $id_pai])->first();

        $pais .= ','.$assunto_pai->descricao;

        if($assunto_pai->paiLocal != NULL)
            return $this->encontrarPaisAssunto($assunto_pai->paiLocal, $pais);
        else
            return $pais;
    }

 	/**
     * 
     */
    public function buscarAvisosPendentes($dataInicio = NULL, $dataFim = NULL) {

        $salvos = NULL;

        if( !isset($dataInicio) )
            $dataInicio = self::DATAINICIO;

        if( !isset($dataFim) )
            $dataFim = $this->DATAFIM;

        $consultaAvisoPendentes = $this->consultaPadrao;
        $consultaAvisoPendentes['dataReferencia'] = $dataInicio;
        $consultaAvisoPendentes['dataFimReferencia'] = $dataFim;

        $avisos_pendentes = $this->Soap->consultarAvisosPendentes($consultaAvisoPendentes);

        if(isset($avisos_pendentes->sucesso))
        	$sucesso = $avisos_pendentes->sucesso;
		else
			$sucesso = false;

		if($sucesso) {
			if(isset($avisos_pendentes->aviso)) {

                if(!is_array($avisos_pendentes->aviso))
                    $avisos = array($avisos_pendentes->aviso); 
                else
                    $avisos = $avisos_pendentes->aviso;

                foreach ($avisos as $aviso_pendente) {
                // for($i = 0; $i < 3;$i++){
                    // $aviso_pendente = $avisos[$i];
                    $aviso_pendente->sucesso = $sucesso;
                    $aviso_salvo = $this->criarAvisoPendente($aviso_pendente);

                    if($aviso_salvo)
                        $salvos[] = $aviso_salvo;
                }
            } 
            else
                $salvos = false;
        } 
        else {
            $salvos = false;
            $avisos_pendentes->sucesso = $sucesso;
            $this->criarAvisoPendente($avisos_pendentes);
        }

        return $salvos;
    }

   	/**
     * 
     */
    public function criarAvisoPendente($aviso) {

        $aviso_encontrado = NULL;

        if (isset($aviso->idAviso)) {
            $aviso_encontrado_obj = $this->AvisoPendente->find()->where(['id_aviso' => $aviso->idAviso])->first();

            if (isset($aviso_encontrado_obj))
                $aviso_encontrado = 1;
        }
        else if ($aviso->sucesso)
            $aviso_encontrado = -1;

        if (($aviso->sucesso) && ($aviso_encontrado == NULL)) {

            if (isset($aviso->processo))
                $processoId = $this->encontrarProcesso($aviso->processo, $aviso);

            if (isset($aviso->destinatario->pessoa))
               	$destinatarioId = $this->encontrarPessoa($aviso->destinatario->pessoa, $aviso);
        }
        else{
            $processoId = NULL;
            $destinatarioId = NULL;
        }

        if ($aviso_encontrado == NULL) {

           	$this->montarAvisoPendente($processoId, $destinatarioId, $aviso);

        	$dados = $this->salvarAvisoPendente($aviso);

        	$sucesso = $dados['sucesso'];
        	$aviso_save = $dados['aviso_save'];
        	$salvo = $aviso_save->id;
        } 
        else
            $salvo = false;

        if (isset($aviso_save) && ($sucesso))
            $dados = $this->criarComunicacao($aviso_save);

        return $salvo;
    }

    /**
     *
     */
    public function encontrarProcesso($processo, $aviso){
        
        //Mudar!!
      	$ProcessoJudicial = TableRegistry::get('ProcessoJudicial');

    	if (isset($processo->numero)) {

            preg_match_all('/\D+/', $processo->numero, $achados);

            $processo->numero = str_replace($achados[0], "", $processo->numero);

            $processo_QUERY = $ProcessoJudicial->find()->where(['numero' => $processo->numero])->first();

            if ($processo_QUERY == NULL)
				$processoId = $this->criarProcesso($processo, $aviso);
            else 
                $processoId = $processo_QUERY['id'];
        } 
        else 
            $processoId = NULL;

        return $processoId;
    }

    /**
     *
     */
    public function criarProcesso($processo, $aviso){

    	$dadosBasicos = new \stdClass;

        $variaveis = get_object_vars($processo);

        foreach ($variaveis as $key => $value){
           	$dadosBasicos->$key = $value;
            unset($processo->$key);
        }

        $processo->dadosBasicos = $dadosBasicos;

		$processo_dados = $this->MNI->montarDadosBasicos($aviso);

        unset($processo_dados['status']->processo);

        $processo_save = $this->MNI->cadastrarProcessoJudicialFromDados($processo_dados);

        $processoId = $processo_save->id;

        return $processoId;
    }

    /**
     *
     */
    public function encontrarPessoa($pessoa, $aviso){

        $PessoaTable = TableRegistry::get('Pessoa');

        if (isset($pessoa)) {

        	if (isset($pessoa->numeroDocumentoPrincipal))
        		$destinatario = $PessoaTable->find()->where(['numero_documento_principal' => $pessoa->numeroDocumentoPrincipal])
        									->first();
        	else 
        		$destinatario = NULL;

        	if ($destinatario == NULL) {

        		if (isset($pessoa->nome))
        			$destinatario = $PessoaTable->find()->where(['nome' => $pessoa->nome])->first();
        	}

        	if ($destinatario == NULL)
				 $destinatarioId = $this->criarPessoa($PessoaTable, $pessoa);
        	else 
        		$destinatarioId = $destinatario->id;
        } 
        else
        	$destinatarioId = NULL;

        return $destinatarioId;
    }

    /**
     *
     */
    public function criarPessoa($PessoaTable, $pessoa){

        $pessoa_dados = $this->Util->objectToArray($pessoa);

       	$pessoa_entity = $PessoaTable->newEntity($pessoa_dados);

        $destinatario = $PessoaTable->save($pessoa_entity);      

        $destinatarioId = $destinatario->id;

        return $destinatarioId;
    }

    /**
     *
     */
    public function montarAvisoPendente($processoId, $destinatarioId, $aviso){

    	if (isset($processoId))
            $aviso->processoId = $processoId;
        if (isset($destinatarioId))
            $aviso->destinatarioId = $destinatarioId;
        if (isset($aviso->tipoComunicacao))
            $aviso->tipoComunicacaoId = $aviso->tipoComunicacao;

        if (isset($aviso)) {
            $aviso->fonte = 'TJRJ';
            unset($aviso->processo);
            unset($aviso->destinatario);
            unset($aviso->tipoComunicacao);
        }

        if( isset($aviso->dataDisponibilizacao))
            $aviso->dataDisponibilizacao = $this->montarData($aviso->dataDisponibilizacao);
        else
            $aviso->dataDisponibilizacao = $this->montarData();

        $aviso->dataConsultado = $this->montarData();
    }

    /**
     *
     */
    public function montarData($dateTime = NULL){

    	if (isset($dateTime))
            $data = \DateTime::createFromFormat('Ymd His', $dateTime)->format('Y-m-d H:i:s');
		else
            $data = date("Y-m-d H:i:s");
        
        return $data;
    }

    /**
     *
     */
    public function salvarAvisoPendente($aviso){

        $sucesso = $aviso->sucesso;
        $aviso_dados = $this->Util->objectToArray($aviso);
        $aviso_entidade = $this->AvisoPendente->newEntity($aviso_dados);
        //QUANDO O AVISO VEM FALSE A FUNÇÂO objectToArray NÂO RETORNA O INDEX "SUCESSO"
        $aviso_entidade->sucesso = $sucesso;
        $aviso_save = $this->AvisoPendente->save($aviso_entidade);
        $salvo = $aviso_save->id;

        $return = array('sucesso' => $sucesso, 'aviso_save' => $aviso_save);

        return $return;
    }

    /**
     *
     */
    public function criarComunicacao($aviso){

        $comunicacaoTable = TableRegistry::get('Comunicacao');

        $consultaTeorComunicacao = $this->consultaPadrao;
        $consultaTeorComunicacao['identificadorAviso'] = $aviso->id_aviso;

        $teorComunicacao = $this->Soap->consultarTeorComunicacao($consultaTeorComunicacao);

        //1 Comunicação por aviso?
        $comunicacao_encontrado = $comunicacaoTable->find()->where(['aviso_pendente_id' => $aviso->id])->first();

       	$dados = $this->montarComunicacao($teorComunicacao, $comunicacao_encontrado, $aviso);

       	$comunicacao = $dados['comunicacao'];
       	$documento  = $dados['documento'];

        if( isset($comunicacao->dataReferencia) )
       	    $comunicacao->dataReferencia = $this->montarData($comunicacao->dataReferencia);
        else
            $comunicacao->dataReferencia = $this->montarData();

        if ($comunicacao_encontrado == NULL) {

           	$comunicacao_save = $this->salvarComunicacao($comunicacao, $comunicacaoTable);

            if (isset($documento) && ($comunicacao_save != false))
            		$documento_save = $this->salvarDocumento($documento, $comunicacao_save);
        }
        else
            $documento_save = NULL;

        $dados_return = array('comunicacao' => $comunicacao_save, 'documento' => $documento_save);
        return $dados_return;
    }

    /**
     *
     */
    public function montarComunicacao($teorComunicacao, $comunicacao_encontrado, $aviso){

		$ProcessoJudicial = TableRegistry::get('ProcessoJudicial');
		$comunicacao = new \stdClass;

        if (($teorComunicacao->sucesso) && ($comunicacao_encontrado == NULL)) {

            $comunicacao = $teorComunicacao->comunicacao;
            $comunicacao->sucesso = $teorComunicacao->sucesso;

            if (isset($comunicacao->destinatario))
            	unset($comunicacao->destinatario);

            if (isset($comunicacao->documento)) {
            	$documento = $comunicacao->documento;
            	unset($comunicacao->documento);
            }
            else
            	$documento = NULL;

            if (isset($comunicacao->tipoComunicacao)) {
            	$comunicacao->tipoComunicacaoId = $comunicacao->tipoComunicacao;
            	unset($comunicacao->tipoComunicacao);
            } 
            else
            	$comunicacao->tipoComunicacaoId = NULL;

            if (isset($comunicacao->nivelSigilo)) {
            	$comunicacao->nivelSigiloId = $comunicacao->nivelSigilo;
            	unset($comunicacao->nivelSigilo);
            } 
            else
            $comunicacao->nivelSigiloId = NULL;

            if (isset($comunicacao->tipoPrazo)) {
            	$comunicacao->tipoPrazoId = $comunicacao->tipoPrazo;
            	unset($comunicacao->tipoPrazo);
            } 
            else
            $comunicacao->tipoPrazoId = NULL;

            if(!isset($comunicacao->processo))
            $comunicacao->processo = $ProcessoJudicial->find()->where(['id' => $aviso->processo_id])->first()->numero;
        }
        else if (isset($teorComunicacao->mensagem)){
            $documento = NULL;
            $comunicacao->mensagem = $teorComunicacao->mensagem;
            $comunicacao->sucesso = false;
        }

        $comunicacao->avisoPendenteId = $aviso->id;

        $dados = array('comunicacao' => $comunicacao, 'documento' => $documento);
       	return $dados;
    }

    /**
     *
     */
    public function salvarComunicacao($comunicacao, $comunicacaoTable){

        if(isset($comunicacao->sucesso))
            $sucesso = $comunicacao->sucesso;
        else
            $sucesso = false;

        $comunicacao_dados = $this->Util->objectToArray($comunicacao);

        $comunicacao_entidade = $comunicacaoTable->newEntity($comunicacao_dados);

        $comunicacao_entidade->sucesso = $sucesso;

        $comunicacao_save = $comunicacaoTable->save($comunicacao_entidade);

        return $comunicacao_save;
    }

    /**
     *
     */
    public function salvarDocumento($documento, $comunicacao_save){

        $documentoComunicacaoTable = TableRegistry::get('DocumentoComunicacao');
        $outroParametroTable = TableRegistry::get('OutroParametroDocumentoComunicacao');

		if (!is_array($documento))
            $documentos = array($documento);

		foreach ($documentos as $key => $value) {

			$documento_dados = $this->Util->objectToArray($value);
			$documento_dados['mimetype'] = 'application/pdf';

			$documento_entidade = $documentoComunicacaoTable->newEntity($documento_dados);

			$outros_parametros = $documento_dados['outro_parametro'];

			$documento_entidade->parametros = $outroParametroTable->newEntities($outros_parametros);

			$documento_entidade->comunicacao_id = $comunicacao_save->id;
			$documento_entidade->tipo_documento_id = self::TIPODOCUMENTO_ID[$comunicacao_save->tipo_comunicacao_id];

			$documento_save[] = $documentoComunicacaoTable->save($documento_entidade);
		}

		return $documento_save;
    }

    /**
     *  Escolher entre chamar esse metodo depois de salvar o documento ou em outro momento
     *  Metodo que salva os documentos na pasta digital do sicaj assim ativando o evento da caixa de entrada
     */
    public function salvarDocumentoPastaDigitalSicaj($avisos_id, $dia = NULL, $mes = NULL, $ano = NULL){

        $tabela_documento_sicaj = TableRegistry::get('Documento');

        $avisos = $this->AvisoPendente->find('avisosPendentesPor',
                                    ['por' => 'DocumentoSicaj', 'dia' => $dia, 'mes' => $mes, 'ano' => $ano, 'id' => $avisos_id]);

        if(empty($avisos->toList()))
            $documento_save = false;
        else{
            foreach($avisos as $aviso){

                $aviso['binario']['binario'] = $aviso['documento_comunicacao']['binario'];
                $aviso['binario']['nome'] = 'teste_avisos_pendentes_id_'.$aviso['aviso_id'].'.pdf';
                $aviso['binario']['mimetype'] = $aviso['documento_comunicacao']['mimetype'];

                $aviso['dataInclusao'] = date("Y-m-d");
                $aviso['horaInclusao'] = date("H:i");

                unset($aviso['documento_comunicacao']);

                $aviso['inativo'] = false;
                $aviso['usr'] =  'admin';

                if(!isset($aviso['descricao']) )
                    $aviso['descricao'] = 'documento do aviso pendente_'.$aviso['aviso_id'];

                $documento = $tabela_documento_sicaj->newEntity($aviso);

                $documento_salvo = $tabela_documento_sicaj->save($documento);

                $documento_save[] = $documento_salvo->aviso_id;
            }
        }

        return $documento_save;
    }

    /**
     *
     */
    public function buscarAvisosSalvarSicaj($dia_inicio = NULL, $mes_inicio = NULL, $ano_inicio = NULL,
        $dia_fim = NULL, $mes_fim = NULL, $ano_fim = NULL){

        $start = microtime(true);

        set_time_limit(-1);
        $this->autoRender = false;

        if($dia_inicio != NULL){

	        if($mes_inicio == NULL)
	            $mes_inicio = date('m');
	        if($ano_inicio == NULL)
	            $ano_inicio = date('Y');

	        $dataInicio = $ano_inicio.$mes_inicio.$dia_inicio.'000000';

            if($dia_fim != NULL){

                if($mes_fim == NULL)
                    $mes_fim = date('m');
                if($ano_fim == NULL)
                    $ano_fim = date('Y');

                $dataFim = $ano_fim.$mes_fim.$dia_fim.'000000';
            }
            else
                $dataFim = NULL;
	    }
	    else{
	    	$dataInicio = NULL;
            $dataFim = NULL;
        }

        $avisos = $this->buscarAvisosPendentes($dataInicio, $dataFim);

        if( !empty($avisos) )
            $avisos_log = implode('-', $avisos);
        else
            $avisos_log = 'none';

        if( !empty($avisos) )
            $documentoSicaj = $this->salvarDocumentoPastaDigitalSicaj($avisos);
        else
            $documentoSicaj = false;

        if(!empty($documentoSicaj) )
            $salvos = implode('-', $documentoSicaj);
        else
            $salvos = 'Não Salvos';

        $time_elapsed_secs = microtime(true) - $start;
        $this->log( "\nTempo para buscar os avisos: ".$time_elapsed_secs."(s)\nAvisos Salvos: ". $avisos_log."\nDocumentos Sicaj: ".$salvos, 'info');

        $this->preencherInstantaneo();
    }

    /**
     *
     */
    public function visualizarDocumentoComunicacao($documento_id){

    	$documentoComunicacaoTable = TableRegistry::get('DocumentoComunicacao');

    	$documento = $documentoComunicacaoTable->find()->where(['id' => $documento_id])->first();

    	$this->set(compact('documento'));
    }

    /**
     *
     */
    public function verTodosDocumentosComunicacao($comunicacao_id){

    	$documentoComunicacaoTable = TableRegistry::get('DocumentoComunicacao');

    	$documentos = $documentoComunicacaoTable->find()->where(['comunicacao_id' => $comunicacao_id]);

		$this->set(compact('documentos'));
    }

    /**
     *
     */
    public function preencherInstantaneo(){

    	set_time_limit(-1);
		$this->autoRender = false;

    	$avisos = $this->AvisoPendente->find('avisosPendentesPor', ['por' => 'preencherInstantaneoProcesso']);
    	
    	foreach($avisos as $aviso)
    		$instantaneo_aviso[] = $this->criarAvisoPendenteInstantaneo($aviso);

    	$instantaneoAvisoTable = TableRegistry::get('InstantaneoAvisoPendente');

    	$instantaneoAvisoTable->deleteAll(['aviso_pendente_id >' => '0']);

		$instantaneo_aviso_entidades = $instantaneoAvisoTable->newEntities($instantaneo_aviso);

        foreach($instantaneo_aviso_entidades as $instantaneo_aviso_entidade)
            $instantaneoAvisoTable->save($instantaneo_aviso_entidade);

        //Não esta funcionando por que?
		// $instantaneoAvisoTable->saveMany($instantaneo_aviso_entidades);
    }

    /**
     *  SEMI-COPIA!
     *  METODO ORIGINAL EM SOLITIÇÂO
     */
    public function novoProcessoAviso($aviso_id){
        /*
         * 
         * ATENÇÃO: SALVAR OS AUTORES E REUS TAMBÉM NA TABELA JUD_LITS
         * ATENÇÂO: CRIAR UM VALIDADOR PARA O PROCESSO
         *
         * 
         */
        set_time_limit(-1);

        $processoTable = TableRegistry::get('Processo');

        $aviso = $this->AvisoPendente->find()
                ->where(['AvisoPendente.id' => $aviso_id])
                    ->contain(['ProcessoJudicial' => ['AssuntoLocal'], 'Comunicacao' => ['DocumentoComunicacao'] ])
                    ->first();

        $polo_ativo;
        $polo_passivo;
        $cnj = $aviso->processo_judicial->numero;

        $sugestao = $this->sugerirAssuntos($aviso->processo_judicial->assuntos_locais);

        $processo = $processoTable->newEntity();

        // combos
        $juizos = $processoTable->Juizo->find('list');

        $assuntos = $processoTable->Assunto->
                find('list', ['keyField' => 'COD_ASSUNTO', 'valueField' => 'ASSUNTO'])
                ->order('ASSUNTO')
                ->toArray();

        $materias = $processoTable->Materia->
                find('list');

        $varas = $processoTable->Vara->
                find('list')
                ->where(['PRE_CAD' => 0, 'INATIVO' => 0])
                ->order('ORIGEM');

        $origens = $processoTable->Origem->
                find('list')
                ->where(['inativo' => 0])
                ->order('ORGAO');

        $partes = TableRegistry::get('Parte')->find()
            ->select(['polo' => 'tipo_polo_id'])
            ->select(['numero_processo' => 'ProcessoJudicial.numero'])
            ->select(['valor' => 'ProcessoJudicial.valor_causa'])
            ->select(['numero_documento' => 'Pessoa.numero_documento_principal'])
            ->select(['nome' => 'Pessoa.nome'])
                ->where(['ProcessoJudicial.numero' => $cnj])
                    ->contain(['Pessoa', 'ProcessoJudicial']);

        $unidades = TableRegistry::get('Unidade')->find('ListaUnidadesSede');

        $motivos = TableRegistry::get('MotivoParecerJudicial')->find('list')->where(['TIPO_MOTIVO' => 'MJ']);

        $pessoaTable = TableRegistry::get('Interessado');

        $polo_ativo = [];
        $polo_passivo = [];
        $interessados = [];

        foreach($partes as $parte){

            $pessoa = $pessoaTable->find()
                    ->select(['COD_INTERESSADO', 'INTERESSADO'])
                        ->where(['INTERESSADO' => $parte->nome])
                        ->orWhere(['CPF_CNPJ' => $parte->numero_documento])
                            ->first();

            if(!isset($pessoa)){

                (!isset($parte->numero_documento)) ? $documento = '0' : $documento = $parte->numero_documento;

                // $pessoa = $this->novoInteressado($parte->nome, $documento);
                $pessoa = InteressadoController::novoInteressado($parte->nome, $documento);
            }

            if($parte->polo == 'AT')
                $polo_ativo[] = $pessoa;
            else if($parte->polo == 'PA')
                $polo_passivo[] = $pessoa;
            else
                $interessados[] = $pessoa;
        }

        if ($this->request->is('post')) {

            $data = $this->request->getData();

            $autores = json_decode($data['CODIGOS_AUTOR'], true);
            $reus = json_decode($data['CODIGOS_REU'], true);
            $interessados = json_decode($data['CODIGOS_INTERESSADO'], true);

            $movimentar = $data['primeiraMovimentacao'];

            (!empty($autores)) ? $data['COD_AUTOR'] = array_shift($autores) : $data['COD_AUTOR'] = NULL;

            (!empty($reus)) ? $data['COD_REU']= array_shift($reus) : $data['COD_REU'] = NULL;

            (!empty($interessados)) ? $data['COD_INTERESSADO'] = array_shift($interessados) : $data['COD_INTERESSADO'] = NULL;

            $processo = $this->cleanArrayToObject($data, 'processo', true);

            $jud_list = array();

            foreach($autores as $codigo)
                $jud_list[] = $this->montarJudList($codigo, $data['NUM_PROCESSO'], 'A');

            foreach($reus as $codigo)
                $jud_list[] = $this->montarJudList($codigo, $data['NUM_PROCESSO'], 'R');

            foreach($interessados as $codigo)
                $jud_list[] = $this->montarJudList($codigo, $data['NUM_PROCESSO']);

            debug($data);exit();

            // if(!empty($jud_list) ){

            //     $jud_listTable = TableRegistry::get('JudLitis');

            //     $jud_list_entities = $jud_listTable->newEntities($jud_list);

            //     $jud_listTable->saveMany($jud_list_entities);
            // }

            if ($processoTable->save($processo)) {
                $this->Flash->success('Processo salvo com sucesso !');

                if($movimentar == true){

                    //do stuff
                }
                else{

                    //do other stuff
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('O Processo não pode ser salvo');

        }

        $this->set(compact('processo', 'juizos', 'assuntos', 'materias', 'varas', 'origens', 'aviso', 'documentosTJ', 'cnj', 'polo_passivo', 'polo_ativo', 'interessados', 'sugestao', 'unidades', 'motivos'));
    }

    /**
     *
     *
     */
    public function atualizarInstantaneoPagina(){

        $this->viewBuilder()->layout(false);
        set_time_limit(-1);
        
        $data = $this->request->getQuery('ids');

        $ids = json_decode($data);

        foreach($ids as $id)
            $this->atualizarInstantaneo($id, false);

        $this->viewBuilder()->className('Json');

        $this->set('json', true); 
        $this->set('_serialize', 'json');
    }

    /**
     *
     *
     */
    public function atualizarInstantaneo($id, Bool $return = true){

        $this->viewBuilder()->layout(false);

        set_time_limit(-1);

        $instantaneoAvisoPendenteTable = TableRegistry::get('InstantaneoAvisoPendente');

        $aviso = $this->AvisoPendente->find('avisosPendentesPor', ['por' => 'atualizarInstantaneo', 'id' => $id]);
                        
        $instantaneo_aviso = $this->criarAvisoPendenteInstantaneo($aviso);

        $instantaneo = $instantaneoAvisoPendenteTable->find()->where(['aviso_pendente_id' => $id])->first();

        $instantaneoAvisoPendenteTable->patchEntity($instantaneo, $instantaneo_aviso);

        $instantaneoAvisoPendenteTable->save($instantaneo);

        $aviso = $this->AvisoPendente->find('avisosPendentesPor', ['por' => 'individualId', 'id' => $id])->first();

        if( isset($aviso->processo_judicial->assunto_processo_judicial) ){
            foreach($aviso->processo_judicial->assunto_processo_judicial as $assunto_processo_judicial ){

                if( isset($assunto_processo_judicial->assunto_local) )
                    $assunto = $assunto_processo_judicial->assunto_local;
                else
                    $assunto = NULL;

                $pais = $this->encontrarPaisAssunto($assunto->paiLocal);

                if( isset($caminho) )
                    $caminho .= ','.$pais;

                else
                    $caminho = $assunto->descricao.$pais;
            }
        }
        
        unset($aviso->processo_judicial);

        $json = $aviso;
        $json->assuntos = $caminho;

        unset($aviso);

        $this->viewBuilder()->className('Json');

        if($return){

            $this->set('json', $json); 
            $this->set('_serialize', 'json');
        }
    }

    /**
     *
     *
     */
    private function criarAvisoPendenteInstantaneo(Array $aviso, Bool $object = false){

        if(isset($aviso['numero_processo_recurso']) ){

            $aviso['numero_processo'] = $aviso['numero_processo_recurso'];
            $aviso['tabela_dominio_processo'] = 'recurso';
            $aviso['coluna_dominio_processo'] = 'cnj';
 
        }

        if(isset($aviso['numero_processo_processo']) ){
            $aviso['numero_processo'] = $aviso['numero_processo_processo'];
            $aviso['tabela_dominio_processo'] = 'processo';

            if($aviso['coluna_Num_Proc_Jud_Novo'] == $aviso['numero_processo_judicial'])
                $aviso['coluna_dominio_processo'] = 'Num_Proc_Jud_Novo';
            else if($aviso['coluna_NUM_PROC_JUD'] == $aviso['numero_processo_judicial'])
                $aviso['coluna_dominio_processo'] = 'NUM_PROC_JUD';
            else if($aviso['coluna_NO_ORIGINAL'] == $aviso['numero_processo_judicial'])
                $aviso['coluna_dominio_processo'] = 'NO_ORIGINAL';
            else if($aviso['coluna_NUM_PROCESSO_2'] == $aviso['numero_processo_judicial'])
                $aviso['coluna_dominio_processo'] = 'NUM_PROCESSO_2';
        }

        $aviso['data_hora'] = date("Y-m-d H:i:s");

        unset($aviso['numero_processo_processo']);
        unset($aviso['numero_processo_recurso']);
        unset($aviso['coluna_Num_Proc_Jud_Novo']);
        unset($aviso['coluna_NUM_PROC_JUD']);
        unset($aviso['coluna_NO_ORIGINAL']);
        unset($aviso['coluna_NUM_PROCESSO_2']);

        if(isset($aviso['numero_processo']) ){
            $numero_processo = $aviso['numero_processo'];
            $despacho = $this->AvisoPendente->find('avisosPendentesPor', ['por' => 'preencherInstantaneoDespacho', 'numero_processo' => $numero_processo ])->first();
        }
        else
            $despacho = [];

        if(!empty($despacho)){

            if($despacho['distribuicao_data'] > $despacho['movimentacao_data']){
                $aviso['despacho'] = $despacho['despacho_distribuicao'];
                $aviso['tabela_dominio_despacho'] = 'Distribuicao';
            }
            else if($despacho['movimentacao_data'] > $despacho['distribuicao_data']){
                $aviso['despacho'] = $despacho['despacho_movimentacao'];
                $aviso['tabela_dominio_despacho'] = 'Movimentacao';
            }
            else{

                if($despacho['distribuicao_hora'] >= $despacho['movimentacao_hora']){
                    $aviso['despacho'] = $despacho['despacho_distribuicao'];
                    $aviso['tabela_dominio_despacho'] = 'Distribuicao';
                }
                else if($despacho['movimentacao_hora'] > $despacho['distribuicao_hora']){
                    $aviso['despacho'] = $despacho['despacho_movimentacao'];
                    $aviso['tabela_dominio_despacho'] = 'Movimentacao';
                }
            }

            $aviso['nome_autor'] = $despacho['nome_autor'];
            $aviso['codigo_autor'] = $despacho['codigo_autor'];
            $aviso['ultima_distribuicao'] = $despacho['ultima_distribuicao'];

        }


        if($object)
            $aviso = TableRegistry::get('InstantaneoAvisoPendente')->newEntity($aviso);

        return $aviso;
    }

    /**
     *
     *  Movido como metodo statico em IneressadoController!
     */
    public function novoInteressado($nome, $documento = 0){

        $this->viewBuilder()->layout(false);
        set_time_limit(-1);

        $pessoaTable = TableRegistry::get('Interessado');

        $nova_pessoa = $pessoaTable->newEntity();

        $nova_pessoa->USUARIO1 = 'admin';
        $nova_pessoa->CPF_CNPJ = $documento;
        $nova_pessoa->INTERESSADO = $nome;
        $nova_pessoa->DATA_INC1 = date("Y-m-d H:i:s");

        $pessoa = $pessoaTable->save($nova_pessoa);

        $this->viewBuilder()->className('Json');

        $this->set('json', $pessoa->COD_INTERESSADO); 
        $this->set('_serialize', 'json');  
    }

    /**
     *  COPIA!
     *  METODO ORIGINAL EM SOLITIÇÂOCONTROLLER!
     *  NOVO METODO STATICO EM MNICONTROLLER!
     */
    public function listaDocumentosTj($processo){
        
        $this->viewBuilder()->layout(false);
        
        set_time_limit(-1);
                
        $documentosTJ = $this->MNI->consultarDocumentosProcesso($this->Soap, $processo);
        
        $listaDocumentosTJ = [];

        foreach ($documentosTJ as $documentoTJ) {

            if(!isset($documentoTJ['descricao']))
                $documentoTJ['descricao'] = 'Documento sem descrição';

            if(isset($documentoTJ['idDocumentoVinculado'])){
                $listaDocumentosTJ[$processo . '/' . $documentoTJ['idDocumentoVinculado'] . '/' . $documentoTJ['idDocumento']] = $documentoTJ['tipoDocumentoLocal']->descricao . " - " . $documentoTJ['descricao'];
            }else{
                $listaDocumentosTJ[$processo . '/' . $documentoTJ['idDocumento']] = $documentoTJ['tipoDocumentoLocal']->descricao . " - " . $documentoTJ['descricao'];
            }
            
        }

        $this->viewBuilder()->className('Json');

        $this->set('json', $listaDocumentosTJ); 
        $this->set('_serialize', 'json');
    }    

    /**
     *  FUNÇÂO USADA PARA MUDAR OS NUMEROS DOS AVISOS E ASSIM PODER CONSUMI-LOS DE NOVO COMO SE FOSSEM AVISOS DIFERENTES.
     *  FUNÇÂO UTILIZADA SOMENTE DURANTE OS TESTES!
     */
    public function mudarIdAvisos(){

        $this->autoRender = false;

        $avisos = $this->AvisoPendente->find()->where(['sucesso' => true]);

        foreach($avisos as $aviso){
            $avisoId['id_aviso'] = $aviso->id_aviso;
            $avisoId['id_aviso'][3] = $aviso->id_aviso[3]-1;

            // if($aviso->id_aviso[3] != '6')
            $avisos_save[] = $this->AvisoPendente->patchEntity($aviso, $avisoId);
        }

        foreach($avisos_save as $aviso)
            $this->AvisoPendente->save($aviso);
    }

    /**
     *
     */
    public function deleteAllInstantaneo(){

        set_time_limit(-1);
        $this->autoRender = false;

        $instantaneoAvisoTable = TableRegistry::get('InstantaneoAvisoPendente');

        $instantaneoAvisoTable->deleteAll(['aviso_pendente_id >' => '0']);
    }

    /**
     *
     *
     */
    public function montarJudList($codigo, $numero_processo, $tipo = NULL){

        $jud_list['NUM_PROCESSO'] = $numero_processo;
        $jud_list['COD_LITIS'] = $codigo;
        $jud_list['DATA_INC'] = date("Y-m-d H:i:s");
        $jud_list['USUARIO'] = 'admin'; 
        $jud_list['TIPO'] = $tipo;        

        return $jud_list;
    }

    /**
     * 
     *
     */
    public function sugerirAssuntos($assuntos){

        $descricoes = [];

        foreach($assuntos as $key => $assunto){
            $descricoes[$key] = $assunto->descricao;
            $descricoes[$key] .= $this->encontrarPaisAssunto($assunto->paiLocal);
        }

        $query_assuntos_sugeridos = TableRegistry::get('Assunto')->find('list', ['keyField' => 'COD_ASSUNTO', 'valueField' => 'ASSUNTO'])
                            ->distinct(['ASSUNTO'])
                            ->order('ASSUNTO');

        foreach($descricoes as $descricao){

            $descricoes_separadas = explode(',', $descricao);

            foreach($descricoes_separadas as $descricao_separada){

                $assunto_partido = preg_split('/\s+/', $descricao_separada);               
                $length = count($assunto_partido);

                while ($length != 0){
                
                    $assunto_busca = '';

                    for($i = 0; $i < $length; $i++){

                        if( $assunto_busca != NULL)
                            $assunto_busca .= ' '.$assunto_partido[$i];
                        else
                            $assunto_busca .= $assunto_partido[$i];
                    }

                    $query_assuntos_sugeridos->orWhere(['ASSUNTO LIKE' => '%'.$assunto_busca.'%']);

                    $length--;
                }
            }
        }

        return $query_assuntos_sugeridos->toArray();
    }

    /**
     *
     *
     */
    public function unsetKeys(Array &$array, Array $keys){
        //TRANSFERIR PARA UM HELPER
        foreach($keys as $key)
            unset($array[$key]);
    }

    /**
     *
     *
     */
    public function cleanArrayToObject(Array &$array, String $objectName, Bool $arrayToObject = false){
        //TRANSFERIR PARA UM HELPER
        $modelName = ucfirst(strtolower($objectName));

        $modelTable = TableRegistry::get($modelName);

        $keys = $modelTable->schema()->columns();

        $removeKeys = array_diff_ukey($array,array_flip($keys), 'strcasecmp');

        $this->unsetKeys($array, array_keys($removeKeys));

        unset($keys);
        unset($removeKeys);

        if($arrayToObject)
            return $modelTable->newEntity($array);
    }

    public function test(){}
}

