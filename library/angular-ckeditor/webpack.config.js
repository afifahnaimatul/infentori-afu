const path = require('path');

const SRC = './lib';
const DIST = './dist';

module.exports = {
  devServer: {
    contentBase: path.join(__dirname, DIST),
    clientLogLevel: 'info'
  },

  entry: [ path.join(__dirname, SRC, 'index.js') ],

  output: {
    filename: 'ng-ckeditor5.js',
    library: 'ngCkeditor5',
    libraryTarget: 'umd',
    path: path.join(__dirname, DIST)
  },

  module: {
    rules: [ {
      test: /\.jsx?$/,
      exclude: /node_modules/,
      use: [ {
        loader: 'babel-loader',
        options: {
          presets: [ '@babel/preset-env' ]
        }
      } ]
    } ]
  },

  resolve: {
    extensions: [ '.js' ]
  }
};
