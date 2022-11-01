const fs           = require('fs');
const yargs        = require('yargs');
const browserslist = require('browserslist');

const terser = require('terser');

const autoprefixer = require('autoprefixer');
const postcss      = require('postcss');
const cssVars      = require("postcss-css-variables");

const lightning = require('lightningcss');



let argv = require('yargs')
	.array('i')
	.argv
;
let processType = argv._[0];

if (!({js: true, css: true}[processType])) {
	let errorMessage = `Первым аргументом может быть только js или css. Получено [${processType}]`;
	throw errorMessage;
}
if (!argv.d) {
	throw "Не указан DOCUMENT_ROOT через -d ...";
}
if (!argv.i) {
	throw "Не указан список исходных файлов через -i ... -i ... -i ...";
}
if (!argv.o) {
	throw "Не указан выходной файл через -o";
}



function concatInputFilesList () {
	let r = '';
	for (let fileUrl of argv.i) {
		r += fs.readFileSync(argv.d + '/' + fileUrl);
	}
	return r;
}

let codeString = concatInputFilesList();



if (processType == 'js') {
	terser.minify(codeString, {compress: true, mangle: true, ecma: 2018, safari10: true, sourceMap: false, /*timings: true*/}).then(
		r => {
			fs.writeFileSync(argv.d + '/' + argv.o, r.code, 'utf8');
		}
	);
}




if (processType == 'css') {
	//Добавляем префиксы, заменяем переменные
	postcss(
		[
			autoprefixer({overrideBrowserslist: ['>= 0.25%']}),
			cssVars,
		]
	)
		.process(codeString, {from: undefined})
		.then(r => codeString = r.css)
	;

	//Минифицируем
	codeString = lightning.transform({
		filename:  'styles.css',
		code:      Buffer.from(codeString),
		minify:    true,
		sourceMap: false,
		targets:   lightning.browserslistToTargets(browserslist('>= 0.25%'))
	}).code;

	//Схороняем результата
	fs.writeFileSync(argv.d + '/' + argv.o, codeString, 'utf8');
}
