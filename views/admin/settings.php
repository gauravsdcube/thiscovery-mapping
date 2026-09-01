<?php

use humhub\modules\thiscoveryMapping\assets\MappingFormAsset;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\ModuleSettings;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var ModuleSettings $model */

MappingFormAsset::register($this);

$this->title = Yii::t('ThiscoveryMappingModule.base', 'Thiscovery Mapping');
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <?= Yii::t('ThiscoveryMappingModule.base', '<strong>Thiscovery Mapping</strong> configuration') ?>
        <span class="pull-right">
            <a href="<?= Html::encode(Url::toHelp(null, 'admins')) ?>">
                <i class="fa fa-question-circle" aria-hidden="true"></i>
                <?= Yii::t('ThiscoveryMappingModule.base', 'Help') ?>
            </a>
        </span>
    </div>
    <div class="panel-body">
        <p class="help-block">
            <?= Yii::t('ThiscoveryMappingModule.base', 'Basemaps default to Stadia Maps EU servers (Frankfurt and Paris). Restrict the API key by HTTP referrer in the Stadia dashboard. Place search is proxied through this site so the key is not required in the browser.') ?>
        </p>
        <?php if (trim((string)$model->apiKey) === '' && $model->provider === \humhub\modules\thiscoveryMapping\Module::PROVIDER_STADIA): ?>
            <div class="alert alert-danger">
                <?= Yii::t('ThiscoveryMappingModule.base', 'No Stadia API key is saved. Maps will show a 401 authentication error until you paste a key here.') ?>
            </div>
        <?php endif; ?>
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'provider')->dropDownList(ModuleSettings::providerLabels()) ?>
        <?= $form->field($model, 'apiKey')->passwordInput(['autocomplete' => 'off']) ?>
        <?= $form->field($model, 'style')->dropDownList(ModuleSettings::styleLabels()) ?>
        <?= $form->field($model, 'customTileUrl')->textInput(['placeholder' => 'https://…/{z}/{x}/{y}.png']) ?>
        <?= $form->field($model, 'attribution')->textarea(['rows' => 2]) ?>
        <?= $form->field($model, 'geocoder')->dropDownList(ModuleSettings::geocoderLabels()) ?>
        <div data-tm-place-wrap>
            <?= $this->render('../map/_place_search') ?>
            <?= $this->render('../map/_preview_map', ['map' => null]) ?>
            <div class="row">
                <div class="col-md-4"><?= $form->field($model, 'centerLat')->textInput(['data-tm-lat' => '1']) ?></div>
                <div class="col-md-4"><?= $form->field($model, 'centerLng')->textInput(['data-tm-lng' => '1']) ?></div>
                <div class="col-md-4"><?= $form->field($model, 'zoom')->textInput(['data-tm-zoom' => '1']) ?></div>
            </div>
        </div>
        <?= Html::submitButton(Yii::t('ThiscoveryMappingModule.base', 'Save'), ['class' => 'btn btn-primary']) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
