module.exports = {
	main_php: {
				src: [ '<%= pkg.pot.src %>' ],
				overwrite: true,
				replacements: [{
					from: / Version:\s*(.*)/,
					to: " Version: <%= pkg.version %>"
				},{
					from: /version =\s*(.*)/,
					to: "version = '<%= pkg.version %>';"
				}]
			}
		};