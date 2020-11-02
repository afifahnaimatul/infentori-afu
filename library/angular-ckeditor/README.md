# ng-ckeditor5

> ckeditor5 wrapper for angularjs

```bash
> npm install --save @vleesbrood/ng-ckeditor
# or
> yarn add @vleesbrood/ng-ckeditor5
```

## example

```javascript
import angular from 'angular';
import ckeditor from '@vleesbrood/ng-ckeditor5';
// add translations
import '@ckeditor/ckeditor5-build-classic/build/translations/nl.js';

angular.module('application', [
  ckeditor
]).controller('controller', ($scope) => {
  $scope.config = {
    language: 'nl'
  };

  $scope.toolbar = [ 'blockquote' ];
  $scope.replaceToolbar = true;
});
```

```html
<html>
  <head>
    <meta charset="utf-8">
    <title>ng-ckeditor example</title>
  </head>

    <body ng-app="app" ng-controller="controller">
    <textarea id="editor"
              ckeditor5
              config="config"
              toolbar="toolbar"
              replace-toolbar="replaceToolbar"
              ng-model="blaat"></textarea>
  </body>
</html>
```

## configuration

TBD

## API

Check [the ckeditor5 docs](https://docs.ckeditor.com/ckeditor5/latest/api/index.html) to see the supported API.

## Roadmap

Check [the ckeditor5 roadmap](https://github.com/ckeditor/ckeditor5-design/issues/172) to see what's coming.
