# yii2-smart-wizard

## Basic
~~~ php
<?php
use is7\smartwizard\Step;

echo Step::widget([
    'widgetOptions' => [
        'theme' => 'default',
        'showStepURLhash' => false,
        'autoAdjustHeight' => false,
    ],
    'items' => [
        1 => [
            'icon' => 'glyphicon glyphicon-user',
            'label' => 'Step - 1',
            'content' => 'Content 1'
        ],
        2 => [            
            'label' => 'Step - 2',
            'content' => '<h2>Content 2 </h2>'
        ],
    ],
]);
~~~

## Advanced
~~~ php
<?php
use is7\smartwizard\Step;

echo Step::widget([
    'widgetOptions' => [
        'theme' => 'default',
        'showStepURLhash' => false,
        'autoAdjustHeight' => false,
    ],
    'extraButtons' => [
        'reset', // Reset Wizard
        'button' => [
            'icon' => 'glyphicon glyphicon-ok',
            'label' => 'Click',
            'class' => 'btn btn-primary',
            'onClick' => 'function() { alert("Clicked!"); }',
        ],
    ],
    'items' => [
        1 => [
            'icon' => 'glyphicon glyphicon-user',
            'label' => 'Step - 1',
            'content' => 'Content 1'
        ],
        2 => [            
            'label' => 'Step - 2',
            'content' => '<h2>Content 2 </h2>'
        ],
    ],
]);
~~~

## Form Validate
~~~ php
<?php $form = ActiveForm::begin([
    'enableClientValidation' => true,
]); 

echo Step::widget([
    'formId' => $form->id,
    'extraButtons' => [
        'submit',
        'reset',
    ],
    'widgetOptions' => [
        'theme' => 'default',
        'showStepURLhash' => false,
        'autoAdjustHeight' => false,        
    ],
    'items' => [
        1 => [
            'icon' => 'glyphicon glyphicon-info-sign',
            'label' => 'Step - 1 <br /><small>Information</small>',
            'content' => $this->render('_step-1', ['models' => $models, 'form' => $form])
        ],
        2 => [
            'icon' => 'glyphicon glyphicon-picture',
            'label' => 'Step - 2 <br /><small>Photo</small>',
            'content' => $this->render('_step-2', ['models' => $models, 'form' => $form])
        ],
        3 => [            
            'label' => 'Step - 3 <br /><small>Address</small>',
            'content' => $this->render('_step-3', ['models' => $models, 'form' => $form])
        ],
    ],
]);
?>

<?php ActiveForm::end(); ?>
~~~
