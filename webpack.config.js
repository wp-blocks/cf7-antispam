const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		script: path.resolve(process.cwd(), `src/script.ts`),
		'admin-scripts': path.resolve(process.cwd(), `src/admin-scripts.js`),
	},
};
