# TODO: Fix Doctrine Deprecation Warnings

- [x] Edit config/packages/doctrine.yaml to add enable_native_lazy_objects: true under orm
- [x] Remove report_fields_where_declared: true from orm
- [x] Add controller_resolver.auto_mapping: true under orm
- [ ] Run php bin/console cache:clear to verify warnings are resolved
