<?php
namespace is7\smartwizard;

use yii\helpers\BaseInflector;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * \is7\smartwizard\Step.
 *
 * @author Dmitry Zhukov <dmitry@zhukovs.ru>
 * @author kuakling <kuakling@gmail.com>
 * @since 2.0
 */
class Step extends \yii\base\Widget
{

    public $widgetOptions = [];
    
    public $items = [];
    
    /**
     * @var array widget extra buttons
     * [
     *   'submit' => [
     *     'label' => 'Finish',
     *     'icon' => 'glyphicon glyphicon-ok',
     *     'class' => 'btn btn-primary',
     *     'onClick' => 'function() {alert('Click');}',
     *   ]
     */
    public $extraButtons = [];

    /**
     * @var array widget events
     * [
     *   'showStep'  => 'function(e, anchorObject, stepNumber, stepDirection, stepPosition) { console.log('You are on step '+stepNumber+' now'); }',
     *   'leaveStep' => 'function(e, anchorObject, stepNumber, stepDirection) { console.log('Direction: '+stepDirection); }',
     * ]
     */
    public $events = [];

    /**
     * @var boolean|array progress bar options
     */
    public $progress = false;

    /**
     * @var string Yii Form Id
     */
    public $formId = null;

    /**
     * @var array button names array
     */
    private $toolbarExtraButtons = [];

    /**
     * Renders the widget.
     */
    public function run()
    {
        $asset = SmartWizardAsset::register($this->getView());
        StepAsset::register($this->getView());

        $this->prepareProgress();

        if ($this->formId && empty($this->extraButtons)) {
            $this->extraButtons[] = 'submit';
        }
        $this->registerExtraButtons();
        $this->registerEvents();
        $this->registerWidget($asset);
        $this->afterRegisterWidget();
        return $this->renderWidget($asset);
    }

    public function prepareProgress() {
        if ($this->progress === false) {
            return;
        }
        if (!is_array($this->progress)) {
            $this->progress = ['enabled' => boolval($this->progress)];
        }

        if (isset($this->progress['enabled']) && $this->progress['enabled'] === false) {
            return;
        }
        // Otherwise assume that Progress is enabled
        $this->progress['enabled'] = true;

        if (!isset($this->progress['class'])) {
            $this->progress['class'] = '';
        }
        $this->progress['class'] = 'progress-bar '.$this->progress['class'];

        $this->progress['label'] = !isset($this->progress['label']) ? true : boolval($this->progress['label']);
        $this->progress['actual'] = !isset($this->progress['actual']) ? true : boolval($this->progress['actual']);

        if (isset($this->widgetOptions['toolbarSettings']['toolbarButtonPosition'])) {
            switch ($this->widgetOptions['toolbarSettings']['toolbarButtonPosition']) {
                case 'left':
                    $this->progress['position'] = 'right';
                    break;
                case 'right':
                default:
                    $this->progress['position'] = 'left';
                    break;
            }
        }
        else {
            $this->progress['position'] = 'left';
        }

        $this->addEvent('showStep', $this->defaultProgressFunction());
    }
    
    public function renderWidget($asset)
    {
        $render['nav'] = $this->renderNav();
        $render['content'] = $this->renderContent($asset);
        return Html::tag('div', implode("\n", $render), ['id' => $this->id, 'class' => 'sw-main sw-theme-'.$this->getTheme($asset)]);
    }
    
    public function renderNav()
    {
        return Html::ul($this->items, ['item' => function($item, $index) {
            return Html::tag('li', 
                $this->render('_nav-item-default', ['item' => $item, 'index' => $index, 'widget' => $this]),
                ['class' => 'post']
            );
        }, 'class' => 'nav nav-tabs step-anchor']);
    }
    
    public function renderContent($asset)
    {
        $contentArr = [];
        $formStepNum = 0;
        foreach ($this->items as $key => $item) {
            if($this->formId) {
                $item['content'] = Html::tag('div', $item['content'], ['id' => "{$this->id}-form-step-{$formStepNum}"]);
                $formStepNum++;
            }
            $contentArr[] = Html::tag('div', $item['content'], ['id' => "{$this->id}-step-{$key}", 'class' => 'step-content', 'style' => 'display:none']);
        }
        
        return Html::tag('div',implode("\n", $contentArr), ['class' => 'sw-container tab-content']);
    }
    
