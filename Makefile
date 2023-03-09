all:
	bin/console c:c

assets:
	php bin/console assets:install --symlink

init:
	bin/console symfony:security

doc:
	bin/console nelmio:apidoc:dump --format=json > api-doc.json