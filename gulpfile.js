// Grab our gulp packages
const gulp = require('gulp');
const { src, dest, watch, series } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const svgSprite = require('gulp-svg-sprite');
const browsersync = require('browser-sync').create();

// file paths
const files = {
	scssPath: 'assets/scss/*.scss',
	jsPath: 'assets/js/*.js',
	svgPath: 'assets/svg-originals/*.svg',
	phpPath: '*.php',
	nestedPhpPath: '**/*.php',
};

// Sass task: compiles scss -> css
function stylesTask() {
	return src(files.scssPath) // set source and turn on sourcemaps
		.pipe(sass().on('error', sass.logError))
		.pipe(dest('assets/css'));
}


function svgsTask(){
	var config = {
		mode: {
			symbol: { // symbol mode to build the SVG
				render: {
					css: false, // CSS output option for icon sizing
					scss: true // SCSS output option for icon sizing
				},
				dest: 'svg', // destination folder
				prefix: '.icon-%s', // BEM-style prefix if styles rendered
				sprite: 'icons.svg', //generated sprite name
				example: true, // Build a sample page, please!
				svg:{
					xmlDeclaration: false,
					namespaceClassnames: false,
				}
			}
		}
	};

	return src( 'assets/svg-originals/*.svg' )

		.pipe( svgSprite( config ) )
		.pipe( dest( 'assets/svg' ) );
}

function browsersyncServe(cb){
	browsersync.init({
		proxy: "http://zaobank.local"
	});
	cb();
}

function browsersyncReload(cb){
	browsersync.reload();
	cb();
}

exports.default = series(
	stylesTask,
	svgsTask,
	// browsersyncServe,
	// watchTask
);


// Watch Task
function watchTask(){
	watch(
		[files.scssPath, files.svgPath, files.phpPath, files.nestedPhpPath],
		{ interval: 1000, usePolling: true }, //Makes docker work
		series(stylesTask, svgsTask, browsersyncReload)
	);
}

exports.watch = series(
	stylesTask,
	svgsTask,
	browsersyncServe,
	watchTask
);
