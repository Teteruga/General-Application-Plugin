<?php

namespace GeneralApplicationUtilities\View\Helper;

use Cake\View\Helper;

class UtilHelper extends Helper {

    public $helpers = ['Html', 'Form', 'Paginator'];

    public function fixDate($string) {

        $patterns = array('/(19|20)(\d{2})(\d{1,2})(\d{1,2})/');
        $replace = array('\4/\3/\1\2');
        $value = preg_replace($patterns, $replace, $string);

        return $value;
    }

    public function arrayToList($array) {

        $array = $array = json_decode(json_encode($array), true);

        $newArray = [];
        foreach ($array as $key => $value) {

            if (is_array($value)) {
                $newArray[$key] = $this->arrayToList($value);
            } else {
                $newArray[] = $key . ' : ' . $value;
            }
        }

        return $newArray;
    }

    public function nestedList($array) {

        return $this->Html->nestedList($this->arrayToList($array));
    }

    public function formataCnj($cnjNaoFormatado) {

        if (strlen($cnjNaoFormatado) == 20) {

            return substr($cnjNaoFormatado, 0, 7) .
                    '-' .
                    substr($cnjNaoFormatado, 7, 2) .
                    '.' .
                    substr($cnjNaoFormatado, 9, 4) .
                    '.' .
                    substr($cnjNaoFormatado, 13, 1) .
                    '.' .
                    substr($cnjNaoFormatado, 14, 2) .
                    '.' .
                    substr($cnjNaoFormatado, 16, 4)
            ;
        } else {
            return "-";
        }
    }

    public function simNao($value, $showNo = true) {
        return ($value) ? "Sim" : (($showNo) ? "Não" : "");
    }

    public function integerToRoman($integer) {

        // Convert the integer into an integer (just to make sure)
        $integer = intval($integer);
        $result = '';

        // Create a lookup array that contains all of the Roman numerals.
        $lookup = array('M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1);

        foreach ($lookup as $roman => $value) {
            // Determine the number of matches
            $matches = intval($integer / $value);

            // Add the same number of characters to the string
            $result .= str_repeat($roman, $matches);

            // Set the integer to be the remainder of the integer and the value
            $integer = $integer % $value;
        }

        // The Roman numeral should be built, return it
        return $result;
    }

    /**
     * Função numeroCNJ, para validar Numeração Única de Processos
     * estabelecida na Res. 65 do Conselho Nacional de Justiça, que
     * determina a adoção do seguinte formato para números de processos:
     * NNNNNNNN-DD.AAAA.JTR.OOOO, onde
     * NNNNNNN => Corresponde ao número sequencial do processo no ano do ajuizamento
     * DD => Corresponde aos dígitos de verificação
     * AAAA => Corresponde ao ano de ajuizamento da ação/processo
     * JTR => Corresponde aos números que identificam o Ramo e a Região da Justiça
     * OOOO => Corresponde ao número que identifica o juízo a que distribuída a ação
     * O cálculo utiliza o algoritmo do Módulo 97 Base 10, conforme especificação da Norma
     * ISO 7064:2003
     * @param $numero é uma string com o número a ser validado
     * @Author Maurício Schmidt Bastos
     * a função retorna verdadeiro em caso de sucesso e falso quando o teste falha
     * ====================================================================================
     * Permitida a cópia para uso NÃO COMERCIAL, mediante referência à fonte (www.mauricio.bastos.nom.br) e ao autor
     */
    public function validaCNJ($numero) {
        //Remove, se houver, os caracteres não numéricos
        // da entrada do número do processo
        $num = $this->numeroLimpo($numero, 20);

        //extrai o dígito verificador do número limpo
        $numerosemdigito = substr_replace($num, '', 7, 2);

        //Prepara o número para o cálculo do dígito,
        //colocando zeros no fim do número sem o dígito
        $numparacalculo = $numerosemdigito . "00";

        //Prepara o número para conferência do dígito
        //Pega dígito original
        $digitooriginal = substr($num, 7, 2);
        //colocando o dígito informado no fim do número s/ dígito
        $numparaconferir = $numerosemdigito . $digitooriginal;

        //divide o $numeroparacalculo para reduzir complexidade do cálculo
        $nnnnnnn = substr($numparacalculo, 0, 7);
        $ajtr = substr($numparacalculo, 7, -6);
        $oooo00 = substr($numparacalculo, 14, 6);

        /* Fórmula CNJ primeira etapa
         * R1=(NNNNNNN mod 97)
         */
        $r1 = round($nnnnnnn % 97, 2) >= 50 ? round($nnnnnnn % 97, 2) : round($nnnnnnn % 97, 2, PHP_ROUND_HALF_DOWN);
        //garante que $r1 tenha dois dígitos, preenchendo com zero à esquerda, se necessário
        $r1 = $this->numeroLimpo(substr($r1, 0, 2), 2);

        /* Fórmula CNJ segunda etapa
         * R2=((R1 concatenado com AAAAJTR) mod 97)
         */
        //concatena $r1 com AAAAJTR
        $r2 = ($r1 .= $ajtr);
        $r2 = round($r2 % 97, 2) >= 50 ? round($r2 % 97, 2) : round($r2 % 97, 2, PHP_ROUND_HALF_DOWN);
        //garante que $r2 tenha dois dígitos, preenchendo com zero à esquerda, se necessário
        $r2 = $this->numeroLimpo(substr($r2, 0, 2), 2);

        /* Fórmula CNJ terceira etapa
         * R3=((R2 concatenado com OOOO00) mod 97)
         */
        //concatena $r2 com OOOO00
        $r3 = ($r2 .= $oooo00);
        $r3 = round($r3 % 97, 2) >= 50 ? round($r3 % 97, 2) : round($r3 % 97, 2, PHP_ROUND_HALF_DOWN);
        //garante que $r3 tenha dois dígitos, preenchendo com zero à esquerda, se necessário
        $r3 = $this->numeroLimpo(substr($r3, 0, 2), 2);

        /* Fórmula CNJ quarta etapa
         * DD = 98 - (R3 mod 97)
         */
        $d1d0 = 98 - ($r3 % 97);
        $d1d0 = $this->numeroLimpo(substr($d1d0, 0, 2), 2);

        //Compara dígito calculado com informado
        $resultado = $digitooriginal == $d1d0 ? TRUE : FALSE;
        return $resultado;
    }
    
    public function numeroLimpo(string $numero, int $comprimento = 20){
        
        $numero = trim($numero);
        
        $numero = preg_replace('/\D/', '', $numero);
        
        $numero = str_pad($numero, $comprimento, "0", STR_PAD_LEFT);
        
        return $numero;
        
    }

    /**
     *
     *
     */
    public function findArrayPathToKey($needle, Array $haystack, &$path = NULL){

        $found = false;

        foreach($haystack as $key => $stack){

            if($key === $needle){
                $path[] = $key;
                $found = true;
                return $path;
            }
            else if(is_array($stack)){
                $path[] = $key;
                $path_return = $this->findArrayPathToKey($needle, $stack, $path, false);

                if(array_search($needle, $path_return))
                    return $path_return;
            }
            
        }

        if(!$found)
            array_pop($path);

        return $path;
    }

    /**
     *
     *
     */
    public function humanizeOnUppercase($name, $spacer = ' '){

        $pieces = preg_split('/(?=[A-Z])/', $name);

        if($pieces[0] == '')
            unset($pieces[0]);

        $name_humanize = implode($spacer, $pieces);

        return $name_humanize;
    }



}
