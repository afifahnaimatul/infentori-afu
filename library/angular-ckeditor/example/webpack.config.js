const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const BrowserSyncWebpackPlugin = require('browser-sync-webpack-plugin');

const SRC = './src';
const DIST = './dist';
const PORT = 9000;

module.exports = {
  devServer: {
    contentBase: path.join(__dirname, DIST),
    clientLogLevel: 'info'
  },

  entry: [ '@babel/polyfill', path.join(__dirname, SRC, 'index.js') ],

  output: {
    filename: '[name].[chunkhash].js',
    path: path.join(__dirname, DIST)
  },

  module: {
    rules: [ {
      test: /\.html$/,
      loader: 'html-loader'
    },{
      test: /\.jsx?$/,
      exclude: /node_modules/,
      use: [ {
        loader: 'babel-loader',
        options: {
          presets: [ '@babel/preset-env' ]
        }
      } ]
    }, {
      test: /\.scss$/,
      exclude: /node_modules/,
      use: [ {
        loader: 'style-loader'
      }, {
        loader: 'css-loader'
      }, {
        loader: 'sass-loader',
        options: {
          outputStyle: 'compressed',
          sourceMap: true,
          sourceMapContents: true
        }
      }
      ]
    } ]
  },

  plugins: [
    new HtmlWebpackPlugin({
      template: path.join(SRC, 'index.html')
    }),
    new BrowserSyncWebpackPlugin({
      // Don't show any output from BrowserSync
      logLevel: 'silent',
      port: PORT,
      proxy: 'http://localhost:8080'
    })
  ],

  resolve: {
    extensions: [ '.js' ]
  }
};
