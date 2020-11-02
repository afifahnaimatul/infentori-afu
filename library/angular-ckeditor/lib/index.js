import classicEditor from '@ckeditor/ckeditor5-build-classic';

const NAMESPACE = 'vleesbrood/ckeditor5';

/* eslint-disable no-undef */
const module = angular.module(NAMESPACE, []);
/* eslint-enable no-undef */

module.directive('ckeditor5', editorDirective);

function editorDirective () {
  return {
    restrict: 'A',
    require: 'ngModel',
    scope: {
      config: '=',
      plugins: '=',
      removePlugins: '=',
      replaceConfig: '=',
      replaceToolbar: '=',
      replacePlugins: '=',
      toolbar: '='
    },
    link: (scope, element, attrs, ngModel) => {
      'ngInject';
      const constructConfigArray = (customItems, defaultItems, replace) => {
        if (!customItems) return defaultItems;
        if (customItems && replace) return customItems;
        return defaultItems.concat(customItems);
      };

      const constructConfig = () => {
        const { config, plugins, removePlugins, replaceConfig, replacePlugins, replaceToolbar, toolbar } = scope;

        if (!config && !toolbar && !plugins) return;
        if (config && replaceConfig) return config;

        // construct the plugins, if replacePlugins is truthy, we only provide the given plugins
        const defaultPlugins = classicEditor.build.plugins.map(plugin => plugin.pluginName);
        let customPlugins = constructConfigArray(plugins, defaultPlugins, replacePlugins);

        // construct the toolbar, if replaceToolbar is truthy, we only provide the given toolbar config
        const defaultToolbar = classicEditor.build.config.toolbar.items;
        let customToolbar = constructConfigArray(toolbar, defaultToolbar, replaceToolbar);

        return Object.assign({}, { 
          plugins: customPlugins,
          removePlugins,
          toolbar: customToolbar
        }, config);
      };

      const isTextarea = element[0].tagName.toLowerCase() === 'textarea';
      if (!isTextarea) {
        throw new Error('element is not a textarea');
      }

      classicEditor.create(
        element[0],
        constructConfig()
      ).then((instance) => {
        const setData = () => {
          const data = instance.getData();
          const value = ngModel.$viewValue;

          if (data !== value) {
            instance.setData(value);
          }
        };

        const setModel = () => {
          const data = instance.getData();
          ngModel.$setViewValue(data);
          ngModel.$render();
        };

        scope.$watch(() => ngModel.$viewValue, setData);
        instance.model.document.on('change', setModel);
      });
    }
  };
}

export default NAMESPACE;