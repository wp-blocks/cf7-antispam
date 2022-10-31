/**
 * ESLint presets
 */
module.exports = {
	env: {
		browser: true,
		es2021: true,
	},
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	parserOptions: {
		ecmaVersion: 'latest',
		requireConfigFile: false,
		env: { es6: true },
		ecmaFeatures: {
			experimentalObjectRestSpread: true,
		},
	},
};
