# Shared release and version targets for this project.
RELEASE_KIND ?=
RELEASE_REMOTE ?= origin
RELEASE_PROPERTIES ?= $(CURDIR)/build.properties
ifneq (,$(wildcard $(RELEASE_PROPERTIES)))
include $(RELEASE_PROPERTIES)
endif

RELEASE_MAJOR := $(or $(build.version.major),0)
RELEASE_MINOR := $(or $(build.version.minor),0)
RELEASE_PATCH := $(or $(build.version.bugfix),0)
RELEASE_RC := $(or $(build.version.candidate),1)
RELEASE_HOTFIX := $(or $(build.version.patch),1)

ifeq ($(RELEASE_KIND),stable)
RELEASE_VERSION := $(RELEASE_MAJOR).$(RELEASE_MINOR).$(RELEASE_PATCH)
else ifeq ($(RELEASE_KIND),rc)
RELEASE_VERSION := $(RELEASE_MAJOR).$(RELEASE_MINOR).$(RELEASE_PATCH)-rc.$(RELEASE_RC)
else ifeq ($(RELEASE_KIND),hotfix)
RELEASE_VERSION := $(RELEASE_MAJOR).$(RELEASE_MINOR).$(RELEASE_PATCH)-hotfix.$(RELEASE_HOTFIX)
else
RELEASE_VERSION := $(RELEASE_MAJOR).$(RELEASE_MINOR).$(RELEASE_PATCH)-dev
endif

.PHONY: version-show version-bump-major version-bump-minor version-bump-patch
.PHONY: version-bump-rc version-bump-hotfix write-version-properties
.PHONY: release-verify release-build release-tag release-publish

version-show:
	@printf '%s\n' '$(RELEASE_VERSION)'

version-bump-major:
	$(eval build.version.major := $(shell expr $(RELEASE_MAJOR) + 1))
	$(eval build.version.minor := 0)
	$(eval build.version.bugfix := 0)
	$(eval build.version.candidate := 1)
	$(eval build.version.patch := 1)
	@$(MAKE) --no-print-directory write-version-properties build.version.major=$(build.version.major) build.version.minor=$(build.version.minor) build.version.bugfix=$(build.version.bugfix) build.version.candidate=$(build.version.candidate) build.version.patch=$(build.version.patch) build.android.version.code=$(build.android.version.code)

version-bump-minor:
	$(eval build.version.minor := $(shell expr $(RELEASE_MINOR) + 1))
	$(eval build.version.bugfix := 0)
	$(eval build.version.candidate := 1)
	$(eval build.version.patch := 1)
	@$(MAKE) --no-print-directory write-version-properties build.version.major=$(build.version.major) build.version.minor=$(build.version.minor) build.version.bugfix=$(build.version.bugfix) build.version.candidate=$(build.version.candidate) build.version.patch=$(build.version.patch) build.android.version.code=$(build.android.version.code)

version-bump-patch:
	$(eval build.version.bugfix := $(shell expr $(RELEASE_PATCH) + 1))
	$(eval build.version.candidate := 1)
	$(eval build.version.patch := 1)
	@$(MAKE) --no-print-directory write-version-properties build.version.major=$(build.version.major) build.version.minor=$(build.version.minor) build.version.bugfix=$(build.version.bugfix) build.version.candidate=$(build.version.candidate) build.version.patch=$(build.version.patch) build.android.version.code=$(build.android.version.code)

version-bump-rc:
	$(eval build.version.candidate := $(shell expr $(RELEASE_RC) + 1))
	@$(MAKE) --no-print-directory write-version-properties build.version.major=$(build.version.major) build.version.minor=$(build.version.minor) build.version.bugfix=$(build.version.bugfix) build.version.candidate=$(build.version.candidate) build.version.patch=$(build.version.patch) build.android.version.code=$(build.android.version.code)

version-bump-hotfix:
	$(eval build.version.patch := $(shell expr $(RELEASE_HOTFIX) + 1))
	@$(MAKE) --no-print-directory write-version-properties build.version.major=$(build.version.major) build.version.minor=$(build.version.minor) build.version.bugfix=$(build.version.bugfix) build.version.candidate=$(build.version.candidate) build.version.patch=$(build.version.patch) build.android.version.code=$(build.android.version.code)

write-version-properties:
	@tmp_file="$$(mktemp "$(RELEASE_PROPERTIES).XXXXXX")"; \
	{ \
		printf '%s\n' \
			"build.version.major=$(or $(build.version.major),$(RELEASE_MAJOR))" \
			"build.version.minor=$(or $(build.version.minor),$(RELEASE_MINOR))" \
			"build.version.bugfix=$(or $(build.version.bugfix),$(RELEASE_PATCH))" \
			"build.version.candidate=$(or $(build.version.candidate),$(RELEASE_RC))" \
			"build.version.patch=$(or $(build.version.patch),$(RELEASE_HOTFIX))" \
			$(if $(build.android.version.code),"build.android.version.code=$(build.android.version.code)",); \
	} > "$$tmp_file"; \
	mv "$$tmp_file" "$(RELEASE_PROPERTIES)"

release-verify:
	@test -n "$(RELEASE_KIND)" || { echo 'RELEASE_KIND is required (stable, rc, or hotfix)' >&2; exit 2; }
	@test "$(RELEASE_KIND)" = stable || test "$(RELEASE_KIND)" = rc || test "$(RELEASE_KIND)" = hotfix || { echo 'invalid RELEASE_KIND' >&2; exit 2; }
	@git diff --quiet || { echo 'working tree is not clean; commit version changes first' >&2; exit 1; }
	@git diff --cached --quiet || { echo 'index is not clean' >&2; exit 1; }
	@git show-ref --tags --verify --quiet "refs/tags/$(RELEASE_VERSION)" && { echo "tag already exists: $(RELEASE_VERSION)" >&2; exit 1; } || true

release-build: release-verify
	@printf '%s\n' "validated release $(RELEASE_VERSION); run the project build before release-tag"

release-tag: release-build
	@if [ "$(DRY_RUN)" = 1 ]; then \
		printf '%s\n' "git tag -a $(RELEASE_VERSION) -m Release $(RELEASE_VERSION)"; \
	else \
		git tag -a "$(RELEASE_VERSION)" -m "Release $(RELEASE_VERSION)"; \
	fi

release-publish: release-tag
	@if [ "$(DRY_RUN)" = 1 ]; then \
		printf '%s\n' "git push $(RELEASE_REMOTE) $(RELEASE_VERSION)"; \
	else \
		git push "$(RELEASE_REMOTE)" "$(RELEASE_VERSION)"; \
	fi
