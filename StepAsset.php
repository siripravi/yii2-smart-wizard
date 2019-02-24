<?php
namespace is7\smartwizard;

use yii\web\AssetBundle;
/**
 * Asset bundle for the smart wizard files.
 *
 * @author Dmitry Zhukov <dmitry@zhukovs.ru>
 * @since 2.0
 */
class StepAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $depends = [
        '\is7\smartwizard\SmartWizardAsset',
        'yii\web\JqueryAsset'
    ];

    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__.'/assets';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/smartwizard-yii.css'
    ];
}