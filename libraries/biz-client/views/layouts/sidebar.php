<?php

use yii\web\View;
use dee\adminlte\SideNav;

//use yii\helpers\Html;

/* @var $this View */

$items = [];
?>
<aside class="main-sidebar">
    
    <section class="sidebar">
        <?php
        echo SideNav::widget([
            'options' => [
                'class' => 'sidebar-menu',
                'items' => $items,
            ]
        ]);
        ?>
    </section>
</aside>
