# Make targets

Use `deps-install`, `test`, `check`, `lint`, `fmt`, `clean`, and the shared release targets. Coverage reports are generated under `.coverage`.

The project Makefile provides the project-specific build and quality targets plus the shared release targets:

- `make version-show RELEASE_KIND=stable|rc|hotfix` displays the calculated version.
- `make version-bump-major|minor|patch|rc|hotfix` updates `build.properties` atomically.
- Run the normal project build and checks first.
- `make release-verify RELEASE_KIND=...` validates the working tree and tag.
- `make release-tag RELEASE_KIND=...` creates the annotated local tag.
- `make release-publish RELEASE_KIND=...` pushes that tag explicitly.

Release targets never infer a release kind. Existing release tags are preserved; new tags use SemVer with `-rc.N` or `-hotfix.N` suffixes.
