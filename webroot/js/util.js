
//https://stackoverflow.com/questions/40739059/add-working-days-using-javascript
function addWorkDays(startDate, days) {
    // Get the day of the week as a number (0 = Sunday, 1 = Monday, .... 6 = Saturday)
    var dow = startDate.getDay();
    var daysToAdd = parseInt(days);
    // If the current day is Sunday add one day
    if (dow == 0)
        daysToAdd++;
    // If the start date plus the additional days falls on or after the closest Saturday calculate weekends
    if (dow + daysToAdd >= 6) {
        //Subtract days in current working week from work days
        var remainingWorkDays = daysToAdd - (5 - dow);
        //Add current working week's weekend
        daysToAdd += 2;
        if (remainingWorkDays > 5) {
            //Add two days for each working week by calculating how many weeks are included
            daysToAdd += 2 * Math.floor(remainingWorkDays / 5);
            //Exclude final weekend if remainingWorkDays resolves to an exact number of weeks
            if (remainingWorkDays % 5 == 0)
                daysToAdd -= 2;
        }
    }
    startDate.setDate(startDate.getDate() + daysToAdd);
    return startDate;
}


function lightbox(obj, titulo, altura, largura, func) {
	
	var titulo = titulo || "PGE";
	var height = altura || 600;
	var width = largura || 800;
	var func = func || function(){};
	
    var url = $(obj).attr('href');

    $('<div>').dialog({
        modal: true,
        open: function ()
        {
			$(this).html('carregando...');
            $(this).load(url, func);
        },
        height: height,
        width: width,
        title: titulo,
		close: function(event, ui) 
        { 
            $(this).remove();
        } 
    });

}

function ativarAutoComplete(id, url, hidden_id){
        
			console.log("veio?");
        
			$("#"+id).autocomplete({
				source: url,
				minLength: 3,
				select: function (event, ui) {
					$('#'+hidden_id).val(ui.item.id);
					console.log("Selected: " + ui.item.value + " aka " + ui.item.id);
				}
			});
        
}

function ajaxForm(form, resultado_id) {
        
        var form = $(form);
        var formdata = false;
        if (window.FormData) {
            formdata = new FormData(form[0]);
        }

        var formAction = form.attr('action');
        
        //console.log(form);
        
        $.ajax({
            url: formAction,
            data: formdata ? formdata : form.serialize(),
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST',
            success: function (data, textStatus, jqXHR) {
                $('#'+resultado_id).html(data);
            }
        });
    }
	
	
function ajaxLoad(alvo, url) {
	
		console.log('url:'+url);

        $.ajax({
            url: url,
			beforeSend: function() {
				 $("#" + alvo).html('carregando ...');
			  }
        }) .done(function(html) {
            $("#" + alvo).html(html);
        });
    }	
	
	
function ajaxCombo(obj, url, alvo) {

        var valor = $(obj).val();

        $.get(url + "/" + valor, function (data) {

            console.log(data);

            $("#" + alvo + ' option').remove();
            var typeData = data;

            //console.log(data.length);     

            for (var i in data['json']) {
                console.log(data['json'][i]);

                $("#" + alvo).append(new Option(data['json'][i], i));
            }
        });

}
	
function ajaxCKEditor(obj, url, alvo) {
        var valor = $(obj).val();

        $.get(url + "/" + valor, function (data) {
            for (var i in data) {
                //$("#" + alvo).append(data[i]['value']);
                CKEDITOR.instances['informacoes'].setData(data[i]['value']);
                //CKEDITOR.replace('informacoes');
            }
            
        });
}

function toggleCheckboxes(source, tagName, msg) {
    checkboxes = document.getElementsByName(tagName);
    for (var i = 0, n = checkboxes.length; i < n; i++) {
        if (checkboxes[i].disabled == false) {
            checkboxes[i].checked = source.checked;
        }
    }
    contadorDeSelecionados(msg);
}	

function contadorDeSelecionados(msg) {
    var total_selecionados = $('.chkCdas input[type="checkbox"]:checked').length;
    if (total_selecionados > 1) {
        if(msg){
            $("#buttonSubmit").html(capitalizeFirstLetter(msg) + ' ' + total_selecionados + ' CDAs selecionadas');
        }else{
            $("#buttonSubmit").html('Enviar ' + total_selecionados + ' CDAs selecionadas para entrega');
        }
        
        if($('#buttonSubmitCancelar').length){
            $("#buttonSubmitCancelar").html(capitalizeFirstLetter('cancelar') + ' ' + total_selecionados + ' CDAs selecionadas');
        }
        
    } else if (total_selecionados === 0) {
        if(msg){
            $("#buttonSubmit").html('Selecione as CDAs que deseja ' + msg);
        }else{
            $("#buttonSubmit").html('Selecione as CDAs que deseja entregar');
        }
        
        if($('#buttonSubmitCancelar').length){
            $("#buttonSubmitCancelar").html('Selecione as CDAs que deseja cancelar');
        }
        
    } else {
        if(msg){
            $("#buttonSubmit").html(capitalizeFirstLetter(msg) + ' ' + total_selecionados + ' CDA selecionada');
        }else{
            $("#buttonSubmit").html('Enviar ' + total_selecionados + ' CDA selecionada para entrega');
        }
        
        if($('#buttonSubmitCancelar').length){
            $("#buttonSubmitCancelar").html('Cancelar ' + total_selecionados + ' CDA selecionada');
        }
        
    }

    return total_selecionados;
}

function limpaFormularioCep(pais_id, uf_id, cidade_id, bairro_id, rua_id, ibge_id = null) {
    // Limpa valores do formulário de cep.
    $("#"+rua_id).val("");
    $("#"+bairro_id).val("");
    $("#"+cidade_id).val("");
    $("#"+uf_id).val("");
    $("#"+pais_id).val("");
    $("#"+ibge_id).val("");
}

function preencherPorCep(cep_id, pais_id, uf_id, cidade_id, bairro_id, rua_id, ibge_id = null){
    //Nova variável "cep" somente com dígitos.
    var cep = $("#"+cep_id).val().replace(/\D/g, '');

    //Verifica se campo cep possui valor informado.
    if (cep != "") {
    
        //Expressão regular para validar o CEP.
        var validacep = /^[0-9]{8}$/;
    
        //Valida o formato do CEP.
        if(validacep.test(cep)) {
    
            //Preenche os campos com "..." enquanto consulta webservice.
            $("#"+rua_id).val("...");
            $("#"+bairro_id).val("...");
            $("#"+cidade_id).val("...");
            $("#"+uf_id).val("...");
            $("#"+pais_id).val("...");
            $("#"+ibge_id).val("...");
    
            //Consulta o webservice viacep.com.br/
            $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {
    
                if (!("erro" in dados)) {
                    //Atualiza os campos com os valores da consulta.
                    $("#"+rua_id).val(dados.logradouro);
                    $("#"+bairro_id).val(dados.bairro);
                    $("#"+cidade_id).val(dados.localidade);
                    $("#"+uf_id).val(dados.uf);
                    $("#"+pais_id).val("Brasil");
                    $("#"+ibge_id).val(dados.ibge);
                } //end if.
                else {
                    //CEP pesquisado não foi encontrado.
                    limpaFormularioCep();
                    alert("CEP não encontrado.");
                }
            });
        } //end if.
        else {
            //cep é inválido.
            limpaFormularioCep();
            alert("Formato de CEP inválido.");
        }
    } //end if.
    else {
        //cep sem valor, limpa formulário.
        limpaFormularioCep();
    }
}