    public function registerWidget($asset)
    {
        $view = $this->getView();
        if (!empty($this->toolbarExtraButtons)) {
            if (isset($this->widgetOptions['toolbarSettings']['toolbarExtraButtons'])) {
                if (is_string($this->widgetOptions['toolbarSettings']['toolbarExtraButtons'])) {
                    $this->widgetOptions['toolbarSettings']['toolbarExtraButtons'] = new JsExpression($this->widgetOptions['toolbarSettings']['toolbarExtraButtons']);
                }
                $this->widgetOptions['toolbarSettings']['toolbarExtraButtons']->expression .= ".concat([" . implode(', ', $this->toolbarExtraButtons) . "])";
            }
            else {
                $this->widgetOptions['toolbarSettings']['toolbarExtraButtons'] = new JsExpression("[" . implode(', ', $this->toolbarExtraButtons) . "]");
            }
        }
        $jsonOptions = \yii\helpers\Json::encode($this->widgetOptions);
        $view->registerJs("$('#{$this->id}').smartWizard($jsonOptions);");
    }

    public function afterRegisterWidget()
    {
        if ($this->progress && $this->progress['enabled'] === true) {
            $this->registerProgress();
        }
    }

    public function registerExtraButtons() {
        foreach ($this->extraButtons as $buttonName => $buttonOptions) {
            if (is_int($buttonName)) { // array index
                $buttonName = $buttonOptions;
                $buttonOptions = [];
            }

            $buttonOptions['name'] = 'btn'.BaseInflector::camelize($buttonName);
            $buttonOptions['id']   = BaseInflector::camel2id($buttonOptions['name']);
            if (!isset($buttonOptions['label'])) {
                $buttonOptions['label'] = BaseInflector::camel2words($buttonName);
            }
            if (!isset($buttonOptions['class'])) {
                $buttonOptions['class'] = 'btn btn-default';
            }

            switch ($buttonName) {
                case 'submit':
                    if ($this->formId) {
                        if (count($this->items) > 1) {
                            $this->addEvent('showStep', $this->defaultSubmitDisableFunction($buttonOptions['id']));
                        }
                        $this->addEvent('leaveStep',$this->defaultSubmitValidateFunction());
                        if (!isset($buttonOptions['onClick'])) {
                            $buttonOptions['onClick'] = $this->defaultSubmitOnClickFunction();
                        }
                    }
                    break;
                case 'reset':
                    if (!isset($buttonOptions['onClick'])) {
                        $buttonOptions['onClick'] = "function(){ $('#{$this->id}').smartWizard('reset'); }";
                    }
                    break;
            }

            if (isset($buttonOptions['visible'])) {
                $this->addEvent('showStep', $this->buttonVisibleFunction($buttonOptions['id'], $buttonOptions['visible']));
            }

            $this->registerExtraButton($buttonOptions);
        }
    }

    public function registerExtraButton($options) {
        $view = $this->getView();

        if (isset($options['icon'])) {
            $options['label'] = Html::tag('i',null,['class' => $options['icon']]).' '.$options['label'];
        }

        $jQuery   = ["$('<button></button>')"];
        $jQuery[] = "html('{$options['label']}')";
        $jQuery[] = "prop('id','{$options['id']}')";
        $jQuery[] = "addClass('{$options['class']}')";
        if (isset($options['onClick'])) {
            $jQuery[] = "on('click', {$options['onClick']})";
        }
        $view->registerJs("var {$options['name']} = ".implode('.', $jQuery).";");
        $this->toolbarExtraButtons[] = $options['name'];
    }

    public function registerEvents() {
        $view = $this->getView();
        foreach ($this->events as $event => $functions) {
            if (!is_array($functions)) {
                $functions = [ $functions ];
            }
            foreach ($functions as $function) {
                $view->registerJs("$('#{$this->id}').on('{$event}', {$function});");
            }
        }
    }

    public function registerProgress() {
        $view = $this->getView();

        $jsProgress   = ["$('<div>')"];
        $jsProgress[] = "addClass('{$this->progress['class']}')";
        $jsProgress[] = "attr('role', 'progressbar')";
        $jsProgress[] = "attr('aria-valuemin', '0')";
        $jsProgress[] = "attr('aria-valuemax', '100')";
        $jsProgress[] = "attr('aria-valuenow', '0')";
        $jsProgress[] = "css('width', '0%')";
        if ($this->progress['label']) {
            $jsProgress[] = "css('min-width', '2em')";
            $jsProgress[] = "text('0%')";
        }
        else {
            $jsProgress[] = "css('min-width', '1%')";
        }

        $progressClass   = ['btn-group', 'sw-progressbar'];
        $progressClass[] = 'pull-'.$this->progress['position']; // BS 3.x
        $progressClass[] = 'align-self-center'; // BS 4.x
        switch ($this->progress['position']) {
            case 'left':
                $jsMethod = 'prepend';
                $progressClass[] = 'mr-auto'; // BS 4.x
                break;
            case 'right':
                $jsMethod = 'append';
                $progressClass[] = 'ml-auto'; //BS 4.x
                break;
            default:
                $jsMethod = 'append';
        }

        $js = "$('.sw-toolbar').$jsMethod($('<div>').addClass('".implode(' ', $progressClass)."').append($('<div>').addClass('progress').append(".implode('.', $jsProgress).")));";

        $view->registerJs($js);
    }

