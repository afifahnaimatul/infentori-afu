app.controller('migrasiDataCtrl', function ($scope, Data, $rootScope) {
  var control_link = "migrasi";
  $scope.form = {};
  $scope.form.chunks = 20;
  $scope.form.bulan_pembelian = new Date();

  $scope.migrasiPembelian = function (params) {
    var konfirmasi = `Apakah Anda ingin melakukan migrasi pembelian?`;
    if( !confirm(konfirmasi) ){
      return;
    }

    Data.get(control_link + '/migrasiGetPembelian', params).then(function (result) {
      if (result.status_code == 200) {
        console.log(result);
        $scope.listPembelian        = result.data.list;
        $scope.listPembelianTotal   = result.data.dataLength;
        $scope.urutan_pembelian     = 0;
        $scope.progres_pembelian    = 0;

        angular.forEach($scope.listPembelian, function(isi, key){
          // Migrasi Data per Batch
          $scope.doMigrasi(isi, params.reff_type);
        });
      } else {
        $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
      }
    });
  };

  $scope.doMigrasi = async function(list, type){
    var param = {
      list_id   : list,
      reff_type : type,
    }
    await Data.post(control_link + '/migrasiJurnalPembelian', param).then(function (result) {
        if (result.status_code == 200) {
          $scope.urutan++;
          $scope.hitungProgres();
        } else {
            $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
        }
    });
  };

  $scope.hitungProgres = function(){
    $scope.progres_pembelian = parseInt($scope.urutan) / parseInt($scope.listPembelianTotal) * 100;
  };

  /** save action */
  $scope.save = function () {
      console.log($scope.displayed);
      Data.post(control_link + '/save', $scope.displayed).then(function (result) {
          if (result.status_code == 200) {
              $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
          } else {
              $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
          }
      });
  };

});
