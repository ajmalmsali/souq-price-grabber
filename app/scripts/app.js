'use strict';

/**
 * @ngdoc overview
 * @name sourPricesAppApp
 * @description
 * # sourPricesAppApp
 *
 * Main module of the application.
 */

  angular
  .module('souqPricesApp', ['ui.materialize'])
  .directive('ngEnter', function () {
    return function (scope, element, attrs) {
	    element.bind('keydown keypress', function (event) {
	      if(event.which === 13) {
	        scope.$apply(function (){
	            scope.$eval(attrs.ngEnter, {$event:event});
	          });
	        event.preventDefault();
	      }
	    });
  };
});