    public function addEvent($event, $function) {
        if (isset($this->events[$event]) && !is_array($this->events[$event])) {
            $this->events[$event] = [$this->events[$event]];
        }
        $this->events[$event][] = $function;
    }

    public function getTheme($asset)
    {
        $view = $this->getView();
        $theme = 'default';
        $cssFile = 'smart_wizard.css';
        if(isset($this->widgetOptions['theme'])){
            $requestTheme = $this->widgetOptions['theme'];
            switch ($requestTheme) {
                case 'arrows' : $cssFile = 'smart_wizard_theme_arrows.css'; $theme = $requestTheme;
                    break;
                case 'circles' : $cssFile = 'smart_wizard_theme_circles.css'; $theme = $requestTheme;
                    break;
                case 'dots' : $cssFile = 'smart_wizard_theme_dots.css'; $theme = $requestTheme;
                    break;
                default : $cssFile = 'smart_wizard.css'; $theme = 'default';
                    break;
            }
        }
        $view->registerCssFile($asset->baseUrl.'/css/'.$cssFile, ['depends' => ['\is7\smartwizard\SmartWizardAsset']]);
        
        return $theme;
    }

    private function defaultSubmitOnClickFunction() {
        return <<<JS
function() {
    $.each($('.step-content'), function() {
        if ($(this).find('.has-error').length) {
            $('a[href="#'+$(this).prop('id')+'"]').addClass('error');
        }
    });
}
JS;
    }

    private function defaultSubmitDisableFunction($buttonId) {
        return <<<JS
function(e, anchorObject, stepNumber, stepDirection, stepPosition) {
    $('#{$this->formId}').yiiActiveForm("resetForm");
    if(stepPosition === 'first') {
        $('#{$buttonId}').attr('disabled', 'disabled');
    } else if(stepPosition === 'final'){
        $('#{$buttonId}').removeAttr('disabled');
    } else {
        $('#{$buttonId}').attr('disabled', 'disabled');
    }
}
JS;
    }

    private function defaultSubmitValidateFunction() {
        return <<<JS
function(e, anchorObject, stepNumber, stepDirection) {
    var elmForm = $('#{$this->id}-form-step-' + stepNumber);
    if(stepDirection === 'forward' && elmForm){
        var inputs = elmForm.find('*[id]:visible').map(function() { return this.id.replace(/select2-(\S+)-container/i,'$1'); }).get();
        data = $('#{$this->formId}').data("yiiActiveForm");
        $.each(data.attributes, function(i, item) {
            if ($.inArray(item.id, inputs) > -1) {
                this.status = 3;
            }
        });
        $('#{$this->formId}').yiiActiveForm("validate");
        if (elmForm.find(".has-error").not(".error-summary").length) {
            elmForm.find(".error-summary").removeClass('hidden').show();
            $(anchorObject).parent("li").addClass("danger");
            return false;
        }
    }
    return true;
}
JS;
    }

    private function defaultProgressFunction() {
        $countItems = count($this->items);
        $updateLabel = json_encode($this->progress['label']);
        $actualPercentage = json_encode($this->progress['actual']);
        return <<<JS
function(e, anchorObject, stepNumber, stepDirection, stepPosition) {
    $(document).ready(function(){
        var percent = 0;
        if (!{$actualPercentage}) { percent = Math.round(100*(stepNumber+1)/{$countItems}); }
        else { percent = Math.round(100*stepNumber/{$countItems}); }
        var progress = $('.sw-progressbar > .progress > .progress-bar'); 
        progress.attr('aria-valuenow', percent).css('width', percent + '%');
        if ({$updateLabel}) {
            progress.text(percent + '%');
        }
    });
}
JS;
    }

    private function buttonVisibleFunction($buttonId, $visible) {
        if (!is_array($visible)) {
            $visible = [$visible];
        }

        $js = [];
        $if = [];

        foreach ($visible as $value) {
            if ($value == 'final' && count($this->items) == 1) {
                $value = 'first'; // If only one Step, 'final' will be the 'first'
            }
            $if[] = 'if ('.( is_numeric($value) ? 'stepNumber' : 'stepPosition' ).' === '.( is_numeric($value) ? $value : "'$value'" ).') { $("#'.$buttonId.'").show(); }';
        }

        if (!empty($if)) {
            $js[] = 'function(e, anchorObject, stepNumber, stepDirection, stepPosition) {';
            $js[] = implode(' else ', $if);
            $js[] = 'else { $("#'.$buttonId.'").hide(); }';
            $js[] = '}';
        }

        return implode("\n", $js);
    }
}
