# Changelog

### [4.0.0] - 2022-10-06

Now requires PHP 8.1 and Laravel 9 (at minimum).

Breaking changes:
- Stricter types.
- PHP 8.1 language features used to clean things up and enforce types.

Other changes:
- Implemented generic type templates to indicate the Eloquent Model.
- Style cleanup, consistency fixes.
- Fixed various issues indicated by PHPStan.


### [3.0.0] - 2021-03-21

Code cleanup. Only supports PHP 7.2 and up, and Laravel 6 and up. Laravel 8 support added.

Breaking changes:
- Fluent syntax support removed in many places. For the sake of cleaner method signatures, many setter (and similar) methods now return `void` instead of `$this`.
- Stricter type hints added. Many methods now have stricter type hints. Some methods that accepted `string|string]]` are now split up into separate methods accepting `string` and `string[]` parameters separately.
- Stricter parameter types. Some cases where `array|Arrayable` was flexibly allowed have now been restricted to `array`.


[4.0.0]: https://github.com/czim/laravel-filter/compare/3.1.0...4.0.0
[3.0.0]: https://github.com/czim/laravel-filter/compare/3.0.0...2.0.3
