<?php

/* @var $this yii\web\View */

use yii\bootstrap\Button;
use yii\bootstrap\Html;
use yii\web\JsExpression;
use app\assets\OpenlayersAsset;

OpenlayersAsset::register($this);

$this->title = 'My Yii Application';
?>
<div class="site-index">
<script>
function btnExportMapEvent() {
  map.once('postcompose', function(event) {
	  var canvas = event.context.canvas;
	  var link = document.getElementById('image-download');
	  link.href = canvas.toDataURL('image/png');
	  link.click();
  });
  map.renderSync();
}
</script>
	
	<div class="container">
	    <div class="row">
			<div id="mapMain" class="map"></div>
			<script type="text/javascript">
			var map = new ol.Map({
			  layers: [
				new ol.layer.Tile({
				  source: new ol.source.OSM()
				}),
				new ol.layer.Tile({
					source: new ol.source.XYZ({
						url: document.location.origin.concat('/img/tileset/full/{z}/{x}/{y}.png'),
					})
				})
			  ],
			  target: 'mapMain',
			  view: new ol.View({
				center: ol.extent.getCenter(ol.proj.transformExtent(
					[81.75, 50.916666667, 81.875, 51.000], 'EPSG:4326', 'EPSG:3857')),
				zoom: 6
			  })
			});
			</script>
		</div>
		<div class="row">
		    <?= Button::widget([
			    'id' => 'btnExportMap',
			    'label' => Html::icon('download') . Html::encode('Скачать в PNG'),
				'encodeLabel' => false,
				'options' => [
				    'class' => 'btn-primary',
					'onClick' => "js:btnExportMapEvent()"
				],
			]); ?>
			<a id="image-download" download="map.png"></a>
		</div>
	</div>

    <div class="body-content">

    </div>
</div>


