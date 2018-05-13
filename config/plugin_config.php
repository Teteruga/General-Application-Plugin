<?php
return [
	// 'thresholdBelongsTo' => 4,
	'thresholdNeighbors' => 6,
	'menuCreate' => 'Neighbors',
	// 'menuCreate' => 'BelongsTo',
	'logo_default' => 'logo_default.png',
	'languageTemplateActions' => [
		'en' => ['index' => 'index', 'view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete', 'list_by_association_ajax' => 'list_by_association_ajax'],
		'pt-br' => ['index' => 'index', 'vizualizar' => 'vizualizar', 'adicionar' => 'adicionar', 'editar' => 'editar', 'deletar' => 'deletar', 'listar_por_associacao_ajax' => 'listar_por_associacao_ajax'],
	],
	'noTemplateActions' => [
		'en' => ['delete' => 'delete', 'list_by_association_ajax' => 'list_by_association_ajax'],
		'pt-br' => ['deletar' => 'deletar', 'listar_por_associacao_ajax' => 'listar_por_associacao_ajax'],
	],
	'enToLanguageTranslation' => [
		'pt-br' => ['index' => 'index', 'view' => 'vizualizar', 'add' => 'adicionar', 'edit' => 'editar', 'delet' => 'deletar', 'deletMsg' => 'Você realmente deseja deletar', 'list_by_association_ajax' => 'listar_por_associacao_ajax'],
		'en' => ['index' => 'index', 'view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delet' => 'delet', 'deletMsg' => 'Are you sure you want to delete', 'list_by_association_ajax' => 'list_by_association_ajax'],
	],
	'listByVarAjax' => [
		'en' => 'listBy_VARASSOC_Ajax',
		'pt-br' => 'listarPor_VARASSOC_Ajax',
	],
	'emptySelectValue' => [
		'en' => 'Select...',
		'pt-br' => 'Selecione...',
	],

	// 'logo' => 'logo_gif.gif',
	// 'logo_inline_style' => ['width: 50px; height: 50px;'],
	// 'app_name' => false,
	// 'thresholdBelongsTo' => 4,
	// 'thresholdNeighbors' => 6,
	// 'menuCreate' => 'Neighbors',
	// 'bake_language' => 'pt-br',
];