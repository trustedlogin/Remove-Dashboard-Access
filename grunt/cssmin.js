// https://github.com/gruntjs/grunt-contrib-cssmin
module.exports = {
	target: {
		files: [{
			expand: true,
			cwd: 'assets/css',
			src: 'admin.css',
			dest: 'assets/css',
			ext: '.min.css'
		}]
	}
};
