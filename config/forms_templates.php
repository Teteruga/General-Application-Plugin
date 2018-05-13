<?php

return [
    'inputContainer' => '<div class="input form-group {{type}}{{required}}">{{content}}</div>',
    'input' => '<input class="input-sm form-control" type="{{type}}" name="{{name}}"{{attrs}}/>',
    'textarea' => '<textarea class="input-sm form-control" name="{{name}}"{{attrs}}>{{value}}</textarea>',
    'select' => '<select class="input-sm form-control" name="{{name}}"{{attrs}}>{{content}}</select>',
    'dateWidget' => '{{day}}{{month}}{{year}}{{hour}}{{minute}}{{second}}{{meridian}}', 
    'error' => '<div class="error-message alert-warning" style="padding: 10px;"> <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> {{content}}</div>',
];