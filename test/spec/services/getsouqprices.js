'use strict';

describe('Service: GetSouqPrices', function () {

  // load the service's module
  beforeEach(module('sourPricesAppApp'));

  // instantiate service
  var GetSouqPrices;
  beforeEach(inject(function (_GetSouqPrices_) {
    GetSouqPrices = _GetSouqPrices_;
  }));

  it('should do something', function () {
    expect(!!GetSouqPrices).toBe(true);
  });

});
