const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const production = 'development' !== process.env.NODE_ENV;

module.exports = {
  ...defaultConfig,
  module: {
    ...defaultConfig.module,
    rules: [
      ...defaultConfig.module.rules
    ]
  },
  plugins: [
    ...defaultConfig.plugins.filter(
      plugin => plugin.constructor.name !== 'CleanWebpackPlugin'
    )
  ],
};

if ( production ) {
  module.exports.devtool = false;
}
