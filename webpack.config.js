const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      'admin-app': './src/index.tsx',
    },
    output: {
      path: path.resolve(__dirname, 'assets/js'),
      filename: '[name].js',
      clean: true,
    },
    resolve: {
      extensions: ['.tsx', '.ts', '.js', '.jsx'],
      alias: {
        '@': path.resolve(__dirname, 'src'),
      },
    },
    module: {
      rules: [
        {
          test: /\.tsx?$/,
          use: 'ts-loader',
          exclude: /node_modules/,
        },
        {
          test: /\.css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'postcss-loader',
          ],
        },
      ],
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: '../css/admin-app.css',
      }),
    ],
    externals: {
      react: 'React',
      'react-dom': 'ReactDOM',
    },
    optimization: {
      minimize: isProduction,
    },
    devtool: isProduction ? false : 'source-map',
    stats: {
      children: false,
      modules: false,
    },
  };
};
