'use strict';

/**
 * @ngdoc service
 * @name sourPricesAppApp.GetSouqPrices
 * @description
 * # GetSouqPrices
 * Service in the sourPricesAppApp.
 */
angular.module('souqPricesApp')
  .service('Souq', ['$http', function ($http) {
    // AngularJS will instantiate a singleton by calling "new" on this function
    var Souq = function() {
      this.products = [];
      this.busy = false;
      this.page = 1;
      this.keyword = 'iphone';
      this.sortBy = 'price_discounted';
      this.orderBy = 'ASC';
    };

    Souq.prototype.setKeyword = function(keyword){
      this.keyword = keyword;
    };

    Souq.prototype.productsInDom = function(){
      return (this.products.length !== 0);
    };

    Souq.prototype.getPrices = function(paginate) {
      if (this.busy) {
      	return;
      }
      this.busy = true;

      if(!paginate){
    	 this.page = 1;
       this.products = [];
      }else{
        this.page = this.page + 1;
      }

      var url = 'http://souq.infohe.com/api.php?s=' + this.keyword + '&sort=' + this.sortBy + '&order=' + this.orderBy + '&page=' + this.page;
	     
      var souq = this;

	    $http.get(url).success(function(data) {
        
	      var products = data.response;

	      for (var i = 0; i < products.length; i++) {
	        souq.products.push(products[i]);
	      }
	      souq.busy = false;
	    });

  	};
    return Souq;
  }]);