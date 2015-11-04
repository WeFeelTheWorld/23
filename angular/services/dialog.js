(function(){
	"use strict";

	angular.module("app.services").factory('DialogService', function($mdDialog){

		return {
			fromTemplate: function(template, $scope, fn){

				var options = {
					templateUrl: '/views/dialogs/' + template + '/' + template + '.html'
				};

				if ($scope){
					$scope.doFunction = fn;
					options.scope = $scope.$new();

				}

				return $mdDialog.show(options);
			},

			hide: function(){
				return $mdDialog.hide();
			},

			alert: function(title, content){
				$mdDialog.show(
					$mdDialog.alert()
						.title(title)
						.content(content)
						.ok('Ok')
				);
			}
		};
	});
})();
