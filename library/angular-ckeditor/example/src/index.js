// just import it from dist-folder
import angular from 'angular';
import ckeditor from '../../dist/ng-ckeditor5';
import '@ckeditor/ckeditor5-build-classic/build/translations/nl.js';

angular.module('app', [
  ckeditor
]).controller('controller', ($scope) => {
  $scope.toolbar = [ 'blockquote' ];
  $scope.replaceToolbar = false;
  $scope.config = {
    language: 'nl'
  };
});