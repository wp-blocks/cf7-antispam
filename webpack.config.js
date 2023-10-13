const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const addModule = ( fileName, filePath ) => {
	return {
		...defaultConfig,
		name: fileName,
		entry: path.resolve( __dirname, filePath + fileName ),
		output: {
			path: path.resolve( __dirname, filePath + '../dist/' ),
			filename: fileName,
		},
	};
};

const mainScript = addModule( 'script.js', 'core/src/' );
const adminScript = addModule( 'admin-scripts.js', 'admin/src/' );

module.exports = [ mainScript, adminScript ];
