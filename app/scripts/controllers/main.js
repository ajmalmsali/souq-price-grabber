'use strict';

/**
 * @ngdoc function
 * @name sourPricesAppApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the sourPricesAppApp
 */
angular.module('souqPricesApp')
  .controller('MainCtrl', ['$scope', 'Souq', function ($scope, Souq) {
    $scope.input = {
    	searchKeyword : 'iphone'
    };
    $scope.ui = {
    	loading : false
    };

    $scope.getRating = function(starsCount, positiveNegative){
        var arr = [];
        if(positiveNegative === false){
            starsCount = (5 - starsCount);
        }
        starsCount = parseInt(starsCount);

        for (var i=0; i<starsCount; i++) {
            arr.push(i);
        }

        return arr;
    };

    $scope.changeSearch = function(){
    	$scope.ui.loading = true;
        $scope.souq = new Souq();
        $scope.souq.setKeyword($scope.input.searchKeyword);
        $scope.souq.getPrices(false);
    	//console.log($scope.searchKeyword);
    };

    $scope.changeSearch();
  }]);