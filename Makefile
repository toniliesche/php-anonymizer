
ifneq ("$(wildcard $(CURDIR)/build.properties)","")
	include $(CURDIR)/build.properties
endif

build-%: set-version-% tag-git-%;

tag-git-%: tests set-version-%
	git tag -a $(build.version) -m "Release $(build.version)"
	git push origin $(build.version)

new-release-branch:
	git checkout -b release/$(build.version.major).$(build.version.minor).x
	git push origin release/$(build.version.major).$(build.version.minor).x

set-version-release:
	$(eval build.version := ${build.version.major}.${build.version.minor}.${build.version.bugfix})

set-version-rc:
	$(eval build.version := ${build.version.major}.${build.version.minor}.${build.version.bugfix}-rc${build.version.candidate})

set-version-patch:
	$(eval build.version := ${build.version.major}.${build.version.minor}.${build.version.bugfix}.${build.version.patch})

increase-%: update-% write-properties
	@echo updated build.properties file

print-version: set-version-release
	@echo ${build.version}

print-branch:
	@echo release/$(build.version.major).$(build.version.minor).x

update-major:
	@$(eval build.version.major := $(shell echo $$(($(build.version.major) + 1))))
	@$(eval build.version.minor := 0)
	@$(eval build.version.bugfix := 0)
	@$(eval build.version.candidate := 1)
	@$(eval build.version.patch := 1)
	@echo new major version: ${build.version.major}

update-minor:
	@$(eval build.version.minor := $(shell echo $$(($(build.version.minor) + 1))))
	@$(eval build.version.bugfix := 0)
	@$(eval build.version.candidate := 1)
	@$(eval build.version.patch := 1)
	@echo new minor version: ${build.version.minor}

update-bugfix:
	@$(eval build.version.bugfix := $(shell echo $$(($(build.version.bugfix) + 1))))
	@$(eval build.version.candidate := 1)
	@$(eval build.version.patch := 1)
	@echo new bugfix version: ${build.version.bugfix}

update-rc:
	@$(eval build.version.candidate := $(shell echo $$(($(build.version.candidate) + 1))))
	@echo new rc version: ${build.version.candidate}

update-patch:
	@$(eval build.version.patch := $(shell echo $$(($(build.version.patch) + 1))))
	@echo new patch version: ${build.version.patch}

write-properties:
	@echo "build.version.major=${build.version.major}" > build.properties.tmp
	@echo "build.version.minor=${build.version.minor}" >> build.properties.tmp
	@echo "build.version.bugfix=${build.version.bugfix}" >> build.properties.tmp
	@echo "build.version.candidate=${build.version.candidate}" >> build.properties.tmp
	@echo "build.version.patch=${build.version.patch}" >> build.properties.tmp
	@rm build.properties
	@mv build.properties.tmp build.properties

fix-code-style fc: rector-fix fix-style swiss-knife;

commit-checks cc: check-style rector-check static-analysis;

push-checks pc: quality-of-code;

quality-of-code: check-style rector-check require-checks security-check mess-detection static-analysis tests;

check-style:
	vendor/bin/php-cs-fixer check src
	vendor/bin/php-cs-fixer check tests

fix-style:
	vendor/bin/php-cs-fixer fix src
	vendor/bin/php-cs-fixer fix tests

fix-packages:
	composer normalize

static-analysis:
	vendor/bin/phpstan

tests: unit-tests integration-tests;

unit-tests:
	vendor/bin/phpunit --testsuite Unit

integration-tests:
	vendor/bin/phpunit --testsuite Integration

test-coverage tc:
	php -d zend_extension=xdebug.so vendor/bin/phpunit --coverage-html=.coverage

test-coverage-xml tcx:
	php -d zend_extension=xdebug.so vendor/bin/phpunit --coverage-xml=.coverage/xml --coverage-html=.coverage/html --log-junit=.coverage/xml/anonymizer.junit.xml

mutation-tests mt: test-coverage-xml
	vendor/bin/infection --show-mutations --coverage=.coverage/xml --logger-html=.coverage/infection.html

require-checks:
	composer-require-checker
	vendor/bin/composer-unused --no-ansi
	composer normalize --dry-run
	composer validate

security-check:
	composer audit

mess-detection:
	vendor/bin/phpmd src,tests text phpmd.xml

md-baseline:
	vendor/bin/phpmd src,tests text phpmd.xml --generate-baseline

rector-check:
	vendor/bin/rector process src tests --dry-run

rector-fix:
	vendor/bin/rector process src tests

swiss-knife sk: finalize-classes privatize-constants spot-lazy-traits check-commented-code;

finalize-classes:
	vendor/bin/swiss-knife finalize-classes src tests

privatize-constants:
	vendor/bin/swiss-knife privatize-constants src tests

spot-lazy-traits:
	vendor/bin/swiss-knife spot-lazy-traits src tests -q --max-used=1

check-commented-code:
	vendor/bin/swiss-knife check-commented-code src tests
