(function() {
	"use strict";

	angular.module('app.controllers').controller('EpiCtrl', function($scope, Restangular, leafletData, MapService) {
		$scope.current = "";
		$scope.menueOpen = true;
		$scope.closeIcon = 'chevron_left';
		$scope.labels = ["January", "February", "March", "April", "May", "June", "July"];
		$scope.series = ['Series A', 'Series B'];
		$scope.data = [
			[65, 59, 80, 81, 56, 55, 40],
			[28, 48, 40, 19, 86, 27, 90]
		];
		var epi = Restangular.all('epi');
		epi.getList().then(function(data) {
			$scope.epi = data;
			$scope.drawCountries();
			Restangular.oneUrl('restCountries', 'https://restcountries.eu/rest/v1/all').get().then(function(restCountries) {
				angular.forEach($scope.epi, function(nation) {
					angular.forEach(restCountries, function(country) {
						if (nation.country == country.name) {
							angular.extend(nation, country);
						}
					});
				});
			});
		});
		$scope.toggleOpen = function() {
			$scope.menueOpen = !$scope.menueOpen;
			$scope.closeIcon = $scope.menueOpen == true ? 'chevron_left' : 'chevron_right';
		}
		$scope.setCurrent = function(nat) {
			$scope.current = nat;
		};
		$scope.getOffset = function() {
			if (!$scope.current) {
				return 0;
			}
			return ($scope.current.rank == 1 ? 0 : $scope.current.rank == $scope.current.length + 1 ? $scope.current.rank : $scope.current.rank - 2) * 16;
			//return $scope.current.rank - 2 || 0;
		};
		$scope.getTendency = function() {
			if (!$scope.current) {
				return 'arrow_drop_down'
			}
			return $scope.current.percent_change > 0 ? 'arrow_drop_up' : 'arrow_drop_down';
		};
		var getNationByName = function(name) {
			var nation = {};
			angular.forEach($scope.epi, function(nat) {
				if (nat.country == name) {
					nation = nat;
				}
			});
			return nation;
		};
		var createCanvas = function (colors) {
			$scope.canvas = document.createElement('canvas');
			$scope.canvas.width = 256;
			$scope.canvas.height = 10;
			$scope.ctx = $scope.canvas.getContext('2d');
			var gradient = $scope.ctx.createLinearGradient(0, 0, 256, 10);
			gradient.addColorStop(0, 'rgba(255,255,255,0)');
			gradient.addColorStop(0.53, 'rgba(128, 243, 198,1)');
			gradient.addColorStop(1, 'rgba(102,102,102,1)');
			$scope.ctx.fillStyle = gradient;
			$scope.ctx.fillRect(0, 0, 256, 10);
			$scope.palette = $scope.ctx.getImageData(0, 0, 256, 1).data;
			document.getElementsByTagName('body')[0].appendChild($scope.canvas);
		}
		createCanvas();


		$scope.drawCountries = function() {
			leafletData.getMap('map').then(function(map) {
				//	L.tileLayer('http://localhost:3001/services/postgis/countries_big/geom/dynamicMap/{z}/{x}/{y}.png').addTo(map);
				var debug = {};
				var apiKey = 'pk.eyJ1IjoibWFnbm9sbyIsImEiOiJuSFdUYkg4In0.5HOykKk0pNP1N3isfPQGTQ';
				var url = 'http://localhost:3001/services/postgis/countries_big/geom/vector-tiles/{z}/{x}/{y}.pbf?fields=id,admin,adm0_a3,name,name_long'; //
				var url2 = 'https://a.tiles.mapbox.com/v4/mapbox.mapbox-streets-v6-dev/{z}/{x}/{y}.vector.pbf?access_token=' + apiKey;
					var mvtSource = new L.TileLayer.MVTSource({
						url: url, //"http://spatialserver.spatialdev.com/services/vector-tiles/gaul_fsp_india/{z}/{x}/{y}.pbf",
						debug: false,
						clickableLayers: ['countries_big_geom'],
						mutexToggle: true,
						onClick: function (evt, t) {
							//map.fitBounds(evt.target.getBounds());
							if($scope.current.country != evt.feature.properties.admin){
								map.panTo(evt.latlng);
								map.panBy(new L.Point(-200,0));
							}
							$scope.current = getNationByName(evt.feature.properties.admin);
						},
						getIDForLayerFeature: function (feature) {

							return feature.properties.id;
						},
						filter: function (feature, context) {

							if (feature.layer.name === 'admin' || feature.layer.name === 'gaul_2014_adm1_label') {
								//console.log(feature);
								if (feature.properties.admin_level == 0 || feature.properties.admin_level == 1 || feature.properties.admin_level == 2) {
									return true;
								} else {
									return false;
								}

							}
							return true;
						},

						style: function (feature) {
							var style = {};
							var nation = getNationByName(feature.properties.admin);
							var type = feature.type;
							switch (type) {
							case 1: //'Point'
								style.color = 'rgba(49,79,79,0.01)';
								style.radius = 5;
								style.selected = {
									color: 'rgba(255,255,0,0.5)',
									radius: 0
								};
								break;
							case 2: //'LineString'
								style.color = 'rgba(255,0,0,1)';
								style.size = 1;
								style.selected = {
									color: 'rgba(255,25,0,1)',
									size: 2
								};
								break;
							case 3: //'Polygon'
								if (nation.score) {
									var colorPos = parseInt(256 / 100 * nation.score) * 4;
									var color = 'rgba(' + $scope.palette[colorPos] + ', ' + $scope.palette[colorPos + 1] + ', ' + $scope.palette[colorPos + 2] + ',' + $scope.palette[colorPos +3] + ')';
									style.color = color;
									style.outline = {
										color: 'rgba(50,50,50,0.4)',
										size: 1
									};
									style.selected = {
										color: 'rgba(255,255,255,0.0)',
										outline: {
											color: 'rgba(0,0,0,0.5)',
											size: 1
										}
									};
									break;
								}
								else{
									style.color = 'rgba(255,255,255,0)';
									style.outline = {
										color: 'rgba(255,255,255,0)',
										size: 1
									};
								}
							}

							//	if (feature.layer.name === 'gaul_2014_adm1_label') {
							style.ajaxSource = function (mvtFeature) {
								var id = mvtFeature.id;
								//	console.log(id);
								//return 'http://spatialserver.spatialdev.com/fsp/2014/fsp/aggregations-no-name/' + id + '.json';
							};

							style.staticLabel = function (mvtFeature, ajaxData) {
								var style = {
									html: ajaxData.total_count,
									iconSize: [33, 33],
									cssClass: 'label-icon-number',
									cssSelectedClass: 'label-icon-number-selected'
								};
								return style;
							};
							//	}

							return style;
						},


						layerLink: function (layerName) {
							if (layerName.indexOf('_label') > -1) {
								return layerName.replace('_label', '');
							}
							return layerName + '_label';
						}

					});
					debug.mvtSource = mvtSource;
					map.addLayer(mvtSource);
			});
		};
	});
})();
