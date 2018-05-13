<?php

echo $this->Html->docType();

echo $this->Html->tag('html', null, ['lang' => 'pt-br']);

    echo $this->element('head');

    echo $this->Html->tag('body');

        echo $this->element('header');

        echo $this->Html->tag('main', NULL, ['class' => 'container-fluid theme-showcase', 'role' => 'main', 'id' => 'container-render']);

            echo $this->Html->getCrumbList(
                [
                    'firstClass' => false,
                    'lastClass' => 'active',
                    'class' => 'breadcrumb'
                ]
            );

            echo $this->Flash->render();

            echo $this->fetch('content');

        echo $this->Html->tag('/main');


    echo $this->Html->tag('/body');

echo $this->Html->tag('/html');

?>

<script>
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();

        headerHeight();

        $('.dropdown-submenu a.dropdown-link').on("click", function(e){

            $parent = $(this).parent();

            $u = $(this);

            $nav = $('div#navbarCollapse');

            $dropdowns = $nav.find('ul.dropdown-menu');

            var fathers = [];
            var is_father;

            while(true){

                $parent = $parent.parent();

                if($parent.attr('class') === 'dropdown nav-item dropdown-item show')
                    break;

                if($parent.prop("tagName") === 'UL')
                    fathers.push($parent);
                
            }

            $dropdowns.each(function(){

                is_father = false;

                $ul = $(this);

                $.each(fathers, function(item, father){

                    if( $ul.is(father) )
                        is_father = true;

                });

                if(!is_father && $ul.css('display') == 'block')
                    $ul.toggle();

            });

            $u.next('ul').toggle();

            e.stopPropagation();
            e.preventDefault();
        });
    });

    function headerHeight() {
        $("header").css('height', $(".navbar").css('height'));
    };

    var resizeTimer;
    $(window).resize(function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(headerHeight, 100);
    });
</script>
